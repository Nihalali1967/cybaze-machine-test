<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/**
 * Read model for the profit ledger (section 4 of the spec).
 */
class ProfitModel extends Model
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $table         = 'profits';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'investment_id',
        'user_id',
        'period_key',
        'period_start',
        'period_end',
        'due_date',
        'investment_amount',
        'profit_amount',
        'status',
        'withdrawal_id',
        'processed_at',
    ];

    /**
     * Profit history rows: date, customer, investment, profit, status.
     *
     * @return array<int, array<string, mixed>>
     */
    public function history(array $filters = [], int $limit = 0): array
    {
        $builder = $this->historyBuilder($filters);

        if ($limit > 0) {
            $builder->limit($limit);
        }

        return $builder->get()->getResultArray();
    }

    protected function historyBuilder(array $filters): BaseBuilder
    {
        $builder = $this->db->table('profits p')
            ->select('p.*, u.name AS customer_name, u.email AS customer_email, i.package_name, i.withdrawal_type')
            ->join('users u', 'u.id = p.user_id')
            ->join('investments i', 'i.id = p.investment_id')
            ->orderBy('p.due_date', 'DESC')
            ->orderBy('p.id', 'DESC');

        if (! empty($filters['user_id'])) {
            $builder->where('p.user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['investment_id'])) {
            $builder->where('p.investment_id', (int) $filters['investment_id']);
        }

        if (! empty($filters['status'])) {
            $builder->where('p.status', $filters['status']);
        }

        if (! empty($filters['from'])) {
            $builder->where('p.due_date >=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $builder->where('p.due_date <=', $filters['to']);
        }

        if (! empty($filters['customer'])) {
            $builder->groupStart()
                ->like('u.name', $filters['customer'])
                ->orLike('u.email', $filters['customer'])
                ->groupEnd();
        }

        if (! empty($filters['package'])) {
            $builder->like('i.package_name', $filters['package']);
        }

        return $builder;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function historyCount(array $filters = []): int
    {
        return $this->historyBuilder($filters)->countAllResults();
    }

    /**
     * Available / withdrawn / total profit, optionally for one customer.
     *
     * Profit belonging to a cancelled investment is deliberately excluded from
     * `available` so this figure always agrees with
     * WithdrawalService::available(), which is what the withdraw button uses.
     *
     * @return array{available: float, withdrawn: float, total: float, rows: int, available_count: int}
     */
    public function totals(?int $userId = null): array
    {
        $builder = $this->db->table('profits p')
            ->join('investments i', 'i.id = p.investment_id')
            ->select(
                "COALESCE(SUM(CASE WHEN p.status = 'available' AND i.status <> 'cancelled' THEN p.profit_amount ELSE 0 END), 0) AS available,
                 COALESCE(SUM(CASE WHEN p.status = 'withdrawn' THEN p.profit_amount ELSE 0 END), 0) AS withdrawn,
                 COALESCE(SUM(p.profit_amount), 0) AS total,
                 COUNT(*) AS row_count,
                 COALESCE(SUM(CASE WHEN p.status = 'available' AND i.status <> 'cancelled' THEN 1 ELSE 0 END), 0) AS available_count",
                false,
            );

        if ($userId !== null) {
            $builder->where('p.user_id', $userId);
        }

        $row = $builder->get()->getRowArray();

        return [
            'available'       => (float) ($row['available'] ?? 0),
            'withdrawn'       => (float) ($row['withdrawn'] ?? 0),
            'total'           => (float) ($row['total'] ?? 0),
            'rows'            => (int) ($row['row_count'] ?? 0),
            'available_count' => (int) ($row['available_count'] ?? 0),
        ];
    }

    /**
     * Profit rows belonging to one investment, newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forInvestment(int $investmentId, int $limit = 0): array
    {
        $builder = $this->where('investment_id', $investmentId)
            ->orderBy('due_date', 'DESC')
            ->orderBy('id', 'DESC');

        if ($limit > 0) {
            $builder = $builder->limit($limit);
        }

        return $builder->findAll();
    }
}
