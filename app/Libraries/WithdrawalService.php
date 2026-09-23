<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use Config\Investment as InvestmentConfig;

/**
 * Profit withdrawal.
 *
 * The claim on the profit rows is an UPDATE ... WHERE status = 'available'
 * with an affected-rows assertion, so two clicks (or two tabs) can never pay
 * the same profit twice.
 */
class WithdrawalService
{
    protected BaseConnection $db;
    protected InvestmentConfig $config;

    public function __construct(?BaseConnection $db = null, ?InvestmentConfig $config = null)
    {
        $this->db     = $db ?? db_connect();
        $this->config = $config ?? config('Investment');
    }

    /**
     * What the customer can withdraw right now, plus why not when they cannot.
     *
     * @return array{eligible: bool, reason: string, amount: float, count: int, rows: array<int, array<string, mixed>>}
     */
    public function available(int $userId, ?int $investmentId = null, ?string $asOf = null): array
    {
        $asOf = $asOf ?? date('Y-m-d');

        if ($investmentId !== null) {
            $check = $this->checkInvestment($userId, $investmentId, $asOf);

            if (! $check['ok']) {
                return $this->blocked($check['reason']);
            }
        }

        $rows = $this->availableRows($userId, $investmentId, $asOf, false);

        if ($rows === []) {
            return $this->blocked('No profit is available to withdraw yet.');
        }

        $amount = 0.0;

        foreach ($rows as $row) {
            $amount += (float) $row['profit_amount'];
        }

        return [
            'eligible' => true,
            'reason'   => '',
            'amount'   => round($amount, 2),
            'count'    => count($rows),
            'rows'     => $rows,
        ];
    }

    /**
     * Atomically claim every available profit row and record the payout.
     *
     * @return array{ok: bool, message: string, withdrawal_id: int|null, amount: float, count: int}
     */
    public function withdraw(int $userId, ?int $investmentId = null, ?int $processedBy = null, ?string $asOf = null): array
    {
        $asOf = $asOf ?? date('Y-m-d');

        $this->db->transBegin();

        try {
            // Section 5 checks 1 and 2: investment exists, is the customer's,
            // is not cancelled, and the payout period has been reached.
            if ($investmentId !== null) {
                $check = $this->checkInvestment($userId, $investmentId, $asOf);

                if (! $check['ok']) {
                    $this->db->transRollback();

                    return $this->failure($check['reason']);
                }
            }

            // Check 3: available profit balance (locked for the duration).
            $rows = $this->availableRows($userId, $investmentId, $asOf, true);

            if ($rows === []) {
                $this->db->transRollback();

                return $this->failure('No profit is available to withdraw yet.');
            }

            $ids    = array_map(static fn (array $row): int => (int) $row['id'], $rows);
            $amount = 0.0;

            foreach ($rows as $row) {
                $amount += (float) $row['profit_amount'];
            }

            $amount = round($amount, 2);
            $now    = date('Y-m-d H:i:s');

            $this->db->table('withdrawals')->insert([
                'user_id'       => $userId,
                'investment_id' => $investmentId,
                'amount'        => $amount,
                'profit_count'  => count($ids),
                'status'        => 'paid',
                'requested_at'  => $now,
                'processed_at'  => $now,
                'processed_by'  => $processedBy,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            $withdrawalId = (int) $this->db->insertID();

            // Check 4: conditional claim - only rows that are still available.
            $this->db->table('profits')
                ->whereIn('id', $ids)
                ->where('status', 'available')
                ->update([
                    'status'        => 'withdrawn',
                    'withdrawal_id' => $withdrawalId,
                    'updated_at'    => $now,
                ]);

            if ($this->db->affectedRows() !== count($ids)) {
                // Someone else claimed part of this balance in the meantime.
                $this->db->transRollback();

                return $this->failure('This profit was already withdrawn. Please refresh and try again.');
            }

            $this->db->transCommit();

            return [
                'ok'            => true,
                'message'       => 'Withdrawal of ₹' . number_format($amount, 2) . ' processed successfully.',
                'withdrawal_id' => $withdrawalId,
                'amount'        => $amount,
                'count'         => count($ids),
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }

    /**
     * @return array{ok: bool, reason: string}
     */
    protected function checkInvestment(int $userId, int $investmentId, string $asOf): array
    {
        $investment = $this->db->table('investments')
            ->where('id', $investmentId)
            ->get()
            ->getRowArray();

        if ($investment === null || (int) $investment['user_id'] !== $userId) {
            return ['ok' => false, 'reason' => 'Investment not found.'];
        }

        if ($investment['status'] === 'cancelled') {
            return ['ok' => false, 'reason' => 'This investment has been cancelled.'];
        }

        if ($this->config->strictActiveInvestmentForWithdrawal && $investment['status'] !== 'active') {
            return ['ok' => false, 'reason' => 'This investment is not active.'];
        }

        if ($investment['status'] === 'completed' && ! $this->config->strictActiveInvestmentForWithdrawal) {
            // Matured, but any profit it already earned stays payable.
            return ['ok' => true, 'reason' => ''];
        }

        return ['ok' => true, 'reason' => ''];
    }

    /**
     * Available profit rows, optionally row-locked for the claim.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function availableRows(int $userId, ?int $investmentId, string $asOf, bool $lock): array
    {
        // Profit earned on a cancelled investment is not payable, so it is
        // excluded here and everywhere else that reports an available balance.
        $sql = 'SELECT p.id, p.investment_id, p.period_key, p.period_start, p.period_end,'
            . ' p.due_date, p.profit_amount, p.status'
            . ' FROM ' . $this->db->prefixTable('profits') . ' AS p'
            . ' INNER JOIN ' . $this->db->prefixTable('investments') . ' AS i ON i.id = p.investment_id'
            . ' WHERE p.user_id = ? AND p.status = ? AND p.due_date <= ? AND i.status <> ?';

        $params = [$userId, 'available', $asOf, 'cancelled'];

        if ($investmentId !== null) {
            $sql .= ' AND p.investment_id = ?';
            $params[] = $investmentId;
        }

        $sql .= ' ORDER BY p.due_date ASC, p.id ASC';

        if ($lock && in_array($this->db->DBDriver, ['MySQLi', 'MySQL', 'Postgre', 'SQLSRV', 'OCI8'], true)) {
            $sql .= ' FOR UPDATE';
        }

        return $this->db->query($sql, $params)->getResultArray();
    }

    /**
     * @return array{eligible: false, reason: string, amount: float, count: int, rows: array<int, array<string, mixed>>}
     */
    protected function blocked(string $reason): array
    {
        return [
            'eligible' => false,
            'reason'   => $reason,
            'amount'   => 0.0,
            'count'    => 0,
            'rows'     => [],
        ];
    }

    /**
     * @return array{ok: false, message: string, withdrawal_id: null, amount: float, count: int}
     */
    protected function failure(string $message): array
    {
        return [
            'ok'            => false,
            'message'       => $message,
            'withdrawal_id' => null,
            'amount'        => 0.0,
            'count'         => 0,
        ];
    }
}
