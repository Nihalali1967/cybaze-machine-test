<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ---------------------------------------------------------------------------
// Authentication
// ---------------------------------------------------------------------------
$routes->get('/', 'Auth::index');
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');
$routes->get('logout', 'Auth::logout');

// ---------------------------------------------------------------------------
// Admin area
// ---------------------------------------------------------------------------
$routes->group('admin', ['filter' => ['auth', 'admin']], static function (RouteCollection $routes): void {
    $routes->get('/', 'Admin\Dashboard::index');

    // Investment packages
    $routes->get('packages', 'Admin\Packages::index');
    $routes->get('packages/create', 'Admin\Packages::create');
    $routes->post('packages', 'Admin\Packages::store');
    $routes->get('packages/(:num)/edit', 'Admin\Packages::edit/$1');
    $routes->post('packages/(:num)', 'Admin\Packages::update/$1');
    $routes->post('packages/(:num)/toggle', 'Admin\Packages::toggle/$1');

    // Customers
    $routes->get('customers', 'Admin\Customers::index');
    $routes->get('customers/create', 'Admin\Customers::create');
    $routes->post('customers', 'Admin\Customers::store');
    $routes->post('customers/(:num)/toggle', 'Admin\Customers::toggle/$1');

    // Investments
    $routes->get('investments', 'Admin\Investments::index');
    $routes->get('investments/create', 'Admin\Investments::create');
    $routes->post('investments', 'Admin\Investments::store');
    $routes->get('investments/(:num)', 'Admin\Investments::show/$1');
    $routes->post('investments/(:num)/status', 'Admin\Investments::status/$1');

    // Profit processing + history
    $routes->get('profits', 'Admin\Profits::index');
    $routes->get('profits/process', 'Admin\Profits::process');
    $routes->post('profits/process', 'Admin\Profits::run');
    $routes->get('profits/runs', 'Admin\Profits::runs');

    // Withdrawals ledger
    $routes->get('withdrawals', 'Admin\Withdrawals::index');
});

// ---------------------------------------------------------------------------
// Customer area
// ---------------------------------------------------------------------------
$routes->group('customer', ['filter' => ['auth', 'customer']], static function (RouteCollection $routes): void {
    $routes->get('/', 'Customer\Dashboard::index');

    // Browse active packages and invest
    $routes->get('packages', 'Customer\Investments::packages');
    $routes->get('invest/(:num)', 'Customer\Investments::form/$1');
    $routes->post('invest', 'Customer\Investments::store');

    // My investments
    $routes->get('investments', 'Customer\Investments::index');
    $routes->get('investments/(:num)', 'Customer\Investments::show/$1');

    // Profit history
    $routes->get('profits', 'Customer\Profits::index');

    // Withdrawals
    $routes->get('withdrawals', 'Customer\Withdrawals::index');
    $routes->post('withdrawals', 'Customer\Withdrawals::store');
});
