<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * One command for the whole demo dataset:
 *   php spark db:seed DemoSeeder
 *
 * Logins:
 *   admin@invest.com / admin123     (admin)
 *   john@invest.com  / customer123  (customer)
 *   mary@invest.com  / customer123  (customer)
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call('UserSeeder');
        $this->call('PackageSeeder');
        $this->call('InvestmentSeeder');
    }
}
