<?php

namespace App\Database\Seeds;

use App\Models\InvestmentModel;
use App\Models\PackageModel;
use App\Models\UserModel;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Seeds investments with backdated start dates so profit processing can be
 * demonstrated immediately instead of waiting months for the first payout.
 *
 * John - Gold, quarterly, started 9 months ago => 3 payouts are already due
 *        (3 x ₹2,500 = ₹7,500 available).
 * John - Premium, yearly, started today       => nothing due yet.
 * Mary - Silver, monthly, started 5 months ago => 5 payouts due
 *        (5 x ₹312.50 = ₹1,562.50), and matures once they are processed.
 */
class InvestmentSeeder extends Seeder
{
    public function run(): void
    {
        $users       = new UserModel();
        $packages    = new PackageModel();
        $investments = new InvestmentModel();

        $john = $users->findByEmail('john@invest.com');
        $mary = $users->findByEmail('mary@invest.com');

        if ($john === null || $mary === null) {
            CLI::write('Skipping investment seed: run UserSeeder first.', 'yellow');

            return;
        }

        $plan = [
            [
                'user'     => $john,
                'package'  => 'Gold',
                'amount'   => 50000.00,
                'date'     => date('Y-m-d', strtotime('-9 months')),
                'maturity' => null,
                'notes'    => 'Demo position: 3 quarterly payouts are already due.',
            ],
            [
                'user'     => $mary,
                'package'  => 'Silver',
                'amount'   => 25000.00,
                'date'     => date('Y-m-d', strtotime('-5 months')),
                'maturity' => 5,
                'notes'    => 'Demo position: matures after 5 monthly payouts.',
            ],
            [
                'user'     => $john,
                'package'  => 'Premium',
                'amount'   => 100000.00,
                'date'     => date('Y-m-d'),
                'maturity' => null,
                'notes'    => 'Demo position: yearly, first payout is not due yet.',
            ],
        ];

        foreach ($plan as $item) {
            $package = $packages->where('name', $item['package'])->first();

            if ($package === null) {
                continue;
            }

            $userId = (int) $item['user']['id'];

            $alreadySeeded = $investments
                ->where('user_id', $userId)
                ->where('package_id', (int) $package['id'])
                ->where('investment_date', $item['date'])
                ->countAllResults() > 0;

            if ($alreadySeeded) {
                continue;
            }

            $result = $investments->createInvestment(
                $userId,
                (int) $package['id'],
                (float) $item['amount'],
                $item['date'],
                $item['maturity'],
                $item['notes'],
            );

            if (! $result['ok']) {
                CLI::write('Could not seed investment for ' . $item['user']['name'] . ': ' . implode(' ', $result['errors']), 'red');

                continue;
            }

            CLI::write(sprintf(
                'Seeded investment #%d: %s -> %s ₹%s, next payout %s',
                $result['id'],
                $item['user']['name'],
                $package['name'],
                number_format((float) $item['amount'], 2),
                (string) $investments->find($result['id'])['next_profit_date'],
            ));
        }
    }
}
