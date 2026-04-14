<?php

use App\Controllers\AdminController;
use App\Controllers\AdvancedAgendaController;
use App\Controllers\AuthController;
use App\Controllers\ClientsController;
use App\Controllers\FinanceController;
use App\Controllers\OnboardingController;
use App\Controllers\ProductsController;
use App\Controllers\PublicController;
use App\Controllers\ReportsController;
use App\Controllers\ServicesController;
use App\Controllers\SettingsController;
use App\Controllers\StatusController;
use App\Controllers\VendorController;

$router->get('/', [AuthController::class, 'home']);
$router->get('/login', [AuthController::class, 'login']);
$router->post('/auth/login', [AuthController::class, 'passwordLogin']);
$router->get('/register', [AuthController::class, 'register']);
$router->post('/register', [AuthController::class, 'storeRegister']);
$router->post('/auth/simple-login', [AuthController::class, 'simpleLogin']);
$router->post('/auth/logout', [AuthController::class, 'logout']);
$router->get('/dev/login', [AuthController::class, 'devLogin']);
$router->get('/dev/set-test-passwords', [AuthController::class, 'devSetTestPasswords']);

$router->get('/onboarding', [OnboardingController::class, 'show']);
$router->post('/onboarding', [OnboardingController::class, 'store']);

$router->get('/select-vendor', [AuthController::class, 'selectVendor']);
$router->post('/select-vendor', [AuthController::class, 'setVendor']);

$router->get('/pending', [StatusController::class, 'pending']);
$router->get('/suspended', [StatusController::class, 'suspended']);
$router->get('/plan-expired', [StatusController::class, 'expired']);

$router->get('/admin', [AdminController::class, 'index']);
$router->post('/admin/vendors/{vendorId}/activate', [AdminController::class, 'activateVendor']);
$router->post('/admin/vendors/{vendorId}/renew', [AdminController::class, 'renewVendor']);
$router->post('/admin/vendors/{vendorId}/suspend', [AdminController::class, 'suspendVendor']);
$router->post('/admin/vendors/{vendorId}/reactivate', [AdminController::class, 'reactivateVendor']);
$router->post('/admin/plans', [AdminController::class, 'savePlan']);
$router->post('/admin/plans/{planId}/delete', [AdminController::class, 'deletePlan']);

$router->get('/vendor/dashboard', [VendorController::class, 'dashboard']);
$router->get('/vendor/menu', [VendorController::class, 'menu']);
$router->get('/vendor/agenda', [VendorController::class, 'agenda']);
$router->post('/vendor/appointments', [VendorController::class, 'storeAppointment']);
$router->post('/vendor/appointments/{appointmentId}/status', [VendorController::class, 'updateAppointmentStatus']);
$router->post('/vendor/appointments/{appointmentId}/delete', [VendorController::class, 'deleteAppointment']);
$router->post('/vendor/waiting-list', [VendorController::class, 'storeWaiting']);
$router->post('/vendor/waiting-list/{entryId}/delete', [VendorController::class, 'deleteWaiting']);

$router->get('/vendor/services', [ServicesController::class, 'index']);
$router->post('/vendor/services', [ServicesController::class, 'save']);
$router->post('/vendor/services/{serviceId}/delete', [ServicesController::class, 'delete']);
$router->post('/vendor/services/{serviceId}/toggle', [ServicesController::class, 'toggle']);

$router->get('/vendor/products', [ProductsController::class, 'index']);
$router->post('/vendor/products', [ProductsController::class, 'save']);
$router->post('/vendor/products/{productId}/delete', [ProductsController::class, 'delete']);
$router->post('/vendor/products/{productId}/sell', [ProductsController::class, 'sell']);

$router->get('/vendor/finance', [FinanceController::class, 'index']);
$router->post('/vendor/finance/appointments/{appointmentId}/pay', [FinanceController::class, 'markPaid']);
$router->post('/vendor/finance/appointments/{appointmentId}/no-show', [FinanceController::class, 'markNoShow']);

$router->get('/vendor/reports', [ReportsController::class, 'index']);
$router->get('/vendor/clients', [ClientsController::class, 'index']);

$router->get('/vendor/settings', [SettingsController::class, 'index']);
$router->post('/vendor/settings', [SettingsController::class, 'save']);

$router->get('/vendor/professionals', [AdvancedAgendaController::class, 'professionals']);
$router->post('/vendor/professionals', [AdvancedAgendaController::class, 'storeProfessional']);
$router->post('/vendor/professionals/{professionalId}/update', [AdvancedAgendaController::class, 'updateProfessional']);
$router->post('/vendor/professionals/{professionalId}/toggle', [AdvancedAgendaController::class, 'toggleProfessional']);
$router->post('/vendor/professionals/{professionalId}/delete', [AdvancedAgendaController::class, 'deleteProfessional']);
$router->get('/vendor/professionals/{professionalId}/availability', [AdvancedAgendaController::class, 'professionalAvailability']);
$router->post('/vendor/professionals/{professionalId}/availability', [AdvancedAgendaController::class, 'updateProfessionalAvailability']);
$router->get('/vendor/professionals/{professionalId}/exceptions', [AdvancedAgendaController::class, 'professionalExceptions']);
$router->post('/vendor/professionals/{professionalId}/exceptions', [AdvancedAgendaController::class, 'addProfessionalException']);
$router->post('/vendor/professionals/{professionalId}/exceptions/{date}/delete', [AdvancedAgendaController::class, 'deleteProfessionalException']);

$router->get('/vendor/advanced-agenda', [AdvancedAgendaController::class, 'index']);
$router->post('/vendor/advanced-agenda/appointments', [AdvancedAgendaController::class, 'createAppointment']);
$router->post('/vendor/advanced-agenda/appointments/{appointmentId}/status', [AdvancedAgendaController::class, 'updateAppointmentStatus']);
$router->post('/vendor/advanced-agenda/appointments/{appointmentId}/delete', [AdvancedAgendaController::class, 'deleteAppointment']);

$router->get('/p/{slug}', [PublicController::class, 'profile']);
$router->get('/book/{slug}/{serviceId}', [PublicController::class, 'booking']);
$router->post('/book/{slug}/{serviceId}', [PublicController::class, 'storeBooking']);
