<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\InvestmentModel;
use App\Models\ProfitModel;
use App\Models\ProfitRunModel;
use App\Models\WithdrawalModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $investments = new InvestmentModel();
        $profits     = new ProfitModel();

        $today = date('Y-m-d');

        return view('admin/dashboard', [
            'title'            => 'Dashboard',
            'summary'          => $investments->summary(),
            'dueCount'         => $investments->dueCount($today),
            'profitTotals'     => $profits->totals(),
            'withdrawalTotals' => (new WithdrawalModel())->totals(),
            'recentProfits'    => $profits->history([], 5),
            'recentRuns'       => (new ProfitRunModel())->recent(5),
            'today'            => $today,
        ]);
    }
}
