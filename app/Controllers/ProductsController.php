<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ProductService;
use App\Services\VendorService;
use RuntimeException;

final class ProductsController extends Controller
{
    public function index(Request $request): void
    {
        $vendor = AuthService::requireActiveVendor();
        $editId = (int) $request->query('edit', 0);

        $this->render('vendor/products', [
            'title' => 'Produtos',
            'vendor' => $vendor,
            'products' => VendorService::products((int) $vendor['id']),
            'editing_product' => $editId > 0 ? ProductService::find((int) $vendor['id'], $editId) : null,
        ], 'vendor');
    }

    public function save(Request $request): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();

        try {
            ProductService::save((int) $vendor['id'], $request->input(), $_FILES);
            $this->flashSuccess('Produto salvo.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/products');
    }

    public function delete(Request $request, string $productId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();

        ProductService::delete((int) $vendor['id'], (int) $productId);
        $this->flashSuccess('Produto excluído.');
        $this->redirect('/vendor/products');
    }

    public function sell(Request $request, string $productId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();

        try {
            ProductService::sell((int) $vendor['id'], (int) $productId, $request->input());
            $this->flashSuccess('Venda registrada e financeiro atualizado.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/products');
    }
}
