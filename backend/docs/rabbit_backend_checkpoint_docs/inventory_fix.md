# Inventory Logic and Duplicate Row Fix

## What Inventory Does

The inventory module provides owner-side stock visibility.

It answers:

```text
What products/items do we currently have?
How much stock do we have now?
Which items are low or out of stock?
What is the approximate stock value?
Who is the preferred supplier for each item?
```

## Core Rule

The real current stock is calculated from `stock_movements`:

```text
current_stock = total IN movements - total OUT movements
```

`components.stock_qty` is treated as legacy/reference data.

## Tables Used

Inventory combines:

```text
components       -> product identity
stock_movements  -> real stock movement history
part_sources     -> supplier/cost/lead-time information
suppliers        -> supplier name
```

## Stock Status Logic

The service/controller classifies each item roughly like this:

```php
if ($currentStock <= 0) {
    $status = 'OUT_OF_STOCK';
} elseif ($currentStock <= $lowStockThreshold) {
    $status = 'LOW_STOCK';
} else {
    $status = 'OK';
}
```

## Estimated Stock Value

Inventory calculates approximate stock value as:

```text
estimated_stock_value = current_stock * preferred_unit_cost
```

## Bug Found

The old inventory query joined directly to `part_sources` using:

```sql
LEFT JOIN part_sources ps
    ON ps.component_id = c.id
    AND ps.is_preferred = 1
```

But some products had more than one source marked as preferred.

That caused duplicated inventory rows.

Example:

```text
Product 33 with Supplier A
Product 33 with Supplier B
```

This was wrong because inventory should return one row per component/product.

## Fix Applied

The query now selects exactly one source per component using a subquery:

```sql
LEFT JOIN part_sources ps
    ON ps.id = (
        SELECT ps2.id
        FROM part_sources ps2
        WHERE ps2.component_id = c.id
        ORDER BY ps2.is_preferred DESC, ps2.id DESC
        LIMIT 1
    )
```

This applies the rule:

```text
preferred source first
then highest/newest id as tie-breaker
```

## Stock Aggregation Fix

Stock is calculated in a separate subquery:

```sql
LEFT JOIN (
    SELECT
        component_id,
        SUM(
            CASE
                WHEN type = 'in' THEN quantity
                WHEN type = 'out' THEN -quantity
                ELSE 0
            END
        ) AS current_stock
    FROM stock_movements
    GROUP BY component_id
) stock
    ON stock.component_id = c.id
```

This prevents stock totals from being multiplied by supplier joins.

## Final Inventory Query

```php
<?php

namespace App\Queries;

class InventoryQuery
{
    public static function all(): string
    {
        return "
            SELECT
                c.id,
                c.name,
                c.sku,
                c.category,
                c.stock_qty AS legacy_stock_qty,
                c.low_stock_threshold,

                COALESCE(stock.current_stock, 0) AS current_stock,

                s.name AS preferred_supplier,
                ps.unit_cost AS preferred_unit_cost_mad,
                ps.lead_time_days

            FROM components c

            LEFT JOIN (
                SELECT
                    component_id,
                    SUM(
                        CASE
                            WHEN type = 'in' THEN quantity
                            WHEN type = 'out' THEN -quantity
                            ELSE 0
                        END
                    ) AS current_stock
                FROM stock_movements
                GROUP BY component_id
            ) stock
                ON stock.component_id = c.id

            LEFT JOIN part_sources ps
                ON ps.id = (
                    SELECT ps2.id
                    FROM part_sources ps2
                    WHERE ps2.component_id = c.id
                    ORDER BY ps2.is_preferred DESC, ps2.id DESC
                    LIMIT 1
                )

            LEFT JOIN suppliers s
                ON s.id = ps.supplier_id

            WHERE c.is_active = 1

            ORDER BY c.name ASC
        ";
    }

    public static function alerts(): string
    {
        return "
            SELECT
                c.id,
                c.name,
                c.sku,
                c.category,
                c.stock_qty AS legacy_stock_qty,
                c.low_stock_threshold,

                COALESCE(stock.current_stock, 0) AS current_stock,

                s.name AS preferred_supplier,
                ps.unit_cost AS preferred_unit_cost_mad,
                ps.lead_time_days

            FROM components c

            LEFT JOIN (
                SELECT
                    component_id,
                    SUM(
                        CASE
                            WHEN type = 'in' THEN quantity
                            WHEN type = 'out' THEN -quantity
                            ELSE 0
                        END
                    ) AS current_stock
                FROM stock_movements
                GROUP BY component_id
            ) stock
                ON stock.component_id = c.id

            LEFT JOIN part_sources ps
                ON ps.id = (
                    SELECT ps2.id
                    FROM part_sources ps2
                    WHERE ps2.component_id = c.id
                    ORDER BY ps2.is_preferred DESC, ps2.id DESC
                    LIMIT 1
                )

            LEFT JOIN suppliers s
                ON s.id = ps.supplier_id

            WHERE c.is_active = 1
              AND COALESCE(stock.current_stock, 0) <= c.low_stock_threshold

            ORDER BY current_stock ASC, c.name ASC
        ";
    }
}
```

## Business Rule Reminder

Inventory does not manufacture assemblies.

Rabbit buys and stocks assemblies, sub-assemblies, components, and raw materials directly.

Dependencies/BOM relations are not used to consume child stock or produce parent stock.

So inventory does not calculate:

```text
assembly stock = min(child stock quantities)
```

Every item is independently stocked.
