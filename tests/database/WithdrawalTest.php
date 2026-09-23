<?php

use App\Libraries\ProfitProcessor;
use App\Libraries\WithdrawalService;
use App\Models\InvestmentModel;
use App\Models\ProfitModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Traits\InvestmentFixtures;

/**
 * Section 5 of the spec: eligibility checks and single-payout guarantees.
 */
final class WithdrawalTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use InvestmentFixtures;

    protected $refresh   = true;
    protected $namespace = 'App';
    protected $seed      = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerId = $this->createCustomer();
    }

    private function service(): WithdrawalService
    {
        return new WithdrawalService();
    }

    /**
     * John's position: ₹50,000 Gold, quarterly, started 9 months ago, with
     * its three payouts already processed.
     *
     * @return array<string, mixed>
     */
    private function processedQuarterlyInvestment(): array
    {
        $package    = $this->createPackage('Gold', 50000.0, 20.0, 'quarterly');
        $investment = $this->createInvestment($package, $this->monthsAgo(9));

        (new ProfitProcessor())->process($this->today());

        return $investment;
    }

    public function testWithdrawsEveryAvailableProfitRow(): void
    {
        $investment = $this->processedQuarterlyInvestment();

        $available = $this->service()->available($this->customerId, (int) $investment['id']);

        $this->assertTrue($available['eligible'], $available['reason']);
        $this->assertSame(7500.0, $available['amount']);
        $this->assertSame(3, $available['count']);

        $result = $this->service()->withdraw($this->customerId, (int) $investment['id']);

        $this->assertTrue($result['ok'], $result['message']);
        $this->assertSame(7500.0, $result['amount']);
        $this->assertSame(3, $result['count']);

        $this->assertSame(3, $this->db->table('profits')->where('status', 'withdrawn')->countAllResults());
        $this->assertSame(0, $this->db->table('profits')->where('status', 'available')->countAllResults());

        $withdrawal = $this->db->table('withdrawals')->get()->getRowArray();

        $this->assertSame('paid', $withdrawal['status']);
        $this->assertSame(3, (int) $withdrawal['profit_count']);
        $this->assertSame(7500.0, (float) $withdrawal['amount']);
        $this->assertSame((int) $investment['id'], (int) $withdrawal['investment_id']);

        // Every claimed row points back at the payout that consumed it.
        $this->assertSame(3, $this->db->table('profits')->where('withdrawal_id', $withdrawal['id'])->countAllResults());
    }

    public function testSecondWithdrawalFindsNothingLeft(): void
    {
        $investment = $this->processedQuarterlyInvestment();

        $first = $this->service()->withdraw($this->customerId, (int) $investment['id']);
        $this->assertTrue($first['ok']);

        $available = $this->service()->available($this->customerId, (int) $investment['id']);
        $this->assertFalse($available['eligible']);
        $this->assertSame(0.0, $available['amount']);

        $second = $this->service()->withdraw($this->customerId, (int) $investment['id']);
        $this->assertFalse($second['ok']);
        $this->assertStringContainsString('No profit is available', $second['message']);

        // Still exactly one payout.
        $this->assertSame(1, $this->db->table('withdrawals')->countAllResults());
    }

    public function testCannotWithdrawBeforeTheFirstPeriodArrives(): void
    {
        $package = $this->createPackage('Premium', 100000.0, 25.0, 'yearly');
        $this->createInvestment($package, $this->today());

        (new ProfitProcessor())->process($this->today());

        $available = $this->service()->available($this->customerId);

        $this->assertFalse($available['eligible']);
        $this->assertSame(0.0, $available['amount']);
        $this->assertStringContainsString('No profit is available', $available['reason']);

        $result = $this->service()->withdraw($this->customerId);
        $this->assertFalse($result['ok']);
        $this->assertSame(0, $this->db->table('withdrawals')->countAllResults());
    }

    public function testCancelledInvestmentIsBlocked(): void
    {
        $investment = $this->processedQuarterlyInvestment();

        (new InvestmentModel())->update($investment['id'], ['status' => 'cancelled']);

        $available = $this->service()->available($this->customerId, (int) $investment['id']);
        $this->assertFalse($available['eligible']);
        $this->assertStringContainsString('cancelled', $available['reason']);

        $result = $this->service()->withdraw($this->customerId, (int) $investment['id']);
        $this->assertFalse($result['ok']);
        $this->assertSame(0, $this->db->table('withdrawals')->countAllResults());

        // The global balance agrees, so the two figures can never disagree.
        $global = $this->service()->available($this->customerId);
        $this->assertFalse($global['eligible']);
        $this->assertSame(0.0, (new ProfitModel())->totals($this->customerId)['available']);
    }

    public function testWithdrawalCanBeScopedToOneInvestment(): void
    {
        $gold   = $this->createPackage('Gold', 50000.0, 20.0, 'quarterly');
        $silver = $this->createPackage('Silver', 25000.0, 15.0, 'monthly');

        $goldInvestment   = $this->createInvestment($gold, $this->monthsAgo(9));
        $silverInvestment = $this->createInvestment($silver, $this->monthsAgo(5));

        (new ProfitProcessor())->process($this->today());

        $result = $this->service()->withdraw($this->customerId, (int) $silverInvestment['id']);

        $this->assertTrue($result['ok'], $result['message']);
        $this->assertSame(1562.50, $result['amount']);
        $this->assertSame(5, $result['count']);

        // Only the silver position was paid out.
        $this->assertSame(
            5,
            $this->db->table('profits')->where('investment_id', $silverInvestment['id'])->where('status', 'withdrawn')->countAllResults(),
        );
        $this->assertSame(
            0,
            $this->db->table('profits')->where('investment_id', $goldInvestment['id'])->where('status', 'withdrawn')->countAllResults(),
        );
        $this->assertSame(
            3,
            $this->db->table('profits')->where('investment_id', $goldInvestment['id'])->where('status', 'available')->countAllResults(),
        );

        // The gold balance is still withdrawable afterwards.
        $goldBalance = $this->service()->available($this->customerId, (int) $goldInvestment['id']);
        $this->assertTrue($goldBalance['eligible']);
        $this->assertSame(7500.0, $goldBalance['amount']);
    }

    public function testWithdrawingEverythingPaysBothPositionsInOnePayout(): void
    {
        $gold   = $this->createPackage('Gold', 50000.0, 20.0, 'quarterly');
        $silver = $this->createPackage('Silver', 25000.0, 15.0, 'monthly');

        $this->createInvestment($gold, $this->monthsAgo(9));
        $this->createInvestment($silver, $this->monthsAgo(5));

        (new ProfitProcessor())->process($this->today());

        $result = $this->service()->withdraw($this->customerId);

        $this->assertTrue($result['ok'], $result['message']);
        $this->assertSame(7500.0 + 1562.50, $result['amount']);
        $this->assertSame(8, $result['count']);

        $this->assertSame(1, $this->db->table('withdrawals')->countAllResults());
        $this->assertSame(8, $this->db->table('profits')->where('status', 'withdrawn')->countAllResults());
        $this->assertSame(0, $this->db->table('profits')->where('status', 'available')->countAllResults());
    }
}
