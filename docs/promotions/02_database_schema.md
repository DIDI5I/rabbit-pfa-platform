# Rabbit — Promotions Database Schema

## `promotions`

```sql
CREATE TABLE promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    discount_type ENUM('percentage', 'fixed_amount') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_promotions_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
);
```

## `promotion_products`

```sql
CREATE TABLE promotion_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    promotion_id INT NOT NULL,
    component_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_promotion_products_promotion
        FOREIGN KEY (promotion_id) REFERENCES promotions(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_promotion_products_component
        FOREIGN KEY (component_id) REFERENCES components(id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_promotion_product (promotion_id, component_id)
);
```

## Important Notes

`promotion_products` links promotions to products from the `components` table.

The unique key prevents the same product from being attached to the same promotion multiple times.

The backend uses `INSERT IGNORE` when attaching products so duplicate attachments do not crash.

## Discount Types

Supported values:

```text
percentage
fixed_amount
```
