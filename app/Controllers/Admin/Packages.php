<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PackageModel;

/**
 * Section 1: predefined investment packages.
 */
class Packages extends BaseController
{
    protected PackageModel $packages;

    public function __construct()
    {
        $this->packages = new PackageModel();
    }

    public function index()
    {
        return view('admin/packages/index', [
            'title'    => 'Investment packages',
            'packages' => $this->packages->withUsage(),
        ]);
    }

    public function create()
    {
        return view('admin/packages/form', [
            'title'   => 'New package',
            'package' => null,
        ]);
    }

    public function store()
    {
        $rules = $this->rules();

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $inserted = $this->packages->insert($this->payload());

        if ($inserted === false) {
            return redirect()->back()->withInput()->with('errors', $this->packages->errors());
        }

        return redirect()->to('/admin/packages')->with('success', 'Package "' . $this->request->getPost('name') . '" created.');
    }

    public function edit(int $id)
    {
        $package = $this->packages->find($id);

        if ($package === null) {
            return redirect()->to('/admin/packages')->with('error', 'Package not found.');
        }

        return view('admin/packages/form', [
            'title'   => 'Edit ' . $package['name'],
            'package' => $package,
        ]);
    }

    public function update(int $id)
    {
        $package = $this->packages->find($id);

        if ($package === null) {
            return redirect()->to('/admin/packages')->with('error', 'Package not found.');
        }

        if (! $this->validate($this->rules($id))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($this->packages->update($id, $this->payload()) === false) {
            return redirect()->back()->withInput()->with('errors', $this->packages->errors());
        }

        return redirect()->to('/admin/packages')->with(
            'success',
            'Package updated. Existing investments keep the terms they were created with.',
        );
    }

    /**
     * Active / Inactive toggle.
     */
    public function toggle(int $id)
    {
        $package = $this->packages->find($id);

        if ($package === null) {
            return redirect()->to('/admin/packages')->with('error', 'Package not found.');
        }

        $newStatus = $package['status'] === PackageModel::STATUS_ACTIVE
            ? PackageModel::STATUS_INACTIVE
            : PackageModel::STATUS_ACTIVE;

        $this->packages->update($id, ['status' => $newStatus]);

        $note = $newStatus === PackageModel::STATUS_INACTIVE
            ? ' New investments are blocked; existing investments are unaffected.'
            : '';

        return redirect()->to('/admin/packages')->with('success', 'Package marked ' . $newStatus . '.' . $note);
    }

    /**
     * @return array<string, string>
     */
    protected function rules(?int $id = null): array
    {
        return [
            'name'              => 'required|min_length[2]|max_length[100]|is_unique[packages.name,id,' . ($id ?? 0) . ']',
            'amount'            => 'required|numeric|greater_than[0]',
            'return_percentage' => 'required|numeric|greater_than[0]|less_than_equal_to[100]',
            'withdrawal_type'   => 'required|in_list[weekly,monthly,quarterly,yearly]',
            'status'            => 'required|in_list[active,inactive]',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return [
            'name'              => (string) $this->request->getPost('name'),
            'amount'            => (float) $this->request->getPost('amount'),
            'return_percentage' => (float) $this->request->getPost('return_percentage'),
            'withdrawal_type'   => (string) $this->request->getPost('withdrawal_type'),
            'status'            => (string) $this->request->getPost('status'),
        ];
    }
}
