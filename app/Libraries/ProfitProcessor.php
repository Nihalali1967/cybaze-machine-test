<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Investment as InvestmentConfig;
use Throwable;

/**
 * Generates profit rows for every period that has fallen due.
 *
 * Design notes:
 *  - It is idempotent. Running it twice on the same day creates nothing new.
 *  - It catches up. If nobody clicked for six weeks, one run creates all six
 *    missed periods instead of silently dropping five.
 *  - Duplicate protection has three layers: a per-row existence check, the
 *    UNIQUE (investment_id, due_date) key, and the fact that
 *    next_profit_date only advances inside the same transaction.
 */
class ProfitProcessor
{
    protected BaseConnection $db;
    protected InvestmentConfig $config;

    public function __construct(?BaseConnection $db = null, ?InvestmentConfig $config = null)
    {
        $this->db     = $db ?? db_connect();
        $this->config = $config ?? config('Investment');
    }

    /**
     * Read-only: what would a run produce? Nothing is written.
     */
    public function preview(string $asOf): array
    {
        return $this->traverse($asOf, true, null);
    }

    /**
     * Commit every profit period due up to and including $asOf.
     */
    public function process(string $asOf, ?int $runBy = null): array
    {
        return $this->traverse($asOf, false, $runBy);
    }

    /**
     * @return array{
     *     as_of: string, dry_run: bool, investments_scanned: int,
     *     periods_created: int, periods_skipped: int, total_amount: float,
     *     details: array<int, array<string, mixed>>, run_id: int|null
     * }
     */
    protected function traverse(string $asOf, bool $dryRun, ?int $runBy): array
    {
        $report = [
            'as_of'               => $asOf,
            'dry_run'             => $dryRun,
            'investments_scanned' => 0,
            'periods_created'     => 0,
            'periods_skipped'     => 0,
            'total_amount'        => 0.0,
            'details'             => [],
            'run_id'              => null,
        ];

        // A dry run must not touch investments or profits, so it needs no
        // transaction - only the audit row is written.
        if ($dryRun) {
            $this->collect($report, $asOf, true);

            $report['run_id'] = $this->logRun($report, $runBy);

            return $report;
        }

        $this->db->transBegin();

        try {
            $this->collect($report, $asOf, false);
            $report['run_id'] = $this->logRun($report, $runBy);

            if ($this->db->transStatus() === false) {
                throw new DatabaseException('Profit processing transaction failed.');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }

        return $report;
    }

    /**
     * Walk every due investment and fill in the report.
     *
     * @param array<string, mixed> $report
     */
    protected function collect(array &$report, string $asOf, bool $dryRun): void
    {
        $investments = $this->dueInvestments($asOf, ! $dryRun);

        $report['investments_scanned'] = count($investments);

        foreach ($investments as $investment) {
            $detail = $this->processInvestment($investment, $asOf, $dryRun);

            if ($detail['periods'] === []) {
                continue;
            }

            $report['details'][]        = $detail;
            $report['periods_created'] += $detail['created'];
            $report['periods_skipped'] += $detail['skipped'];
            $report['total_amount']    += $detail['amount'];
        }
    }

    /**
     * Active investments whose next payout has arrived.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function dueInvestments(string $asOf, bool $lock): array
    {
        $sql = 'SELECT i.*, u.name AS customer_name'
            . ' FROM ' . $this->db->prefixTable('investments') . ' AS i'
            . ' INNER JOIN ' . $this->db->prefixTable('users') . ' AS u ON u.id = i.user_id'
            . ' WHERE i.status = ? AND i.next_profit_date IS NOT NULL AND i.next_profit_date <= ?'
            . ' ORDER BY i.next_profit_date ASC, i.id ASC';

        if ($lock && $this->supportsRowLocking()) {
            // Serialises two admins who click "Process Profit" at the same moment.
            $sql .= ' FOR UPDATE';
        }

        return $this->db->query($sql, ['active', $asOf])->getResultArray();
    }

    /**
     * Fill every due period for a single investment.
     *
     * @param array<string, mixed> $investment
     *
     * @return array<string, mixed>
     */
    protected function processInvestment(array $investment, string $asOf, bool $dryRun): array
    {
        $investmentId = (int) $investment['id'];
        $type         = (string) $investment['withdrawal_type'];
        $profitAmount = (float) $investment['period_profit_amount'];
        $maturity     = $investment['maturity_periods'] === null ? null : (int) $investment['maturity_periods'];

        $nextDate    = $investment['next_profit_date'];
        $periodsPaid = (int) $investment['periods_paid'];
        $status      = (string) $investment['status'];
        $periodStart = (string) $investment['investment_date'];

        // When resuming mid-schedule, the previous payout date is the real
        // start of the next period.
        if ($periodsPaid > 0) {
            $periodStart = $this->lastDueDate($investmentId) ?? $periodStart;
        }

        $created = 0;
        $skipped = 0;
        $amount  = 0.0;
        $periods = [];

        while ($nextDate !== null && $nextDate <= $asOf) {
            $periodKey = ProfitCalculator::periodKey($type, $nextDate);

            if ($this->profitExists($investmentId, $nextDate)) {
                // Layer 1: this period was already generated.
                $skipped++;
                $state = 'already_exists';
            } else {
                $state = 'created';

                if (! $dryRun) {
                    try {
                        $this->insertProfit([
                            'investment_id'     => $investmentId,
                            'user_id'           => (int) $investment['user_id'],
                            'period_key'        => $periodKey,
                            'period_start'      => $periodStart,
                            'period_end'        => $nextDate,
                            'due_date'          => $nextDate,
                            'investment_amount' => (float) $investment['investment_amount'],
                            'profit_amount'     => $profitAmount,
                            'status'            => 'available',
                            'processed_at'      => date('Y-m-d H:i:s'),
                        ]);
                        $created++;
                        $amount += $profitAmount;
                    } catch (DatabaseException $e) {
                        if (! $this->isDuplicateKey($e)) {
                            throw $e;
                        }

                        // Layer 2: the unique key caught a concurrent insert.
                        $skipped++;
                        $state = 'already_exists';
                    }
                } else {
                    $created++;
                    $amount += $profitAmount;
                }
            }

            $periods[] = [
                'period_key' => $periodKey,
                'due_date'   => $nextDate,
                'amount'     => $profitAmount,
                'state'      => $state,
            ];

            // Layer 3: the schedule only moves forward inside this transaction.
            $periodStart = $nextDate;
            $nextDate    = ProfitCalculator::nextDate($type, $nextDate);
            $periodsPaid++;

            if ($maturity !== null && $periodsPaid >= $maturity) {
                $status   = 'completed';
                $nextDate = null;

                break;
            }
        }

        if (! $dryRun && $periods !== []) {
            $this->db->table('investments')->where('id', $investmentId)->update([
                'next_profit_date' => $nextDate,
                'periods_paid'     => $periodsPaid,
                'status'           => $status,
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'investment_id'   => $investmentId,
            'customer_name'   => $investment['customer_name'],
            'package_name'    => $investment['package_name'],
            'withdrawal_type' => $type,
            'investment_amount' => (float) $investment['investment_amount'],
            'period_profit_amount' => $profitAmount,
            'created'         => $created,
            'skipped'         => $skipped,
            'amount'          => $amount,
            'periods'         => $periods,
            'next_profit_date' => $nextDate,
            'status'          => $status,
        ];
    }

    protected function profitExists(int $investmentId, string $dueDate): bool
    {
        return $this->db->table('profits')
            ->where('investment_id', $investmentId)
            ->where('due_date', $dueDate)
            ->countAllResults() > 0;
    }

    protected function lastDueDate(int $investmentId): ?string
    {
        $row = $this->db->table('profits')
            ->select('due_date')
            ->where('investment_id', $investmentId)
            ->orderBy('due_date', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        return $row['due_date'] ?? null;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function insertProfit(array $row): void
    {
        $now = date('Y-m-d H:i:s');

        $row['created_at'] = $now;
        $row['updated_at'] = $now;

        $this->db->table('profits')->insert($row);
    }

    /**
     * @param array<string, mixed> $report
     */
    protected function logRun(array $report, ?int $runBy): ?int
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('profit_runs')->insert([
            'run_by'              => $runBy,
            'as_of'               => $report['as_of'],
            'investments_scanned' => $report['investments_scanned'],
            'periods_created'     => $report['periods_created'],
            'periods_skipped'     => $report['periods_skipped'],
            'total_amount'        => $report['total_amount'],
            'is_dry_run'          => $report['dry_run'] ? 1 : 0,
            'run_at'              => $now,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        return (int) $this->db->insertID();
    }

    protected function isDuplicateKey(DatabaseException $e): bool
    {
        return $e->getCode() === 1062 || str_contains($e->getMessage(), 'Duplicate entry');
    }

    protected function supportsRowLocking(): bool
    {
        return in_array($this->db->DBDriver, ['MySQLi', 'MySQL', 'Postgre', 'SQLSRV', 'OCI8'], true);
    }
}
