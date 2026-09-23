<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProfitModel;
use App\Models\WithdrawalModel;

class Withdrawals extends BaseController
{
    public function index()
    {
        $filters = [
            'status' => (string) ($this->request->getGet('status') ?? ''),
            'search' => (string) ($this->request->getGet('search') ?? ''),
        ];

        $withdrawals = new WithdrawalModel();

        return view('admin/withdrawals/index', [
            'title'       => 'Withdrawal ledger',
            'withdrawals' => $withdrawals->ledger($filters),
            'filters'     => $filters,
            'totals'      => $withdrawals->totals(),
            'outstanding' => (new ProfitModel())->totals()['available'],
        ]);
    }
}
