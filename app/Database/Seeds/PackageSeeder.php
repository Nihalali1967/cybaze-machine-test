<?php

namespace App\Database\Seeds;

use App\Models\PackageModel;
use CodeIgniter\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $model = new PackageModel();

        // The four packages from the spec's example table, plus one inactive
        // package so the Active/Inactive rule is demonstrable.
        $packages = [
            [
                'name'              => 'Basic',
                'amount'            => 10000.00,
                'return_percentage' => 10.00,
                'withdrawal_type'   => 'weekly',
                'status'            => PackageModel::STATUS_ACTIVE,
            ],
            [
                'name'              => 'Silver',
                'amount'            => 25000.00,
                'return_percentage' => 15.00,
                'withdrawal_type'   => 'monthly',
                'status'            => PackageModel::STATUS_ACTIVE,
            ],
            [
                'name'              => 'Gold',
                'amount'            => 50000.00,
                'return_percentage' => 20.00,
                'withdrawal_type'   => 'quarterly',
                'status'            => PackageModel::STATUS_ACTIVE,
            ],
            [
                'name'              => 'Premium',
                'amount'            => 100000.00,
                'return_percentage' => 25.00,
                'withdrawal_type'   => 'yearly',
                'status'            => PackageModel::STATUS_ACTIVE,
            ],
            [
                'name'              => 'Starter Flex',
                'amount'            => 5000.00,
                'return_percentage' => 8.00,
                'withdrawal_type'   => 'monthly',
                'status'            => PackageModel::STATUS_INACTIVE,
            ],
        ];

        foreach ($packages as $package) {
            if ($model->where('name', $package['name'])->countAllResults() > 0) {
                continue;
            }

            $model->insert($package);
        }
    }
}
