<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $model = new UserModel();

        $users = [
            [
                'name'     => 'Admin User',
                'email'    => 'admin@invest.com',
                'password' => 'admin123',
                'role'     => UserModel::ROLE_ADMIN,
                'phone'    => '9800000001',
            ],
            [
                'name'     => 'John Carter',
                'email'    => 'john@invest.com',
                'password' => 'customer123',
                'role'     => UserModel::ROLE_CUSTOMER,
                'phone'    => '9800000002',
            ],
            [
                'name'     => 'Mary Fernandes',
                'email'    => 'mary@invest.com',
                'password' => 'customer123',
                'role'     => UserModel::ROLE_CUSTOMER,
                'phone'    => '9800000003',
            ],
        ];

        foreach ($users as $user) {
            if ($model->where('email', $user['email'])->countAllResults() > 0) {
                continue;
            }

            $user['password'] = $model->hash($user['password']);
            $user['status']   = 'active';

            $model->insert($user);
        }
    }
}
