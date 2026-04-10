<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ServiceCatalogService;
use App\Services\VendorService;
use RuntimeException;

final class ServicesController extends Controller
{
    public function index(Request $request): void
    {
        $vendor = AuthService::requireActiveVendor();
        $editId = (int) $request->query('edit', 0);

        $this->render('vendor/services', [
            'title' => 'Serviços',
            'vendor' => $vendor,
            'services' => VendorService::services((int) $vendor['id']),
            'editing_service' => $editId > 0 ? ServiceCatalogService::find((int) $vendor['id'], $editId) : null,
        ], 'vendor');
    }

    public function save(Request $request): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();

        try {
            ServiceCatalogService::save((int) $vendor['id'], $request->input(), $_FILES);
            $this->flashSuccess('Serviço salvo.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/services');
    }

    public function delete(Request $request, string $serviceId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();

        ServiceCatalogService::delete((int) $vendor['id'], (int) $serviceId);
        $this->flashSuccess('Serviço excluído.');
        $this->redirect('/vendor/services');
    }

    public function toggle(Request $request, string $serviceId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();

        try {
            ServiceCatalogService::toggle((int) $vendor['id'], (int) $serviceId);
            $this->flashSuccess('Status do serviço atualizado.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/services');
    }
}
