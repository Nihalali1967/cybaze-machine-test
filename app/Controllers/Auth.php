<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function index()
    {
        if (session()->get('user_id') === null) {
            return redirect()->to('/login');
        }

        return $this->redirectToDashboard((string) session()->get('role'));
    }

    public function login()
    {
        if (session()->get('user_id') !== null) {
            return $this->redirectToDashboard((string) session()->get('role'));
        }

        return view('auth/login', ['title' => 'Sign in']);
    }

    public function attempt()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $users = new UserModel();
        $user  = $users->findByEmail((string) $this->request->getPost('email'));

        if ($user === null || ! password_verify((string) $this->request->getPost('password'), $user['password'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid email or password.');
        }

        if ($user['status'] !== 'active') {
            return redirect()->back()->withInput()->with('error', 'This account is inactive. Please contact the administrator.');
        }

        session()->set([
            'user_id' => (int) $user['id'],
            'role'    => $user['role'],
            'name'    => $user['name'],
            'email'   => $user['email'],
        ]);

        return $this->redirectToDashboard((string) $user['role']);
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login')->with('success', 'You have been signed out.');
    }

    protected function redirectToDashboard(string $role)
    {
        return $role === UserModel::ROLE_ADMIN
            ? redirect()->to('/admin')
            : redirect()->to('/customer');
    }
}
