<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Libraries\WithdrawalService;
use App\Models\InvestmentModel;
use App\Models\ProfitModel;
use App\Models\WithdrawalModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $userId = (int) session()->get('user_id');

        $investments = new InvestmentModel();
        $profits     = new ProfitModel();

        return view('customer/dashboard', [
            'title'            => 'My dashboard',
            'summary'          => $investments->summaryForCustomer($userId),
            'profitTotals'     => $profits->totals($userId),
            'withdrawalTotals' => (new WithdrawalModel())->totals($userId),
            'investments'      => $investments->forCustomer($userId),
            'available'        => (new WithdrawalService())->available($userId),
            'today'            => date('Y-m-d'),
        ]);
    }
}
