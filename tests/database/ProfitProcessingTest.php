<?php

use App\Libraries\ProfitCalculator;
use App\Libraries\ProfitProcessor;
use App\Models\InvestmentModel;
use App\Models\PackageModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Traits\InvestmentFixtures;

/**
 * Section 3 of the spec: process weekly profits, never twice for one period.
 */
final class ProfitProcessingTest extends CIUnitTestCase
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

    private function processor(): ProfitProcessor
    {
        return new ProfitProcessor();
    }

    /**
     * The payout dates the spec's rules imply for this investment, computed
     * independently of the processor so the assertion stays date-independent.
     *
     * @param array<string, mixed> $investment
     *
     * @return list<string>
     */
    private function expectedDueDates(array $investment): array
    {
        $dates  = [];
        $cursor = $investment['next_profit_date'];

        while ($cursor !== null && $cursor <= $this->today() && count($dates) < 24) {
            $dates[] = $cursor;
            $cursor  = ProfitCalculator::nextDate($investment['withdrawal_type'], $cursor);
        }

        return $dates;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function profitRows(int $investmentId): array
    {
        return $this->db->table('profits')
            ->where('investment_id', $investmentId)
            ->orderBy('due_date', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function testCreatesEveryDuePeriodInOneRun(): void
    {
        $package    = $this->createPackage('Gold', 50000.0, 20.0, 'quarterly');
        $investment = $this->createInvestment($package, $this->monthsAgo(9));

        $expected = $this->expectedDueDates($investment);
        $this->assertCount(3, $expected, 'The demo position should owe three quarterly payouts.');

        $report = $this->processor()->process($this->today());

        $this->assertSame(1, $report['investments_scanned']);
        $this->assertSame(3, $report['periods_created']);
        $this->assertSame(0, $report['periods_skipped']);
        $this->assertSame(7500.0, $report['total_amount']);

        $rows = $this->profitRows((int) $investment['id']);
        $this->assertSame($expected, array_column($rows, 'due_date'));

        foreach ($rows as $row) {
            $this->assertSame(2500.0, (float) $row['profit_amount']);
            $this->assertSame(50000.0, (float) $row['investment_amount']);
            $this->assertSame('available', $row['status']);
            $this->assertNull($row['withdrawal_id']);
        }

        $this->assertSame(
            ProfitCalculator::periodKey('quarterly', $expected[0]),
            $rows[0]['period_key'],
        );

        // The schedule advanced past the last payout and paid 3 periods.
        $after = (new InvestmentModel())->find($investment['id']);
        $this->assertSame(3, (int) $after['periods_paid']);
        $this->assertSame(
            ProfitCalculator::nextDate('quarterly', $expected[2]),
            $after['next_profit_date'],
        );
        $this->assertSame('active', $after['status']);
    }

    public function testRunningTwiceCreatesNothingNew(): void
    {
        $package    = $this->createPackage('Gold', 50000.0, 20.0, 'quarterly');
        $investment = $this->createInvestment($package, $this->monthsAgo(9));

        $first = $this->processor()->process($this->today());

        $this->assertSame(3, $first['periods_created']);
        $this->assertSame(3, $this->db->table('profits')->countAllResults());

        $second = $this->processor()->process($this->today());

        // The schedule already moved past today, so the position is not even
        // scanned on the second run: nothing to create and nothing to skip.
        $this->assertSame(0, $second['investments_scanned']);
        $this->assertSame(0, $second['periods_created']);
        $this->assertSame(0, $second['periods_skipped']);
        $this->assertSame(0.0, $second['total_amount']);
        $this->assertSame(3, $this->db->table('profits')->countAllResults());

        // The investment did not move either.
        $after = (new InvestmentModel())->find($investment['id']);
        $this->assertSame(3, (int) $after['periods_paid']);

        // A third run on the date the next payout actually falls due adds
        // exactly one period and nothing more.
        $third = $this->processor()->process($after['next_profit_date']);
        $this->assertSame(1, $third['periods_created']);
        $this->assertSame(4, $this->db->table('profits')->countAllResults());
    }

    /**
     * The other half of the duplicate guard: when a period row already exists
     * but the schedule never advanced (an interrupted run, or a row inserted
     * by hand), the processor must report it as skipped rather than pay it
     * a second time.
     */
    public function testAlreadyGeneratedPeriodIsSkippedNotRecreated(): void
    {
        $package    = $this->createPackage('Gold', 50000.0, 20.0, 'quarterly');
        $investment = $this->createInvestment($package, $this->monthsAgo(9));

        $this->processor()->process($this->today());

        // Rewind the schedule so the already-paid periods fall due again.
        (new InvestmentModel())->update($investment['id'], [
            'next_profit_date' => $investment['next_profit_date'],
            'periods_paid'     => 0,
        ]);

        $rerun = $this->processor()->process($this->today());

        $this->assertSame(0, $rerun['periods_created']);
        $this->assertSame(3, $rerun['periods_skipped']);
        $this->assertSame(0.0, $rerun['total_amount']);
        $this->assertSame(3, $this->db->table('profits')->countAllResults());
    }

    public function testPreviewWritesNothing(): void
    {
        $package    = $this->createPackage('Gold', 50000.0, 20.0, 'quarterly');
        $investment = $this->createInvestment($package, $this->monthsAgo(9));

        $preview = $this->processor()->preview($this->today());

        $this->assertTrue($preview['dry_run']);
        $this->assertSame(3, $preview['periods_created']);
        $this->assertSame(7500.0, $preview['total_amount']);

        $this->assertSame(0, $this->db->table('profits')->countAllResults());

        $after = (new InvestmentModel())->find($investment['id']);
        $this->assertSame($investment['next_profit_date'], $after['next_profit_date']);
        $this->assertSame(0, (int) $after['periods_paid']);

        // Even a dry run is audited.
        $this->assertSame(1, $this->db->table('profit_runs')->where('is_dry_run', 1)->countAllResults());
        $this->assertSame(0, $this->db->table('profit_runs')->where('is_dry_run', 0)->countAllResults());
    }

    /**
     * The hard guarantee: even a direct insert cannot duplicate a period.
     */
    public function testDatabaseRejectsADuplicatePeriod(): void
    {
        $package    = $this->createPackage('Gold', 50000.0, 20.0, 'quarterly');
        $investment = $this->createInvestment($package, $this->monthsAgo(9));

        $this->processor()->process($this->today());

        $row = $this->profitRows((int) $investment['id'])[0];

        $this->expectException(DatabaseException::class);

        $this->db->table('profits')->insert([
            'investment_id'     => $row['investment_id'],
            'user_id'           => $row['user_id'],
            'period_key'        => $row['period_key'],
            'period_start'      => $row['period_start'],
            'period_end'        => $row['period_end'],
            'due_date'          => $row['due_date'],
            'investment_amount' => $row['investment_amount'],
            'profit_amount'     => $row['profit_amount'],
            'status'            => 'available',
            'processed_at'      => date('Y-m-d H:i:s'),
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * The spec's section 3 example, end to end.
     */
    public function testProfitAmountMatchesTheSpecExample(): void
    {
        $package    = $this->createPackage('Monthly Twelve', 50000.0, 12.0, 'monthly');
        $investment = $this->createInvestment($package, $this->monthsAgo(3));

        $this->assertSame(500.0, (float) $investment['period_profit_amount']);

        $this->processor()->process($this->today());

        $rows = $this->profitRows((int) $investment['id']);
        $this->assertCount(3, $rows);

        foreach ($rows as $row) {
            $this->assertSame(500.0, (float) $row['profit_amount']);
            $this->assertSame(50000.0, (float) $row['investment_amount']);
        }
    }

    public function testMaturityCompletesTheInvestment(): void
    {
        $package    = $this->createPackage('Silver', 25000.0, 15.0, 'monthly');
        $investment = $this->createInvestment($package, $this->monthsAgo(5), 5);

        $report = $this->processor()->process($this->today());

        $this->assertSame(5, $report['periods_created']);
        $this->assertSame(1562.50, $report['total_amount']);

        $after = (new InvestmentModel())->find($investment['id']);
        $this->assertSame('completed', $after['status']);
        $this->assertNull($after['next_profit_date']);
        $this->assertSame(5, (int) $after['periods_paid']);

        // A matured position stops accruing.
        $again = $this->processor()->process($this->today());
        $this->assertSame(0, $again['investments_scanned']);
        $this->assertSame(0, $again['periods_created']);
    }

    /**
     * Section 2's "Important" requirement, proven end to end.
     */
    public function testPackageEditsDoNotChangeExistingInvestments(): void
    {
        $package    = $this->createPackage('Gold', 50000.0, 20.0, 'quarterly');
        $investment = $this->createInvestment($package, $this->monthsAgo(9));

        // The admin changes both the rate and the payout frequency.
        (new PackageModel())->update($package['id'], [
            'return_percentage' => 99.0,
            'withdrawal_type'   => 'yearly',
        ]);

        $report = $this->processor()->process($this->today());

        $this->assertSame(2500.0, $report['details'][0]['period_profit_amount']);

        foreach ($this->profitRows((int) $investment['id']) as $row) {
            $this->assertSame(2500.0, (float) $row['profit_amount']);
        }

        $after = (new InvestmentModel())->find($investment['id']);
        $this->assertSame(20.0, (float) $after['return_percentage']);
        $this->assertSame('quarterly', $after['withdrawal_type']);
        $this->assertSame('Gold', $after['package_name']);
    }

    public function testInvestmentMadeTodayIsNotProcessed(): void
    {
        $package    = $this->createPackage('Premium', 100000.0, 25.0, 'yearly');
        $investment = $this->createInvestment($package, $this->today());

        $report = $this->processor()->process($this->today());

        $this->assertSame(0, $report['investments_scanned']);
        $this->assertSame(0, $this->db->table('profits')->countAllResults());

        $after = (new InvestmentModel())->find($investment['id']);
        $this->assertSame(ProfitCalculator::nextDate('yearly', $this->today()), $after['next_profit_date']);
        $this->assertSame(0, (int) $after['periods_paid']);
    }
}
