<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AdminService;
use App\Services\AuthService;
use App\Services\VendorService;
use RuntimeException;

final class AdminController extends Controller
{
    public function index(Request $request): void
    {
        AuthService::requireAdmin();

        $filter = (string) $request->query('status', 'all');
        $plans = VendorService::listPlans();
        $vendors = VendorService::listAll($filter);
        $editPlanId = (int) $request->query('edit_plan', 0);
        $editingPlan = null;

        foreach ($plans as $plan) {
            if ((int) $plan['id'] === $editPlanId) {
                $editingPlan = $plan;
                break;
            }
        }

        $this->render('admin/index', [
            'title' => 'Admin',
            'filter' => $filter,
            'vendors' => $vendors,
            'plans' => $plans,
            'editing_plan' => $editingPlan,
        ], 'app');
    }

    public function activateVendor(Request $request, string $vendorId): void
    {
        $this->validateCsrf($request);
        AuthService::requireAdmin();

        try {
            AdminService::activateVendor((int) $vendorId, (int) $request->input('plan_id'));
            $this->flashSuccess('Vendor ativado com plano e vencimento configurados.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/admin?status=' . urlencode((string) $request->input('current_filter', 'all')));
    }

    public function renewVendor(Request $request, string $vendorId): void
    {
        $this->validateCsrf($request);
        AuthService::requireAdmin();

        try {
            AdminService::renewVendor((int) $vendorId, (int) $request->input('plan_id'));
            $this->flashSuccess('Plano renovado com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/admin?status=' . urlencode((string) $request->input('current_filter', 'all')));
    }

    public function suspendVendor(Request $request, string $vendorId): void
    {
        $this->validateCsrf($request);
        AuthService::requireAdmin();

        AdminService::suspendVendor((int) $vendorId);
        $this->flashSuccess('Vendor suspenso com sucesso.');
        $this->redirect('/admin?status=' . urlencode((string) $request->input('current_filter', 'all')));
    }

    public function reactivateVendor(Request $request, string $vendorId): void
    {
        $this->validateCsrf($request);
        AuthService::requireAdmin();

        try {
            AdminService::reactivateVendor((int) $vendorId, (int) $request->input('plan_id'));
            $this->flashSuccess('Vendor reativado.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/admin?status=' . urlencode((string) $request->input('current_filter', 'all')));
    }

    public function savePlan(Request $request): void
    {
        $this->validateCsrf($request);
        AuthService::requireAdmin();

        try {
            AdminService::savePlan($request->input());
            $this->flashSuccess('Plano salvo com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/admin');
    }

    public function deletePlan(Request $request, string $planId): void
    {
        $this->validateCsrf($request);
        AuthService::requireAdmin();

        AdminService::deletePlan((int) $planId);
        $this->flashSuccess('Plano excluído.');
        $this->redirect('/admin');
    }
}
