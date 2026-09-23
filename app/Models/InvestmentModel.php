<?php

namespace App\Models;

use App\Libraries\ProfitCalculator;
use CodeIgniter\Model;
use Config\Investment as InvestmentConfig;

/**
 * Investments own their own copy of the package terms.
 *
 * Nothing in this model ever reads `packages.return_percentage` after the
 * investment is created, which is what makes the spec's requirement hold:
 * editing a package cannot change an existing investment.
 */
class InvestmentModel extends Model
{
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table         = 'investments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'user_id',
        'package_id',
        // frozen package terms
        'package_name',
        'package_amount',
        'return_percentage',
        'withdrawal_type',
        'return_basis',
        'period_profit_amount',
        // position
        'investment_amount',
        'investment_date',
        'next_profit_date',
        'periods_paid',
        'maturity_periods',
        'status',
        'notes',
    ];

    protected $validationRules = [
        'user_id'            => 'required|is_natural_no_zero',
        'package_name'       => 'required|max_length[100]',
        'investment_amount'  => 'required|numeric|greater_than[0]',
        'return_percentage'  => 'required|numeric|greater_than[0]',
        'withdrawal_type'    => 'required|in_list[weekly,monthly,quarterly,yearly]',
        'investment_date'    => 'required|valid_date',
        'next_profit_date'   => 'required|valid_date',
        'status'             => 'required|in_list[active,completed,cancelled]',
    ];

    /**
     * Copy the package terms onto the investment, frozen forever.
     *
     * The per-period profit is computed once here rather than on every payout,
     * so every period of an investment pays an identical amount and a later
     * package edit cannot introduce rounding drift.
     *
     * @param array<string, mixed> $package
     *
     * @return array<string, mixed>
     */
    public function snapshotFromPackage(
        array $package,
        float $amount,
        string $investmentDate,
        ?int $maturityPeriods = null,
        ?string $basis = null,
    ): array {
        $config = config('Investment');

        if (! $config instanceof InvestmentConfig) {
            $config = new InvestmentConfig();
        }

        $basis = $basis ?? $config->defaultReturnBasis;
        $type  = (string) $package['withdrawal_type'];

        return [
            'package_id'           => (int) $package['id'],
            'package_name'         => $package['name'],
            'package_amount'       => (float) $package['amount'],
            'return_percentage'    => (float) $package['return_percentage'],
            'withdrawal_type'      => $type,
            'return_basis'         => $basis,
            'period_profit_amount' => ProfitCalculator::periodProfit(
                $amount,
                (float) $package['return_percentage'],
                $type,
                $basis,
            ),
            'investment_amount'    => $amount,
            'investment_date'      => $investmentDate,
            'next_profit_date'     => ProfitCalculator::nextDate($type, $investmentDate),
            'periods_paid'         => 0,
            'maturity_periods'     => $maturityPeriods,
            'status'               => self::STATUS_ACTIVE,
        ];
    }

    /**
     * Validate the request, then create the investment with frozen terms.
     * Shared by the admin "assign investment" form and the customer portal.
     *
     * @return array{ok: bool, id: int|null, errors: array<int, string>}
     */
    public function createInvestment(
        int $userId,
        int $packageId,
        ?float $amount,
        ?string $investmentDate = null,
        ?int $maturityPeriods = null,
        ?string $notes = null,
    ): array {
        $config = config('Investment');

        $package = $this->db->table('packages')->where('id', $packageId)->get()->getRowArray();

        if ($package === null) {
            return $this->failed('The selected investment package does not exist.');
        }

        if ($package['status'] !== 'active') {
            return $this->failed('Only an active package can be invested in.');
        }

        $amount     = $amount ?? (float) $package['amount'];
        $date       = $investmentDate !== null && $investmentDate !== '' ? $investmentDate : date('Y-m-d');
        $today      = date('Y-m-d');
        $errors     = [];

        if ($amount <= 0) {
            $errors[] = 'The investment amount must be greater than zero.';
        }

        if ($amount < (float) $package['amount']) {
            $errors[] = 'The investment amount must be at least the package amount of ₹' . number_format((float) $package['amount'], 2) . '.';
        }

        if (strtotime($date) === false) {
            $errors[] = 'The investment date is not a valid date.';
        } elseif ($date > $today) {
            $errors[] = 'The investment date cannot be in the future.';
        } elseif ($date < $today && ! $config->allowBackdatedInvestments) {
            $errors[] = 'The investment date must be today.';
        }

        if ($errors !== []) {
            return ['ok' => false, 'id' => null, 'errors' => $errors];
        }

        $row            = $this->snapshotFromPackage($package, $amount, $date, $maturityPeriods);
        $row['user_id'] = $userId;

        if ($notes !== null && $notes !== '') {
            $row['notes'] = $notes;
        }

        if ($this->insert($row) === false) {
            return ['ok' => false, 'id' => null, 'errors' => array_values($this->errors())];
        }

        return ['ok' => true, 'id' => (int) $this->getInsertID(), 'errors' => []];
    }

    /**
     * @return array{ok: false, id: null, errors: array<int, string>}
     */
    protected function failed(string $message): array
    {
        return ['ok' => false, 'id' => null, 'errors' => [$message]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function adminList(array $filters = []): array
    {
        $builder = $this->db->table('investments i')
            ->select('i.*, u.name AS customer_name, u.email AS customer_email')
            ->join('users u', 'u.id = i.user_id')
            ->orderBy('i.id', 'DESC');

        if (! empty($filters['status'])) {
            $builder->where('i.status', $filters['status']);
        }

        if (! empty($filters['user_id'])) {
            $builder->where('i.user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['search'])) {
            $builder->groupStart()
                ->like('u.name', $filters['search'])
                ->orLike('u.email', $filters['search'])
                ->orLike('i.package_name', $filters['search'])
                ->groupEnd();
        }

        return $builder->get()->getResultArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forCustomer(int $userId): array
    {
        return $this->where('user_id', $userId)->orderBy('id', 'DESC')->findAll();
    }

    public function findDetailed(int $id): ?array
    {
        $row = $this->db->table('investments i')
            ->select('i.*, u.name AS customer_name, u.email AS customer_email')
            ->join('users u', 'u.id = i.user_id')
            ->where('i.id', $id)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Dashboard figures for one customer.
     *
     * @return array<string, mixed>
     */
    public function summaryForCustomer(int $userId): array
    {
        $row = $this->db->table('investments')
            ->select('COUNT(*) AS total_count,
                COALESCE(SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END), 0) AS active_count,
                COALESCE(SUM(investment_amount), 0) AS invested,
                COALESCE(SUM(CASE WHEN status = "active" THEN investment_amount ELSE 0 END), 0) AS active_invested,
                MIN(CASE WHEN status = "active" THEN next_profit_date END) AS next_profit_date', false)
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        $row['invested']         = (float) ($row['invested'] ?? 0);
        $row['active_invested']  = (float) ($row['active_invested'] ?? 0);
        $row['total_count']      = (int) ($row['total_count'] ?? 0);
        $row['active_count']     = (int) ($row['active_count'] ?? 0);

        return $row;
    }

    /**
     * Dashboard figures for the admin.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $row = $this->db->table('investments')
            ->select('COUNT(*) AS total_count,
                COALESCE(SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END), 0) AS active_count,
                COALESCE(SUM(investment_amount), 0) AS invested,
                COALESCE(SUM(CASE WHEN status = "active" THEN investment_amount ELSE 0 END), 0) AS active_invested', false)
            ->get()
            ->getRowArray();

        $row['invested']        = (float) ($row['invested'] ?? 0);
        $row['active_invested'] = (float) ($row['active_invested'] ?? 0);
        $row['total_count']     = (int) ($row['total_count'] ?? 0);
        $row['active_count']    = (int) ($row['active_count'] ?? 0);

        return $row;
    }

    public function dueCount(string $asOf): int
    {
        return $this->where('status', self::STATUS_ACTIVE)
            ->where('next_profit_date <=', $asOf)
            ->countAllResults();
    }
}
