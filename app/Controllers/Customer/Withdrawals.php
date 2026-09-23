<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Libraries\WithdrawalService;
use App\Models\InvestmentModel;
use App\Models\WithdrawalModel;

/**
 * Section 5: withdraw available profit.
 */
class Withdrawals extends BaseController
{
    public function index()
    {
        $userId  = (int) session()->get('user_id');
        $service = new WithdrawalService();

        $investments = (new InvestmentModel())->forCustomer($userId);

        // Per-investment eligibility, so the customer can see exactly which
        // position is payable and why.
        $breakdown = [];

        foreach ($investments as $investment) {
            $breakdown[(int) $investment['id']] = $service->available($userId, (int) $investment['id']);
        }

        $withdrawals = new WithdrawalModel();

        return view('customer/withdrawals', [
            'title'       => 'Withdraw profit',
            'available'   => $service->available($userId),
            'investments' => $investments,
            'breakdown'   => $breakdown,
            'withdrawals' => $withdrawals->ledger(['user_id' => $userId]),
            'totals'      => $withdrawals->totals($userId),
        ]);
    }

    public function store()
    {
        $userId = (int) session()->get('user_id');
        $target = (string) $this->request->getPost('investment_id');

        $investmentId = ($target === '' || $target === 'all') ? null : (int) $target;

        $result = (new WithdrawalService())->withdraw($userId, $investmentId);

        return $result['ok']
            ? redirect()->to('/customer/withdrawals')->with('success', $result['message'])
            : redirect()->to('/customer/withdrawals')->with('error', $result['message']);
    }
}
