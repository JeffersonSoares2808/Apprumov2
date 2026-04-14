<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\VendorService;
use RuntimeException;

final class SettingsController extends Controller
{
    public function index(Request $request): void
    {
        $vendor = AuthService::requireActiveVendor();

        $this->render('vendor/settings', [
            'title' => 'Configurações',
            'vendor' => $vendor,
            'weekly_hours' => VendorService::weeklyHours((int) $vendor['id']),
            'special_days' => VendorService::specialDays((int) $vendor['id']),
            'notification_settings' => \App\Services\NotificationService::getSettings((int) $vendor['id']),
        ], 'vendor');
    }

    public function save(Request $request): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();

        try {
            VendorService::saveSettings((int) $vendor['id'], $request->input(), $_FILES);

            // Save notification settings
            $notifData = $request->input('notifications', []);
            if (is_array($notifData)) {
                \App\Services\NotificationService::updateSettings((int) $vendor['id'], $notifData);
            }

            $this->flashSuccess('Configurações salvas com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/settings');
    }
}
