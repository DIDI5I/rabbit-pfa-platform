# Rabbit — Client-Safe Catalogue Backend Documentation

## Goal

Rabbit now has a separate client-safe catalogue layer.

This exists because internal product endpoints can expose operational data, while client catalogue endpoints must only expose safe browsing information.

---

## Endpoint Layers

### Internal Product Endpoint

```http
GET /products

Used for owner/admin/internal product management.

Can expose richer internal fields such as:

stock_qty
low_stock_threshold
stock_status
matched_source
supplier info
node_role
has_children
Client-Safe Catalogue Endpoint
GET /catalog/products

Used for client-facing product browsing.

Must not expose:

stock_qty
low_stock_threshold
purchase cost
supplier unit cost
VED class
stock intelligence data
owner analytics
supplier-sensitive fields
Catalogue Product List
GET /catalog/products

Supports:

search
category
availability
page
limit

Examples:

GET /catalog/products
GET /catalog/products?search=pompe
GET /catalog/products?category=component
GET /catalog/products?availability=AVAILABLE
GET /catalog/products?search=hydraulique&category=component&availability=AVAILABLE&page=1&limit=10
Catalogue Product Detail
GET /catalog/products/{id}

Returns one active catalogue product.

Client-safe product fields:

id
name
sku
description
category
unit_of_measure
availability_status
is_active

Availability values:

AVAILABLE
LOW_AVAILABILITY
OUT_OF_STOCK
Catalogue Product Relations
GET /catalog/products/{id}/relations

Returns safe product relationships.

Allowed relation types:

replacement_part
compatible_part
compatible_alternative
spare_part
accessory
related_product

This endpoint does not expose internal BOM/manufacturing data, stock quantities, purchase costs, supplier costs, or stock intelligence fields.

Catalogue Relation Response Shape
{
  "message": "Catalog product relations fetched successfully",
  "data": {
    "product_id": 1,
    "items": [
      {
        "relation_id": 10,
        "relation_type": "spare_part",
        "notes": null,
        "product": {
          "id": 27,
          "name": "Filtre retour hydraulique 10 microns",
          "sku": "CMP-HYD-FLT10",
          "description": "...",
          "category": "component",
          "unit_of_measure": "ea",
          "availability_status": "OUT_OF_STOCK",
          "is_active": true
        }
      }
    ],
    "summary": {
      "total": 1,
      "by_relation_type": {
        "spare_part": 1
      }
    }
  }
}
Current Catalogue Status
✅ GET /catalog/products
✅ GET /catalog/products/{id}
✅ GET /catalog/products/{id}/relations
✅ search filter
✅ category filter
✅ availability filter
✅ pagination
✅ client-safe relation response
✅ no owner-sensitive fields exposed
Design Rule

Use /products for internal rich product management.

Use /catalog/products for client-safe browsing.

The future chatbot must choose the correct endpoint based on user role.


Now start the next backend module: **Promotions**.

## Promotions backend goal

Add promotional campaigns that can be attached to products and shown safely in the client catalogue.

Initial endpoints:

```http
GET /promotions
GET /promotions/{id}
POST /promotions
PATCH /promotions/{id}
DELETE /promotions/{id}

GET /catalog/promotions/active
GET /catalog/products/{id}/promotions

For now, owner manages promotions. Clients can only read active promotions.

Step 1 — SQL tables

Run this first:

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