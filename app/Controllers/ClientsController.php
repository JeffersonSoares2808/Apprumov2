<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ReportService;

final class ClientsController extends Controller
{
    public function index(Request $request): void
    {
        $vendor = AuthService::requireActiveVendor();

        $this->render('vendor/clients', [
            'title' => 'Clientes',
            'vendor' => $vendor,
            'clients' => ReportService::clients((int) $vendor['id']),
        ], 'vendor');
    }
}
