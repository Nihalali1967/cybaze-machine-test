<?php

namespace App\Models;

use CodeIgniter\Model;

class PackageModel extends Model
{
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $table         = 'packages';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['name', 'amount', 'return_percentage', 'withdrawal_type', 'status'];

    protected $validationRules = [
        'name'              => 'required|min_length[2]|max_length[100]',
        'amount'            => 'required|numeric|greater_than[0]',
        'return_percentage' => 'required|numeric|greater_than[0]|less_than_equal_to[100]',
        'withdrawal_type'   => 'required|in_list[weekly,monthly,quarterly,yearly]',
        'status'            => 'required|in_list[active,inactive]',
    ];

    protected $validationMessages = [
        'amount' => [
            'greater_than' => 'The package amount must be greater than zero.',
        ],
        'return_percentage' => [
            'greater_than'         => 'The return percentage must be greater than zero.',
            'less_than_equal_to'   => 'The return percentage cannot exceed 100.',
        ],
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activePackages(): array
    {
        return $this->where('status', self::STATUS_ACTIVE)->orderBy('amount', 'ASC')->findAll();
    }

    /**
     * id => "Gold - ₹50,000.00 (20% p.a., Quarterly)"
     *
     * @return array<int, string>
     */
    public function options(bool $activeOnly = true): array
    {
        $rows    = $activeOnly ? $this->activePackages() : $this->orderBy('amount', 'ASC')->findAll();
        $options = [];

        foreach ($rows as $row) {
            $options[(int) $row['id']] = sprintf(
                '%s - ₹%s (%s, %s)',
                $row['name'],
                number_format((float) $row['amount'], 2),
                rtrim(rtrim(number_format((float) $row['return_percentage'], 2), '0'), '.') . '%',
                ucfirst((string) $row['withdrawal_type']),
            );
        }

        return $options;
    }

    /**
     * Packages plus how many investments already reference them, so an admin
     * can see why deleting is not offered for used packages.
     */
    public function withUsage(): array
    {
        return $this->db->table('packages p')
            ->select('p.*, COUNT(i.id) AS investment_count, COALESCE(SUM(CASE WHEN i.status = "active" THEN 1 ELSE 0 END), 0) AS active_count')
            ->join('investments i', 'i.package_id = p.id', 'left')
            ->groupBy('p.id')
            ->orderBy('p.amount', 'ASC')
            ->get()
            ->getResultArray();
    }
}
