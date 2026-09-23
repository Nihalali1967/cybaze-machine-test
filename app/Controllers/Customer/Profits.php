<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\ProfitModel;

class Profits extends BaseController
{
    public function index()
    {
        $userId  = (int) session()->get('user_id');
        $profits = new ProfitModel();

        $filters = [
            'user_id' => $userId,
            'status'  => (string) ($this->request->getGet('status') ?? ''),
            'from'    => (string) ($this->request->getGet('from') ?? ''),
            'to'      => (string) ($this->request->getGet('to') ?? ''),
        ];

        return view('customer/profits', [
            'title'   => 'My profit history',
            'profits' => $profits->history($filters),
            'totals'  => $profits->totals($userId),
            'filters' => $filters,
        ]);
    }
}
