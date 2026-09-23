<?php

namespace App\Models;

use CodeIgniter\Model;

class ProfitRunModel extends Model
{
    protected $table         = 'profit_runs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'run_by',
        'as_of',
        'investments_scanned',
        'periods_created',
        'periods_skipped',
        'total_amount',
        'is_dry_run',
        'run_at',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recent(int $limit = 25): array
    {
        return $this->db->table('profit_runs r')
            ->select('r.*, u.name AS run_by_name')
            ->join('users u', 'u.id = r.run_by', 'left')
            ->orderBy('r.id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function lastCommittedRun(): ?array
    {
        $row = $this->where('is_dry_run', 0)->orderBy('id', 'DESC')->first();

        return $row ?: null;
    }
}
