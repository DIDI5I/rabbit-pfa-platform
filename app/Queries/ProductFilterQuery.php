<?php

namespace App\Queries;

use App\Forms\ProductRequest;

class ProductFilterQuery
{
    private array $where = [];
    private array $params = [];

    public static function from(ProductRequest $request): array
    {
        $filter = new self();

        $filter
            ->activeOnly($request)
            ->search($request)
            ->category($request)
            ->lowStock($request)
            ->stockStatus($request)
            ->supplierId($request)
            ->supplierName($request)
            ->supplierCountry($request)
            ->supplierRating($request)
            ->minPrice($request)
            ->maxPrice($request);

        return [
            'sql' => $filter->where
                ? 'WHERE ' . implode(' AND ', $filter->where)
                : '',
            'params' => $filter->params,
        ];
    }

    private function activeOnly(ProductRequest $request): self
    {
        if ($request->activeOnly()) {
            $this->where[] = "c.is_active = 1";
        }

        return $this;
    }

    private function search(ProductRequest $request): self
    {
        if ($request->search() !== null) {
            $this->where[] = "(
                c.name LIKE ?
                OR c.sku LIKE ?
                OR c.description LIKE ?
                OR EXISTS (
                    SELECT 1
                    FROM part_sources ps
                    JOIN suppliers s ON s.id = ps.supplier_id
                    WHERE ps.component_id = c.id
                    AND s.name LIKE ?
                )
            )";

            $search = '%' . $request->search() . '%';

            array_push($this->params, $search, $search, $search, $search);
        }

        return $this;
    }

    private function category(ProductRequest $request): self
    {
        if ($request->category() !== null) {
            $this->where[] = "c.category = ?";
            $this->params[] = $request->category();
        }

        return $this;
    }

    private function lowStock(ProductRequest $request): self
    {
        if ($request->lowStock() === true) {
            $this->where[] = "c.stock_qty <= c.low_stock_threshold";
        }

        if ($request->lowStock() === false) {
            $this->where[] = "c.stock_qty > c.low_stock_threshold";
        }

        return $this;
    }

    private function supplierId(ProductRequest $request): self
    {
        if ($request->supplierId() !== null) {
            $this->where[] = "EXISTS (
                SELECT 1
                FROM part_sources ps
                WHERE ps.component_id = c.id
                  AND ps.supplier_id = ?
            )";

            $this->params[] = $request->supplierId();
        }

        return $this;
    }

    private function supplierName(ProductRequest $request): self
    {
        if ($request->supplierName() !== null) {
            $this->where[] = "EXISTS (
                SELECT 1
                FROM part_sources ps
                JOIN suppliers s ON s.id = ps.supplier_id
                WHERE ps.component_id = c.id
                  AND s.name LIKE ?
            )";

            $this->params[] = '%' . $request->supplierName() . '%';
        }

        return $this;
    }

    private function supplierCountry(ProductRequest $request): self
    {
        if ($request->supplierCountry() !== null) {
            $this->where[] = "EXISTS (
                SELECT 1
                FROM part_sources ps
                JOIN suppliers s ON s.id = ps.supplier_id
                WHERE ps.component_id = c.id
                  AND s.country LIKE ?
            )";

            $this->params[] = '%' . $request->supplierCountry() . '%';
        }

        return $this;
    }

    private function supplierRating(ProductRequest $request): self
    {
        if ($request->minSupplierRating() !== null) {
            $this->where[] = "EXISTS (
                SELECT 1
                FROM part_sources ps
                JOIN suppliers s ON s.id = ps.supplier_id
                WHERE ps.component_id = c.id
                  AND s.rating >= ?
            )";

            $this->params[] = $request->minSupplierRating();
        }

        return $this;
    }

    private function minPrice(ProductRequest $request): self
    {
        if ($request->minPrice() !== null) {
            $this->where[] = "EXISTS (
                SELECT 1
                FROM part_sources ps
                WHERE ps.component_id = c.id
                AND ps.unit_cost >= ?
            )";

            $this->params[] = $request->minPrice();
        }

        return $this;
    }

    private function maxPrice(ProductRequest $request): self
    {
        if ($request->maxPrice() !== null) {
            $this->where[] = "EXISTS (
                SELECT 1
                FROM part_sources ps
                WHERE ps.component_id = c.id
                AND ps.unit_cost <= ?
            )";

            $this->params[] = $request->maxPrice();
        }

        return $this;
    }

    private function stockStatus(ProductRequest $request): self
    {
        if ($request->stockStatus() === null) {
            return $this;
        }

        if ($request->stockStatus() === 'out') {
            $this->where[] = "c.stock_qty <= 0";
        }

        if ($request->stockStatus() === 'low') {
            $this->where[] = "c.stock_qty > 0 AND c.stock_qty <= c.low_stock_threshold";
        }

        if ($request->stockStatus() === 'ok') {
            $this->where[] = "c.stock_qty > c.low_stock_threshold";
        }

        return $this;
    }

}