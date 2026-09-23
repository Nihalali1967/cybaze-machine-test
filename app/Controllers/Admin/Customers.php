<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Customers extends BaseController
{
    protected UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    public function index()
    {
        return view('admin/customers/index', [
            'title'     => 'Customers',
            'customers' => $this->users->customersWithInvestments(),
        ]);
    }

    public function create()
    {
        return view('admin/customers/form', ['title' => 'New customer']);
    }

    public function store()
    {
        $rules = [
            'name'     => 'required|min_length[2]|max_length[100]',
            'email'    => 'required|valid_email|max_length[150]|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'phone'    => 'permit_empty|max_length[15]',
            'status'   => 'required|in_list[active,inactive]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $inserted = $this->users->insert([
            'name'     => (string) $this->request->getPost('name'),
            'email'    => (string) $this->request->getPost('email'),
            'password' => $this->users->hash((string) $this->request->getPost('password')),
            'phone'    => (string) $this->request->getPost('phone'),
            'role'     => UserModel::ROLE_CUSTOMER,
            'status'   => (string) $this->request->getPost('status'),
        ]);

        if ($inserted === false) {
            return redirect()->back()->withInput()->with('errors', $this->users->errors());
        }

        return redirect()->to('/admin/customers')->with('success', 'Customer created.');
    }

    public function toggle(int $id)
    {
        $customer = $this->users->find($id);

        if ($customer === null || $customer['role'] !== UserModel::ROLE_CUSTOMER) {
            return redirect()->to('/admin/customers')->with('error', 'Customer not found.');
        }

        $newStatus = $customer['status'] === 'active' ? 'inactive' : 'active';

        $this->users->update($id, ['status' => $newStatus]);

        return redirect()->to('/admin/customers')->with('success', $customer['name'] . ' marked ' . $newStatus . '.');
    }
}
