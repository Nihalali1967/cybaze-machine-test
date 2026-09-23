<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Investment domain settings.
 */
class Investment extends BaseConfig
{
    /**
     * How `return_percentage` on a package should be read.
     *
     * 'annual'     : the rate is an annual rate and is prorated by the payout
     *                frequency. This is what the spec's own example implies:
     *                ₹50,000 @ 12% monthly => 50000 * 12 / 100 / 12 = ₹500,
     *                which is exactly the ₹500 profit shown in the sample
     *                profit history.
     * 'per_period' : the rate is paid in full on every payout period.
     *
     * Changing this only affects investments created afterwards - existing
     * investments carry their own `return_basis` snapshot.
     *
     * @var 'annual'|'per_period'
     */
    public string $defaultReturnBasis = 'annual';

    /**
     * Allow admins to backdate an investment. Needed to demo profit
     * processing without waiting a month for the first payout.
     */
    public bool $allowBackdatedInvestments = true;

    /**
     * Default maturity (number of payout periods) for new investments.
     * NULL means open ended - the investment stays active until an admin
     * completes or cancels it.
     */
    public ?int $defaultMaturityPeriods = null;

    /**
     * Section 5 of the spec asks to verify "whether the investment is active"
     * before a withdrawal.
     *
     * true  : strict reading - only an active investment may pay out.
     * false : a matured (completed) investment is blocked only if cancelled,
     *         because the profit it already earned must still be payable.
     */
    public bool $strictActiveInvestmentForWithdrawal = false;
}
