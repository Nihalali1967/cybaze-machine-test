<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\ProfitProcessor;
use App\Models\InvestmentModel;
use App\Models\ProfitModel;
use App\Models\ProfitRunModel;

/**
 * Sections 3 and 4: process profit, then review the ledger.
 */
class Profits extends BaseController
{
    protected ProfitModel $profits;
    protected ProfitProcessor $processor;

    public function __construct()
    {
        $this->profits   = new ProfitModel();
        $this->processor = new ProfitProcessor();
    }

    /**
     * Section 4: profit history.
     */
    public function index()
    {
        $filters = [
            'status'   => (string) ($this->request->getGet('status') ?? ''),
            'customer' => (string) ($this->request->getGet('customer') ?? ''),
            'package'  => (string) ($this->request->getGet('package') ?? ''),
            'from'     => (string) ($this->request->getGet('from') ?? ''),
            'to'       => (string) ($this->request->getGet('to') ?? ''),
        ];

        return view('admin/profits/index', [
            'title'   => 'Profit history',
            'profits' => $this->profits->history($filters),
            'filters' => $filters,
            'totals'  => $this->profits->totals(),
        ]);
    }

    /**
     * Section 3: the "Process/Update Profit" screen. Opening it shows a
     * read-only preview of what a run would create.
     */
    public function process()
    {
        $asOf = (string) ($this->request->getGet('as_of') ?: date('Y-m-d'));

        if (strtotime($asOf) === false) {
            $asOf = date('Y-m-d');
        }

        return view('admin/profits/process', [
            'title'    => 'Process profit',
            'asOf'     => $asOf,
            'preview'  => $this->processor->preview($asOf),
            'lastRun'  => (new ProfitRunModel())->lastCommittedRun(),
            'dueCount' => (new InvestmentModel())->dueCount($asOf),
        ]);
    }

    /**
     * The confirming click. Idempotent: running it again changes nothing.
     */
    public function run()
    {
        if (! $this->validate(['as_of' => 'required|valid_date'])) {
            return redirect()->back()->with('error', 'Please choose a valid processing date.');
        }

        $asOf   = (string) $this->request->getPost('as_of');
        $report = $this->processor->process($asOf, (int) session()->get('user_id'));

        $message = sprintf(
            'Processed up to %s: %d profit %s created worth %s, %d period(s) already existed.',
            fdate($asOf),
            $report['periods_created'],
            $report['periods_created'] === 1 ? 'period' : 'periods',
            inr($report['total_amount']),
            $report['periods_skipped'],
        );

        return redirect()->to('/admin/profits')
            ->with($report['periods_created'] > 0 ? 'success' : 'info', $message);
    }

    /**
     * Audit trail of every preview and every committed run.
     */
    public function runs()
    {
        return view('admin/profits/runs', [
            'title' => 'Processing runs',
            'runs'  => (new ProfitRunModel())->recent(50),
        ]);
    }
}
