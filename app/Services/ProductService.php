<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class ProductService
{
    public static function save(int $vendorId, array $data, array $files = []): void
    {
        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Informe o nome do produto.');
        }

        $existing = $id > 0 ? self::find($vendorId, $id) : null;
        $imagePath = UploadService::storeImage($files['image'] ?? null, 'products') ?: ($existing['image_path'] ?? null);

        $payload = [
            'vendor_id' => $vendorId,
            'name' => $name,
            'description' => trim((string) ($data['description'] ?? '')),
            'sale_price' => (float) ($data['sale_price'] ?? 0),
            'cost_price' => (float) ($data['cost_price'] ?? 0),
            'stock_quantity' => max(0, (int) ($data['stock_quantity'] ?? 0)),
            'min_stock_quantity' => max(0, (int) ($data['min_stock_quantity'] ?? 0)),
            'category' => trim((string) ($data['category'] ?? '')),
            'image_path' => $imagePath,
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ];

        if ($existing) {
            Database::statement(
                'UPDATE products SET
                    name = :name,
                    description = :description,
                    sale_price = :sale_price,
                    cost_price = :cost_price,
                    stock_quantity = :stock_quantity,
                    min_stock_quantity = :min_stock_quantity,
                    category = :category,
                    image_path = :image_path,
                    is_active = :is_active,
                    updated_at = NOW()
                 WHERE id = :id AND vendor_id = :vendor_id',
                $payload + ['id' => $id]
            );

            return;
        }

        Database::statement(
            'INSERT INTO products (
                vendor_id, name, description, sale_price, cost_price, stock_quantity, min_stock_quantity,
                category, image_path, is_active, created_at, updated_at
             ) VALUES (
                :vendor_id, :name, :description, :sale_price, :cost_price, :stock_quantity, :min_stock_quantity,
                :category, :image_path, :is_active, NOW(), NOW()
             )',
            $payload
        );
    }

    public static function delete(int $vendorId, int $productId): void
    {
        Database::statement('DELETE FROM products WHERE id = :id AND vendor_id = :vendor_id', [
            'id' => $productId,
            'vendor_id' => $vendorId,
        ]);
    }

    public static function sell(int $vendorId, int $productId, array $data): void
    {
        $product = self::find($vendorId, $productId);
        if (!$product) {
            throw new RuntimeException('Produto não encontrado.');
        }

        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        if ((int) $product['stock_quantity'] < $quantity) {
            throw new RuntimeException('Estoque insuficiente para registrar esta venda.');
        }

        $unitPrice = isset($data['unit_price']) && $data['unit_price'] !== ''
            ? (float) $data['unit_price']
            : (float) $product['sale_price'];
        $totalAmount = $unitPrice * $quantity;

        Database::transaction(static function () use ($vendorId, $productId, $product, $quantity, $unitPrice, $totalAmount, $data): void {
            Database::statement(
                'UPDATE products
                 SET stock_quantity = stock_quantity - :quantity, updated_at = NOW()
                 WHERE id = :id AND vendor_id = :vendor_id',
                [
                    'quantity' => $quantity,
                    'id' => $productId,
                    'vendor_id' => $vendorId,
                ]
            );

            Database::statement(
                'INSERT INTO product_sales (
                    vendor_id, product_id, quantity, unit_price, total_amount, customer_name, sold_at, created_at, updated_at
                 ) VALUES (
                    :vendor_id, :product_id, :quantity, :unit_price, :total_amount, :customer_name, NOW(), NOW(), NOW()
                 )',
                [
                    'vendor_id' => $vendorId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_amount' => $totalAmount,
                    'customer_name' => trim((string) ($data['customer_name'] ?? '')),
                ]
            );

            $saleId = Database::lastInsertId();

            Database::statement(
                'INSERT INTO financial_transactions (
                    vendor_id, product_id, product_sale_id, kind, source, title, description, amount,
                    status, transaction_date, created_at, updated_at
                 ) VALUES (
                    :vendor_id, :product_id, :product_sale_id, "income", "product_sale", :title, :description, :amount,
                    "paid", CURDATE(), NOW(), NOW()
                 )',
                [
                    'vendor_id' => $vendorId,
                    'product_id' => $productId,
                    'product_sale_id' => $saleId,
                    'title' => 'Venda de produto',
                    'description' => $product['name'],
                    'amount' => $totalAmount,
                ]
            );
        });

        // Check low stock and notify
        try {
            $updated = self::find($vendorId, $productId);
            if ($updated && (int) $updated['stock_quantity'] <= (int) $updated['min_stock_quantity']) {
                NotificationService::lowStockAlert($vendorId, $updated);
            }
        } catch (\Throwable $e) {
            error_log('Notification error on low stock: ' . $e->getMessage());
        }
    }

    public static function find(int $vendorId, int $productId): ?array
    {
        return Database::selectOne(
            'SELECT * FROM products WHERE id = :id AND vendor_id = :vendor_id LIMIT 1',
            ['id' => $productId, 'vendor_id' => $vendorId]
        );
    }
}
