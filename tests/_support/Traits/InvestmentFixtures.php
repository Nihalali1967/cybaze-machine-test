<?php

namespace Tests\Support\Traits;

use App\Models\InvestmentModel;
use App\Models\PackageModel;
use App\Models\UserModel;
use App\Models\UserModel as Users;

/**
 * Fixture helpers shared by the profit processing and withdrawal tests.
 */
trait InvestmentFixtures
{
    protected int $customerId = 0;

    protected function today(): string
    {
        return date('Y-m-d');
    }

    protected function monthsAgo(int $months): string
    {
        return date('Y-m-d', strtotime('-' . $months . ' months'));
    }

    protected function createCustomer(string $email = 'customer@test.local'): int
    {
        $users = new UserModel();

        $users->insert([
            'name'     => 'Test Customer',
            'email'    => $email,
            'password' => $users->hash('secret123'),
            'role'     => Users::ROLE_CUSTOMER,
            'status'   => 'active',
        ]);

        return (int) $users->getInsertID();
    }

    /**
     * @return array<string, mixed>
     */
    protected function createPackage(
        string $name,
        float $amount,
        float $rate,
        string $type,
        string $status = 'active',
    ): array {
        $packages = new PackageModel();

        $packages->insert([
            'name'              => $name,
            'amount'            => $amount,
            'return_percentage' => $rate,
            'withdrawal_type'   => $type,
            'status'            => $status,
        ]);

        return $packages->find((int) $packages->getInsertID());
    }

    /**
     * @param array<string, mixed> $package
     *
     * @return array<string, mixed>
     */
    protected function createInvestment(
        array $package,
        string $date,
        ?int $maturity = null,
        ?int $customerId = null,
        ?float $amount = null,
    ): array {
        $investments = new InvestmentModel();

        $result = $investments->createInvestment(
            $customerId ?? $this->customerId,
            (int) $package['id'],
            $amount ?? (float) $package['amount'],
            $date,
            $maturity,
        );

        $this->assertTrue($result['ok'], 'Investment was not created: ' . implode(' ', $result['errors']));

        return $investments->find((int) $result['id']);
    }
}
