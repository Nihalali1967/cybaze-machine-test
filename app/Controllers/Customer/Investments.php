<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Libraries\WithdrawalService;
use App\Models\InvestmentModel;
use App\Models\PackageModel;
use App\Models\ProfitModel;

/**
 * Section 2 from the customer's side: pick an active package and invest.
 */
class Investments extends BaseController
{
    protected InvestmentModel $investments;

    public function __construct()
    {
        $this->investments = new InvestmentModel();
    }

    public function packages()
    {
        return view('customer/packages', [
            'title'    => 'Investment packages',
            'packages' => (new PackageModel())->activePackages(),
        ]);
    }

    public function form(int $packageId)
    {
        $package = (new PackageModel())->find($packageId);

        if ($package === null || $package['status'] !== PackageModel::STATUS_ACTIVE) {
            return redirect()->to('/customer/packages')->with('error', 'That package is not open for investment.');
        }

        return view('customer/invest', [
            'title'   => 'Invest in ' . $package['name'],
            'package' => $package,
            'today'   => date('Y-m-d'),
        ]);
    }

    public function store()
    {
        $rules = [
            'package_id'      => 'required|is_natural_no_zero',
            'amount'          => 'permit_empty|numeric|greater_than[0]',
            'investment_date' => 'permit_empty|valid_date',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $amount = $this->request->getPost('amount');

        $result = $this->investments->createInvestment(
            (int) session()->get('user_id'),
            (int) $this->request->getPost('package_id'),
            $amount === null || $amount === '' ? null : (float) $amount,
            (string) ($this->request->getPost('investment_date') ?? ''),
        );

        if (! $result['ok']) {
            return redirect()->back()->withInput()->with('errors', $result['errors']);
        }

        return redirect()->to('/customer/investments/' . $result['id'])
            ->with('success', 'Investment created. Your package terms are locked in.');
    }

    public function index()
    {
        return view('customer/investments', [
            'title'       => 'My investments',
            'investments' => $this->investments->forCustomer((int) session()->get('user_id')),
            'today'       => date('Y-m-d'),
        ]);
    }

    public function show(int $id)
    {
        $investment = $this->investments->find($id);
        $userId     = (int) session()->get('user_id');

        if ($investment === null || (int) $investment['user_id'] !== $userId) {
            return redirect()->to('/customer/investments')->with('error', 'Investment not found.');
        }

        return view('customer/investment_show', [
            'title'      => 'Investment #' . $id,
            'investment' => $investment,
            'profits'    => (new ProfitModel())->forInvestment($id),
            'available'  => (new WithdrawalService())->available($userId, $id),
            'today'      => date('Y-m-d'),
        ]);
    }
}
