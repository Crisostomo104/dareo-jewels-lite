<?php
declare(strict_types=1);

namespace App;

final class Router
{
    private const ROUTES = [
        ['GET',  '/',                              'PublicController@home'],
        ['GET',  '/collection',                    'PublicController@catalog'],
        ['GET',  '/p/(\d+)',                       'PublicController@product'],
        ['GET',  '/about',                         'PublicController@about'],
        ['GET',  '/contact',                       'PublicController@contact'],
        ['GET',  '/setup',                         'AdminController@setupForm'],
        ['POST', '/setup',                         'AdminController@setup'],
        ['GET',  '/admin/login',                   'AdminController@loginForm'],
        ['POST', '/admin/login',                   'AdminController@login'],
        ['POST', '/admin/logout',                  'AdminController@logout'],
        ['GET',  '/admin',                         'AdminController@dashboard'],
        ['GET',  '/admin/calculator',              'AdminController@calculator'],
        ['POST', '/admin/calculator',              'AdminController@calculate'],
        ['GET',  '/admin/products',                'AdminController@products'],
        ['GET',  '/admin/products/new',            'AdminController@productForm'],
        ['GET',  '/admin/products/(\d+)/edit',     'AdminController@productForm'],
        ['POST', '/admin/products',                'AdminController@saveProduct'],
        ['POST', '/admin/products/(\d+)/archive',  'AdminController@archiveProduct'],
        ['GET',  '/admin/sales',                   'AdminController@sales'],
        ['GET',  '/admin/sales/new',               'AdminController@saleForm'],
        ['POST', '/admin/sales',                   'AdminController@createSale'],
        ['POST', '/admin/sales/(\d+)/payment',     'AdminController@recordPayment'],
        ['POST', '/admin/sales/(\d+)/receive',     'AdminController@receiveSale'],
        ['POST', '/admin/sales/(\d+)/payment/(\d+)/reverse', 'AdminController@reversePayment'],
        ['POST', '/admin/sales/(\d+)/cancel',      'AdminController@cancelSale'],
        ['GET',  '/admin/expenses',                'AdminController@expenses'],
        ['POST', '/admin/expenses',                'AdminController@createExpense'],
        ['POST', '/admin/expenses/(\d+)/delete',   'AdminController@deleteExpense'],
        ['GET',  '/admin/reports',                 'AdminController@reports'],
        ['GET',  '/admin/reports/export',          'AdminController@exportCsv'],
        ['GET',  '/admin/settings',                'AdminController@settingsForm'],
        ['POST', '/admin/settings',                'AdminController@saveSettings'],
        ['GET',  '/admin/account',                 'AdminController@accountForm'],
        ['POST', '/admin/account',                 'AdminController@saveAccount'],
        ['POST', '/admin/account/password',        'AdminController@changePassword'],
    ];

    public static function run(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';
        $base = base_path();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim($path, '/');

        foreach (self::ROUTES as [$verb, $pattern, $handler]) {
            if ($verb !== $method || !preg_match('#^' . $pattern . '$#D', $path, $matches)) {
                continue;
            }
            array_shift($matches);
            if ($method === 'POST') {
                csrf_check();
            }
            [$class, $action] = explode('@', $handler);
            $controller = __NAMESPACE__ . '\\' . $class;
            $object = new $controller();
            $object->{$action}(...array_map('intval', $matches));
            return;
        }
        abort_404();
    }
}
