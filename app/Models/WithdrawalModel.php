<?php

namespace App\Models;

use CodeIgniter\Model;

class WithdrawalModel extends Model
{
    public const STATUS_PAID = 'paid';

    protected $table         = 'withdrawals';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'user_id',
        'investment_id',
        'amount',
        'profit_count',
        'status',
        'requested_at',
        'processed_at',
        'processed_by',
        'admin_remarks',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function ledger(array $filters = []): array
    {
        $builder = $this->db->table('withdrawals w')
            ->select('w.*, u.name AS customer_name, u.email AS customer_email, i.package_name')
            ->join('users u', 'u.id = w.user_id')
            ->join('investments i', 'i.id = w.investment_id', 'left')
            ->orderBy('w.id', 'DESC');

        if (! empty($filters['user_id'])) {
            $builder->where('w.user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['status'])) {
            $builder->where('w.status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $builder->groupStart()
                ->like('u.name', $filters['search'])
                ->orLike('u.email', $filters['search'])
                ->groupEnd();
        }

        return $builder->get()->getResultArray();
    }

    /**
     * @return array{count: int, amount: float}
     */
    public function totals(?int $userId = null): array
    {
        $builder = $this->db->table('withdrawals')
            ->select('COUNT(*) AS row_count, COALESCE(SUM(amount), 0) AS amount', false)
            ->where('status', self::STATUS_PAID);

        if ($userId !== null) {
            $builder->where('user_id', $userId);
        }

        $row = $builder->get()->getRowArray();

        return [
            'count'  => (int) ($row['row_count'] ?? 0),
            'amount' => (float) ($row['amount'] ?? 0),
        ];
    }
}
