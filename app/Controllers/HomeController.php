<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\VendorService;

final class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));

        $vendors = VendorService::listActivePublic(
            $search !== '' ? $search : null,
            $category !== '' ? $category : null
        );

        $categories = VendorService::listActiveCategories();
        $activeVendorCount = VendorService::countActive();

        $this->render('public/home', [
            'title' => 'Apprumo — Encontre profissionais perto de você',
            'vendors' => $vendors,
            'categories' => $categories,
            'search' => $search,
            'selected_category' => $category,
            'active_vendor_count' => $activeVendorCount,
        ], 'public');
    }
}
