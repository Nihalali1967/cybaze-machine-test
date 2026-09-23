<?php

use App\Libraries\ProfitCalculator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pure maths, no database: proration, schedule stepping and period keys.
 */
final class ProfitCalculatorTest extends CIUnitTestCase
{
    /**
     * The spec's section 3/4 example: ₹50,000 at 12%, paid monthly, produces
     * ₹500 per period. 50000 * 12 / 100 / 12 = 500.
     */
    public function testMatchesTheSpecExample(): void
    {
        $this->assertSame(500.0, ProfitCalculator::periodProfit(50000.0, 12.0, 'monthly'));
    }

    /**
     * The four packages from the spec's package table.
     */
    public function testPackageTableExamples(): void
    {
        $this->assertSame(19.23, ProfitCalculator::periodProfit(10000.0, 10.0, 'weekly'));
        $this->assertSame(312.50, ProfitCalculator::periodProfit(25000.0, 15.0, 'monthly'));
        $this->assertSame(2500.0, ProfitCalculator::periodProfit(50000.0, 20.0, 'quarterly'));
        $this->assertSame(25000.0, ProfitCalculator::periodProfit(100000.0, 25.0, 'yearly'));
    }

    public function testPerPeriodBasisPaysTheFullRateEveryPeriod(): void
    {
        $this->assertSame(
            6000.0,
            ProfitCalculator::periodProfit(50000.0, 12.0, 'monthly', ProfitCalculator::BASIS_PER_PERIOD),
        );
    }

    public function testPeriodsPerYear(): void
    {
        $this->assertSame(52, ProfitCalculator::periodsPerYear('weekly'));
        $this->assertSame(12, ProfitCalculator::periodsPerYear('monthly'));
        $this->assertSame(4, ProfitCalculator::periodsPerYear('quarterly'));
        $this->assertSame(1, ProfitCalculator::periodsPerYear('yearly'));
        $this->assertTrue(ProfitCalculator::isValidType('monthly'));
        $this->assertFalse(ProfitCalculator::isValidType('daily'));
    }

    public function testRejectsAnUnknownWithdrawalType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ProfitCalculator::periodProfit(1000.0, 10.0, 'daily');
    }

    public function testNextDateAdvancesByOnePeriod(): void
    {
        $this->assertSame('2026-09-30', ProfitCalculator::nextDate('weekly', '2026-09-23'));
        $this->assertSame('2026-10-23', ProfitCalculator::nextDate('monthly', '2026-09-23'));
        $this->assertSame('2026-12-23', ProfitCalculator::nextDate('quarterly', '2026-09-23'));
        $this->assertSame('2027-09-23', ProfitCalculator::nextDate('yearly', '2026-09-23'));
    }

    /**
     * Without clamping, PHP turns 31 Jan + 1 month into 3 March and an
     * investor would be paid on the wrong day.
     */
    public function testClampsToTheEndOfTheTargetMonth(): void
    {
        $this->assertSame('2026-02-28', ProfitCalculator::nextDate('monthly', '2026-01-31'));
        $this->assertSame('2025-04-30', ProfitCalculator::nextDate('quarterly', '2025-01-31'));
        $this->assertSame('2026-09-30', ProfitCalculator::nextDate('monthly', '2026-08-31'));
        $this->assertSame('2025-02-28', ProfitCalculator::nextDate('yearly', '2024-02-29'));
    }

    public function testDoesNotClampWhenTheDayExistsInTheTargetMonth(): void
    {
        $this->assertSame('2026-11-15', ProfitCalculator::nextDate('monthly', '2026-10-15'));
        $this->assertSame('2026-02-28', ProfitCalculator::nextDate('monthly', '2026-01-28'));
        $this->assertSame('2026-01-31', ProfitCalculator::nextDate('monthly', '2025-12-31'));
    }

    /**
     * A quarterly position opened 9 months ago owes exactly three payouts.
     */
    public function testWalksTheScheduleForwardFromTheInvestmentDate(): void
    {
        $date = '2025-12-23';
        $due  = [];

        for ($i = 0; $i < 3; $i++) {
            $date  = ProfitCalculator::nextDate('quarterly', $date);
            $due[] = $date;
        }

        $this->assertSame(['2026-03-23', '2026-06-23', '2026-09-23'], $due);
    }

    public function testPeriodKeys(): void
    {
        $this->assertSame('2026-W39', ProfitCalculator::periodKey('weekly', '2026-09-23'));
        $this->assertSame('2026-09', ProfitCalculator::periodKey('monthly', '2026-09-23'));
        $this->assertSame('2026-Q3', ProfitCalculator::periodKey('quarterly', '2026-09-23'));
        $this->assertSame('2026', ProfitCalculator::periodKey('yearly', '2026-09-23'));

        $this->assertSame('2026-Q1', ProfitCalculator::periodKey('quarterly', '2026-02-10'));
        $this->assertSame('2026-Q4', ProfitCalculator::periodKey('quarterly', '2026-11-05'));
    }

    /**
     * ISO weeks belong to the ISO year: Monday 29 Dec 2025 is week 1 of 2026,
     * which a naive "Y-W" format would wrongly report as 2025-W01.
     */
    public function testWeeklyKeyUsesTheIsoYear(): void
    {
        $this->assertSame('2026-W01', ProfitCalculator::periodKey('weekly', '2025-12-29'));
        $this->assertSame('2026-W01', ProfitCalculator::periodKey('weekly', '2026-01-01'));
    }
}
