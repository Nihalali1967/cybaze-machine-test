<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\WithdrawalService;
use App\Models\InvestmentModel;
use App\Models\PackageModel;
use App\Models\ProfitModel;
use App\Models\UserModel;

/**
 * Section 2: assign a customer into a package.
 */
class Investments extends BaseController
{
    protected InvestmentModel $investments;

    public function __construct()
    {
        $this->investments = new InvestmentModel();
    }

    public function index()
    {
        $filters = [
            'status'  => (string) ($this->request->getGet('status') ?? ''),
            'user_id' => (string) ($this->request->getGet('user_id') ?? ''),
            'search'  => (string) ($this->request->getGet('search') ?? ''),
        ];

        return view('admin/investments/index', [
            'title'       => 'Investments',
            'investments' => $this->investments->adminList($filters),
            'filters'     => $filters,
            'customers'   => (new UserModel())->customerOptions(),
            'statuses'    => [
                InvestmentModel::STATUS_ACTIVE    => 'Active',
                InvestmentModel::STATUS_COMPLETED => 'Completed',
                InvestmentModel::STATUS_CANCELLED => 'Cancelled',
            ],
            'today' => date('Y-m-d'),
        ]);
    }

    public function create()
    {
        return view('admin/investments/form', [
            'title'     => 'Assign an investment',
            'customers' => (new UserModel())->customerOptions(),
            'packages'  => (new PackageModel())->activePackages(),
            'today'     => date('Y-m-d'),
        ]);
    }

    public function store()
    {
        $rules = [
            'user_id'          => 'required|is_natural_no_zero',
            'package_id'       => 'required|is_natural_no_zero',
            'amount'           => 'permit_empty|numeric|greater_than[0]',
            'investment_date'  => 'permit_empty|valid_date',
            'maturity_periods' => 'permit_empty|is_natural_no_zero',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $amount   = $this->request->getPost('amount');
        $maturity = $this->request->getPost('maturity_periods');

        $result = $this->investments->createInvestment(
            (int) $this->request->getPost('user_id'),
            (int) $this->request->getPost('package_id'),
            $amount === null || $amount === '' ? null : (float) $amount,
            (string) ($this->request->getPost('investment_date') ?? ''),
            $maturity === null || $maturity === '' ? null : (int) $maturity,
            (string) ($this->request->getPost('notes') ?? ''),
        );

        if (! $result['ok']) {
            return redirect()->back()->withInput()->with('errors', $result['errors']);
        }

        return redirect()->to('/admin/investments/' . $result['id'])
            ->with('success', 'Investment created. The package terms were frozen onto it.');
    }

    public function show(int $id)
    {
        $investment = $this->investments->findDetailed($id);

        if ($investment === null) {
            return redirect()->to('/admin/investments')->with('error', 'Investment not found.');
        }

        return view('admin/investments/show', [
            'title'      => 'Investment #' . $id,
            'investment' => $investment,
            'profits'    => (new ProfitModel())->forInvestment($id),
            'available'  => (new WithdrawalService())->available((int) $investment['user_id'], $id),
            'today'      => date('Y-m-d'),
        ]);
    }

    public function status(int $id)
    {
        $investment = $this->investments->find($id);

        if ($investment === null) {
            return redirect()->to('/admin/investments')->with('error', 'Investment not found.');
        }

        $status = (string) $this->request->getPost('status');

        if (! in_array($status, [
            InvestmentModel::STATUS_ACTIVE,
            InvestmentModel::STATUS_COMPLETED,
            InvestmentModel::STATUS_CANCELLED,
        ], true)) {
            return redirect()->back()->with('error', 'Invalid investment status.');
        }

        $this->investments->update($id, ['status' => $status]);

        return redirect()->back()->with('success', 'Investment marked as ' . $status . '.');
    }
}
