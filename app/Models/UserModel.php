<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    public const ROLE_ADMIN    = 'admin';
    public const ROLE_CUSTOMER = 'customer';

    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['name', 'email', 'password', 'role', 'phone', 'status'];

    protected $validationRules = [
        'name'   => 'required|min_length[2]|max_length[100]',
        'email'  => 'required|valid_email|max_length[150]',
        'role'   => 'required|in_list[admin,customer]',
        'status' => 'required|in_list[active,inactive]',
        'phone'  => 'permit_empty|max_length[15]',
    ];

    protected $validationMessages = [
        'email' => [
            'valid_email' => 'Please enter a valid email address.',
        ],
    ];

    public function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function customers(): array
    {
        return $this->where('role', self::ROLE_CUSTOMER)->orderBy('name', 'ASC')->findAll();
    }

    /**
     * id => "Name (email)" for select boxes.
     *
     * @return array<int, string>
     */
    public function customerOptions(): array
    {
        $options = [];

        foreach ($this->customers() as $customer) {
            $options[(int) $customer['id']] = $customer['name'] . ' (' . $customer['email'] . ')';
        }

        return $options;
    }

    public function customersWithInvestments(): array
    {
        return $this->db->table('users u')
            ->select('u.*, COUNT(i.id) AS investment_count, COALESCE(SUM(CASE WHEN i.status = "active" THEN i.investment_amount ELSE 0 END), 0) AS active_amount')
            ->join('investments i', 'i.user_id = u.id', 'left')
            ->where('u.role', self::ROLE_CUSTOMER)
            ->groupBy('u.id')
            ->orderBy('u.name', 'ASC')
            ->get()
            ->getResultArray();
    }
}
