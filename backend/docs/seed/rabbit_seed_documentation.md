# Rabbit Safe Additive Seed Documentation

**Project:** Rabbit industrial MRO / procurement / stock-management platform  
**Date documented:** 2026-05-03  
**Purpose:** Document all seed/API work prepared for Rabbit, including user registration payloads, real user ID mapping, business table schemas provided, SQL seed sections, execution order, and verification queries.

---

## 0. Core rules followed

The seed strategy followed these constraints:

- No destructive reset.
- No `DROP TABLE`.
- No `TRUNCATE`.
- No broad deletion of real business data.
- No SQL inserts for `users`.
- Users are created through the backend registration API so passwords are hashed correctly.
- SQL is used only for business/demo data.
- Synthetic/demo rows are tagged with predictable prefixes such as:
  - `synthetic:%`
  - `synthetic:rfq-demo:%`
  - `synthetic:order-demo:%`
  - `synthetic:notification-demo:%`
  - `SYN-%` for synthetic supplier part numbers.
- Stock source of truth is `stock_movements`.
- Stock formula:

```txt
current_stock = SUM(type = 'in') - SUM(type = 'out')
```

- `stock_movements.quantity` is always positive.
- Direction is controlled by `type = 'in'` or `type = 'out'`.
- An accepted RFQ does **not** increase stock directly.
- A finalized purchase lot creates a `stock_movements` row with:

```txt
type = 'in'
reason = 'PURCHASE_RECEIVED'
reference_type = 'purchase_lot'
```

- Rabbit does not manufacture products.
- Dependencies/BOM are used for navigation, structure, compatibility, and procurement intelligence only.
- Dependencies do **not** consume child stock to create parent stock.

---

## 1. Tables covered

The following tables were covered during the seed planning:

```txt
components
stock_movements
purchase_lots
rfq_requests
rfq_revisions
orders
order_items
dependencies
part_sources
notifications
users
suppliers
```

Notes:

- `users` were handled through API payloads only.
- `suppliers` were assumed to already exist with IDs `1` through `8`, based on the previous seed.

---

## 2. API users registered

Users were created through the backend API, not SQL.

### Original API user payload list

```json
[
  {
    "name": "Admin Rabbit",
    "email": "owner@rabbit.local",
    "password": "Password123!",
    "role": "owner"
  },
  {
    "name": "Ahmed Acheteur",
    "email": "client@industrie.ma",
    "password": "Password123!",
    "role": "client"
  },
  {
    "name": "Casablanca Manufacturing",
    "email": "casablanca.client@rabbit.local",
    "password": "Password123!",
    "role": "client"
  },
  {
    "name": "Atlas Maintenance Services",
    "email": "atlas.maintenance@rabbit.local",
    "password": "Password123!",
    "role": "client"
  },
  {
    "name": "Meknes Agro Industrie",
    "email": "meknes.agro@rabbit.local",
    "password": "Password123!",
    "role": "client"
  },
  {
    "name": "Tanger Automotive Components",
    "email": "tanger.automotive@rabbit.local",
    "password": "Password123!",
    "role": "client"
  },
  {
    "name": "Atlas Industrial Contact",
    "email": "atlas.fournisseur@rabbit.local",
    "password": "Password123!",
    "role": "fournisseur",
    "supplier_company_id": 1
  },
  {
    "name": "Maghreb Pumps Contact",
    "email": "maghreb.pumps@rabbit.local",
    "password": "Password123!",
    "role": "fournisseur",
    "supplier_company_id": 2
  },
  {
    "name": "Casatech Hydraulique Contact",
    "email": "casatech.hydraulique@rabbit.local",
    "password": "Password123!",
    "role": "fournisseur",
    "supplier_company_id": 3
  },
  {
    "name": "Tangier Mechanical Works Contact",
    "email": "tangier.mechanical@rabbit.local",
    "password": "Password123!",
    "role": "fournisseur",
    "supplier_company_id": 4
  },
  {
    "name": "NordMeca Distribution Contact",
    "email": "nordmeca@rabbit.local",
    "password": "Password123!",
    "role": "fournisseur",
    "supplier_company_id": 5
  },
  {
    "name": "Rhein Precision Contact",
    "email": "rhein.precision@rabbit.local",
    "password": "Password123!",
    "role": "fournisseur",
    "supplier_company_id": 6
  },
  {
    "name": "Lombardia Fluid Systems Contact",
    "email": "lombardia.fluid@rabbit.local",
    "password": "Password123!",
    "role": "fournisseur",
    "supplier_company_id": 7
  },
  {
    "name": "Iberica Sealing Contact",
    "email": "iberica.sealing@rabbit.local",
    "password": "Password123!",
    "role": "fournisseur",
    "supplier_company_id": 8
  }
]
```

### Real user IDs available after API registration

The following real IDs were observed in the database after registration:

```sql
-- Owner
SET @owner_id := 1; -- Admin Rabbit

-- Clients
SET @client_ahmed_id := 2; -- Ahmed Acheteur
SET @client_casablanca_id := 12; -- Casablanca Manufacturing
SET @client_atlas_maintenance_id := 13; -- Atlas Maintenance Services
SET @client_meknes_agro_id := 14; -- Meknes Agro Industrie
SET @client_tanger_automotive_id := 15; -- Tanger Automotive Components

-- Fournisseur users
SET @supplier_atlas_user_id := 16; -- Atlas Industrial Contact, supplier_company_id = 1
SET @supplier_maghreb_pumps_user_id := 17; -- Maghreb Pumps Contact, supplier_company_id = 2
SET @supplier_casatech_user_id := 18; -- Casatech Hydraulique Contact, supplier_company_id = 3
SET @supplier_tangier_mechanical_user_id := 19; -- Tangier Mechanical Works Contact, supplier_company_id = 4
SET @supplier_nordmeca_user_id := 20; -- NordMeca Distribution Contact, supplier_company_id = 5
SET @supplier_rhein_precision_user_id := 21; -- Rhein Precision Contact, supplier_company_id = 6
SET @supplier_lombardia_fluid_user_id := 22; -- Lombardia Fluid Systems Contact, supplier_company_id = 7
SET @supplier_iberica_sealing_user_id := 23; -- Iberica Sealing Contact, supplier_company_id = 8
```

---

## 3. Business schemas received

### 3.1 `components`

```sql
CREATE TABLE `components` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` enum('assembly','sub_assembly','component','raw_material') COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_of_measure` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pcs',
  `stock_qty` decimal(10,4) DEFAULT '0.0000',
  `low_stock_threshold` decimal(10,4) DEFAULT '10.0000',
  `ved_class` enum('V','E','D') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specs` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_components_sku` (`sku`),
  KEY `idx_components_category` (`category`),
  KEY `idx_components_stock_qty` (`stock_qty`),
  KEY `idx_components_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 `stock_movements`

```sql
CREATE TABLE `stock_movements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `component_id` int NOT NULL,
  `type` enum('in','out') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,4) NOT NULL,
  `reason` enum('INITIAL_STOCK','RFQ_ACCEPTED','PURCHASE_RECEIVED','SALE','MANUAL_ADJUSTMENT','RETURN','DAMAGED','CANCELLED_ORDER_RESTORE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_movements_component_id` (`component_id`),
  KEY `idx_stock_movements_type` (`type`),
  KEY `idx_stock_movements_reason` (`reason`),
  KEY `idx_stock_movements_reference_type` (`reference_type`),
  KEY `idx_stock_movements_created_by` (`created_by`),
  KEY `idx_stock_movements_created_at` (`created_at`),
  CONSTRAINT `fk_stock_movements_component` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.3 `purchase_lots`

```sql
CREATE TABLE `purchase_lots` (
  `id` int NOT NULL AUTO_INCREMENT,
  `component_id` int NOT NULL,
  `supplier_id` int NOT NULL,
  `quantity_received` decimal(10,4) NOT NULL,
  `supplier_unit_price` decimal(12,2) NOT NULL,
  `supplier_total_price` decimal(12,2) NOT NULL,
  `transport_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `customs_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `handling_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `packaging_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `order_preparation_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `other_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_purchase_cost` decimal(12,2) NOT NULL,
  `unit_purchase_cost` decimal(12,4) NOT NULL,
  `status` enum('draft','finalized','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `purchase_date` date NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_purchase_lots_component_id` (`component_id`),
  KEY `idx_purchase_lots_supplier_id` (`supplier_id`),
  KEY `idx_purchase_lots_purchase_date` (`purchase_date`),
  KEY `idx_purchase_lots_reference_type` (`reference_type`),
  KEY `idx_purchase_lots_created_by` (`created_by`),
  CONSTRAINT `fk_purchase_lots_component` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_purchase_lots_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.4 `rfq_requests`

```sql
CREATE TABLE `rfq_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `component_id` int NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `quantity_requested` decimal(10,4) NOT NULL,
  `status` enum('draft','open','quoted','accepted','rejected','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `client_message` text COLLATE utf8mb4_unicode_ci,
  `quoted_price` decimal(10,2) DEFAULT NULL,
  `supplier_message` text COLLATE utf8mb4_unicode_ci,
  `deadline_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `revision_id` int DEFAULT '1',
  `is_blind` tinyint(1) DEFAULT '0',
  `auto_triggered` tinyint(1) DEFAULT '0',
  `decision_note` text COLLATE utf8mb4_unicode_ci,
  `decided_by` int DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rfq_requests_client_id` (`client_id`),
  KEY `idx_rfq_requests_component_id` (`component_id`),
  KEY `idx_rfq_requests_supplier_id` (`supplier_id`),
  KEY `idx_rfq_requests_status` (`status`),
  KEY `idx_rfq_requests_decided_by` (`decided_by`),
  CONSTRAINT `fk_rfq_requests_component` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rfq_requests_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.5 `rfq_revisions`

```sql
CREATE TABLE `rfq_revisions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rfq_id` int NOT NULL,
  `revision_id` int NOT NULL,
  `supplier_id` int NOT NULL,
  `quoted_price` decimal(10,2) DEFAULT NULL,
  `lead_time_days` int DEFAULT NULL,
  `supplier_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rfq_revisions_rfq_id` (`rfq_id`),
  KEY `fk_rfq_revisions_supplier` (`supplier_id`),
  CONSTRAINT `fk_rfq_revisions_rfq` FOREIGN KEY (`rfq_id`) REFERENCES `rfq_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rfq_revisions_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.6 `orders`

```sql
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `status` enum('pending','paid','processing','shipped','delivered','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `stripe_payment_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `shipping_address` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orders_client_id` (`client_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_stripe_payment_id` (`stripe_payment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.7 `order_items`

```sql
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `component_id` int NOT NULL,
  `quantity` decimal(10,4) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order_id` (`order_id`),
  KEY `idx_order_items_component_id` (`component_id`),
  KEY `idx_order_items_supplier_id` (`supplier_id`),
  CONSTRAINT `fk_order_items_component` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.8 `dependencies`

```sql
CREATE TABLE `dependencies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int NOT NULL,
  `child_id` int NOT NULL,
  `qty_required` decimal(10,4) NOT NULL DEFAULT '1.0000',
  `uom` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pcs',
  `is_phantom` tinyint(1) DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `relation_type` enum('technical_structure','replacement_part','compatible_part','compatible_alternative','spare_part','accessory','similar_product','commercial_alternative','frequently_bought_together','related_product') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'technical_structure',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dependencies_parent_id` (`parent_id`),
  KEY `idx_dependencies_child_id` (`child_id`),
  CONSTRAINT `fk_dependencies_child` FOREIGN KEY (`child_id`) REFERENCES `components` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dependencies_parent` FOREIGN KEY (`parent_id`) REFERENCES `components` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.9 `part_sources`

```sql
CREATE TABLE `part_sources` (
  `id` int NOT NULL AUTO_INCREMENT,
  `component_id` int NOT NULL,
  `supplier_id` int NOT NULL,
  `supplier_part_num` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `lead_time_days` int DEFAULT NULL,
  `min_order_qty` decimal(10,4) DEFAULT '1.0000',
  `is_preferred` tinyint(1) DEFAULT '0',
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_part_sources_component_id` (`component_id`),
  KEY `idx_part_sources_supplier_id` (`supplier_id`),
  KEY `idx_part_sources_valid_from` (`valid_from`),
  CONSTRAINT `fk_part_sources_component` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_part_sources_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.10 `notifications`

```sql
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `type` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_type` varchar(100) DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_id` (`user_id`),
  KEY `idx_notifications_role` (`role`),
  KEY `idx_notifications_is_read` (`is_read`),
  KEY `idx_notifications_created_at` (`created_at`),
  KEY `idx_notifications_reference` (`reference_type`,`reference_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

## 4. Master SQL seed script

> Recommended execution order is documented later in this file.  
> The script below is safe additive, except optional synthetic-only cleanup sections that delete only rows tagged as synthetic/demo rows.

### 4.1 Global variables

```sql
-- API-created users
SET @owner_id := 1;

SET @client_ahmed_id := 2;
SET @client_casablanca_id := 12;
SET @client_atlas_maintenance_id := 13;
SET @client_meknes_agro_id := 14;
SET @client_tanger_automotive_id := 15;

SET @supplier_atlas_user_id := 16;
SET @supplier_maghreb_pumps_user_id := 17;
SET @supplier_casatech_user_id := 18;
SET @supplier_tangier_mechanical_user_id := 19;
SET @supplier_nordmeca_user_id := 20;
SET @supplier_rhein_precision_user_id := 21;
SET @supplier_lombardia_fluid_user_id := 22;
SET @supplier_iberica_sealing_user_id := 23;

-- Existing suppliers from previous seed
SET @supplier_atlas_id := 1;
SET @supplier_maghreb_pumps_id := 2;
SET @supplier_casatech_id := 3;
SET @supplier_tangier_mechanical_id := 4;
SET @supplier_nordmeca_id := 5;
SET @supplier_rhein_precision_id := 6;
SET @supplier_lombardia_id := 7;
SET @supplier_iberica_id := 8;

-- Existing components resolved by SKU
SET @asm_pmp_xr200_id := (SELECT id FROM components WHERE sku = 'ASM-PMP-XR200' LIMIT 1);
SET @asm_pmp_xr300_id := (SELECT id FROM components WHERE sku = 'ASM-PMP-XR300' LIMIT 1);
SET @asm_hyd_hpu80_id := (SELECT id FROM components WHERE sku = 'ASM-HYD-HPU80' LIMIT 1);
SET @asm_conv_drv750_id := (SELECT id FROM components WHERE sku = 'ASM-CONV-DRV750' LIMIT 1);
SET @asm_fan_bd1450_id := (SELECT id FROM components WHERE sku = 'ASM-FAN-BD1450' LIMIT 1);

SET @sub_pmp_wet50_id := (SELECT id FROM components WHERE sku = 'SUB-PMP-WET50' LIMIT 1);
SET @sub_mtr_pkg75_id := (SELECT id FROM components WHERE sku = 'SUB-MTR-PKG75' LIMIT 1);
SET @sub_hyd_pump11_id := (SELECT id FROM components WHERE sku = 'SUB-HYD-PUMP11' LIMIT 1);
SET @sub_brg_ucp207_pair_id := (SELECT id FROM components WHERE sku = 'SUB-BRG-UCP207-PAIR' LIMIT 1);
SET @sub_mnt_kit_pmp_id := (SELECT id FROM components WHERE sku = 'SUB-MNT-KIT-PMP' LIMIT 1);

SET @cmp_brg_6205_id := (SELECT id FROM components WHERE sku = 'CMP-BRG-6205' LIMIT 1);
SET @cmp_brg_ucp207_id := (SELECT id FROM components WHERE sku = 'CMP-BRG-UCP207' LIMIT 1);
SET @cmp_seal_mech25_id := (SELECT id FROM components WHERE sku = 'CMP-SEAL-MECH25' LIMIT 1);
SET @cmp_oring_epdm_kit_id := (SELECT id FROM components WHERE sku = 'CMP-ORING-EPDM-KIT' LIMIT 1);
SET @cmp_seal_oil35_id := (SELECT id FROM components WHERE sku = 'CMP-SEAL-OIL35' LIMIT 1);
SET @cmp_hyd_gp11_id := (SELECT id FROM components WHERE sku = 'CMP-HYD-GP11' LIMIT 1);
SET @cmp_hyd_rv210_id := (SELECT id FROM components WHERE sku = 'CMP-HYD-RV210' LIMIT 1);
SET @cmp_hyd_flt10_id := (SELECT id FROM components WHERE sku = 'CMP-HYD-FLT10' LIMIT 1);
SET @cmp_fst_m10bolt_id := (SELECT id FROM components WHERE sku = 'CMP-FST-M10BOLT' LIMIT 1);
SET @cmp_fst_m10wash_id := (SELECT id FROM components WHERE sku = 'CMP-FST-M10WASH' LIMIT 1);

SET @raw_stl_c45_35_id := (SELECT id FROM components WHERE sku = 'RAW-STL-C45-35' LIMIT 1);
SET @raw_gsk_epdm3_id := (SELECT id FROM components WHERE sku = 'RAW-GSK-EPDM3' LIMIT 1);
```

---

### 4.2 Optional synthetic-only cleanup

Run this only if you want to remove previously generated synthetic/demo rows before re-running the seed.

```sql
DELETE FROM notifications
WHERE message LIKE 'synthetic:notification-demo:%';

DELETE FROM stock_movements
WHERE notes LIKE 'synthetic:order-demo:%'
   OR notes LIKE 'synthetic:rfq-demo:%'
   OR notes LIKE 'synthetic:purchase-lot:%'
   OR notes LIKE 'synthetic:%';

DELETE FROM rfq_revisions
WHERE rfq_id IN (
  SELECT id FROM rfq_requests WHERE client_message LIKE 'synthetic:rfq-demo:%'
);

DELETE FROM purchase_lots
WHERE notes LIKE 'synthetic:rfq-demo:%'
   OR notes LIKE 'synthetic:purchase-lot:%';

DELETE FROM rfq_requests
WHERE client_message LIKE 'synthetic:rfq-demo:%';

DELETE FROM orders
WHERE notes LIKE 'synthetic:order-demo:%';

DELETE FROM dependencies
WHERE notes LIKE 'synthetic:dependency-demo:%';

DELETE FROM part_sources
WHERE supplier_part_num LIKE 'SYN-%';
```

**Important:** The cleanup above only targets synthetic/demo rows. Do not change the `WHERE` clauses into broad deletes.

---

### 4.3 VED classification updates

```sql
UPDATE components
SET ved_class = 'V'
WHERE sku IN (
  'ASM-PMP-XR200',
  'ASM-PMP-XR300',
  'ASM-HYD-HPU80',
  'CMP-HYD-GP11',
  'CMP-HYD-RV210'
);

UPDATE components
SET ved_class = 'E'
WHERE sku IN (
  'ASM-CONV-DRV750',
  'ASM-FAN-BD1450',
  'SUB-PMP-WET50',
  'SUB-MTR-PKG75',
  'SUB-HYD-PUMP11',
  'SUB-BRG-UCP207-PAIR',
  'SUB-MNT-KIT-PMP',
  'CMP-BRG-6205',
  'CMP-BRG-UCP207',
  'CMP-SEAL-MECH25',
  'CMP-ORING-EPDM-KIT',
  'CMP-SEAL-OIL35',
  'CMP-HYD-FLT10'
);

UPDATE components
SET ved_class = 'D'
WHERE sku IN (
  'CMP-FST-M10BOLT',
  'CMP-FST-M10WASH',
  'RAW-STL-C45-35',
  'RAW-GSK-EPDM3'
);
```

---

### 4.4 Threshold setup

```sql
UPDATE components
SET low_stock_threshold = 10
WHERE sku IN (
  'ASM-PMP-XR200',
  'ASM-PMP-XR300',
  'ASM-HYD-HPU80',
  'CMP-HYD-GP11',
  'CMP-HYD-RV210',
  'CMP-HYD-FLT10',
  'CMP-BRG-6205',
  'CMP-SEAL-MECH25'
);

UPDATE components
SET low_stock_threshold = 100
WHERE sku IN (
  'CMP-FST-M10BOLT',
  'CMP-FST-M10WASH',
  'RAW-STL-C45-35',
  'RAW-GSK-EPDM3'
);
```

---

### 4.5 Stock intelligence synthetic movements

These rows demonstrate stock intelligence cases:

- Above threshold.
- Below threshold.
- Zero stock.
- VED Vital with no OUT history.
- Limited history / low confidence.
- Smooth frequent demand.
- Lumpy outlier demand.
- Intermittent/Croston-style demand.

```sql
-- Case A: above threshold, no recommendation expected
INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_fst_m10bolt_id, 'in', 500.0000, 'INITIAL_STOCK', 'synthetic_seed', NULL, 'synthetic:above-threshold-initial-stock', @owner_id, '2026-01-01 09:00:00'
WHERE @cmp_fst_m10bolt_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_fst_m10bolt_id, 'out', 20.0000, 'SALE', 'synthetic_order', 10001, 'synthetic:above-threshold-low-demand', @owner_id, '2026-02-01 09:00:00'
WHERE @cmp_fst_m10bolt_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_fst_m10bolt_id, 'out', 25.0000, 'SALE', 'synthetic_order', 10002, 'synthetic:above-threshold-low-demand', @owner_id, '2026-03-01 09:00:00'
WHERE @cmp_fst_m10bolt_id IS NOT NULL;

-- Case B: below threshold, recommendation expected
INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_hyd_flt10_id, 'in', 30.0000, 'INITIAL_STOCK', 'synthetic_seed', NULL, 'synthetic:below-threshold-initial-stock', @owner_id, '2026-01-03 09:00:00'
WHERE @cmp_hyd_flt10_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_hyd_flt10_id, 'out', 8.0000, 'SALE', 'synthetic_order', 10011, 'synthetic:below-threshold-demand', @owner_id, '2026-02-03 09:00:00'
WHERE @cmp_hyd_flt10_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_hyd_flt10_id, 'out', 7.0000, 'SALE', 'synthetic_order', 10012, 'synthetic:below-threshold-demand', @owner_id, '2026-03-03 09:00:00'
WHERE @cmp_hyd_flt10_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_hyd_flt10_id, 'out', 9.0000, 'SALE', 'synthetic_order', 10013, 'synthetic:below-threshold-demand', @owner_id, '2026-04-03 09:00:00'
WHERE @cmp_hyd_flt10_id IS NOT NULL;

-- Case C: zero stock, critical
INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_hyd_rv210_id, 'in', 12.0000, 'INITIAL_STOCK', 'synthetic_seed', NULL, 'synthetic:zero-stock-critical-initial-stock', @owner_id, '2026-01-05 09:00:00'
WHERE @cmp_hyd_rv210_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_hyd_rv210_id, 'out', 4.0000, 'SALE', 'synthetic_order', 10021, 'synthetic:zero-stock-critical-demand', @owner_id, '2026-02-05 09:00:00'
WHERE @cmp_hyd_rv210_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_hyd_rv210_id, 'out', 4.0000, 'SALE', 'synthetic_order', 10022, 'synthetic:zero-stock-critical-demand', @owner_id, '2026-03-05 09:00:00'
WHERE @cmp_hyd_rv210_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_hyd_rv210_id, 'out', 4.0000, 'SALE', 'synthetic_order', 10023, 'synthetic:zero-stock-critical-demand', @owner_id, '2026-04-05 09:00:00'
WHERE @cmp_hyd_rv210_id IS NOT NULL;

-- Case D: Vital with no OUT history
INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @asm_hyd_hpu80_id, 'in', 3.0000, 'INITIAL_STOCK', 'synthetic_seed', NULL, 'synthetic:vital-no-out-history-criticality-only', @owner_id, '2026-01-07 09:00:00'
WHERE @asm_hyd_hpu80_id IS NOT NULL;

-- Case E: limited OUT history / low confidence
INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_seal_mech25_id, 'in', 20.0000, 'INITIAL_STOCK', 'synthetic_seed', NULL, 'synthetic:limited-history-initial-stock', @owner_id, '2026-01-10 09:00:00'
WHERE @cmp_seal_mech25_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_seal_mech25_id, 'out', 5.0000, 'SALE', 'synthetic_order', 10031, 'synthetic:limited-history-low-confidence', @owner_id, '2026-03-10 09:00:00'
WHERE @cmp_seal_mech25_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_seal_mech25_id, 'out', 6.0000, 'SALE', 'synthetic_order', 10032, 'synthetic:limited-history-low-confidence', @owner_id, '2026-04-10 09:00:00'
WHERE @cmp_seal_mech25_id IS NOT NULL;

-- Case F: smooth frequent demand
INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_brg_6205_id, 'in', 60.0000, 'INITIAL_STOCK', 'synthetic_seed', NULL, 'synthetic:smooth-demand-initial-stock', @owner_id, '2025-11-01 09:00:00'
WHERE @cmp_brg_6205_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_brg_6205_id, 'out', 8.0000, 'SALE', 'synthetic_order', 10041, 'synthetic:smooth-demand', @owner_id, '2025-12-01 09:00:00'
WHERE @cmp_brg_6205_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_brg_6205_id, 'out', 7.0000, 'SALE', 'synthetic_order', 10042, 'synthetic:smooth-demand', @owner_id, '2026-01-01 09:00:00'
WHERE @cmp_brg_6205_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_brg_6205_id, 'out', 9.0000, 'SALE', 'synthetic_order', 10043, 'synthetic:smooth-demand', @owner_id, '2026-02-01 09:00:00'
WHERE @cmp_brg_6205_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_brg_6205_id, 'out', 8.0000, 'SALE', 'synthetic_order', 10044, 'synthetic:smooth-demand', @owner_id, '2026-03-01 09:00:00'
WHERE @cmp_brg_6205_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @cmp_brg_6205_id, 'out', 8.0000, 'SALE', 'synthetic_order', 10045, 'synthetic:smooth-demand', @owner_id, '2026-04-01 09:00:00'
WHERE @cmp_brg_6205_id IS NOT NULL;

-- Case G: lumpy demand / outlier
INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @raw_stl_c45_35_id, 'in', 300.0000, 'INITIAL_STOCK', 'synthetic_seed', NULL, 'synthetic:lumpy-outlier-initial-stock', @owner_id, '2025-11-15 09:00:00'
WHERE @raw_stl_c45_35_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @raw_stl_c45_35_id, 'out', 15.0000, 'SALE', 'synthetic_order', 10051, 'synthetic:lumpy-outlier-normal-demand', @owner_id, '2025-12-15 09:00:00'
WHERE @raw_stl_c45_35_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @raw_stl_c45_35_id, 'out', 18.0000, 'SALE', 'synthetic_order', 10052, 'synthetic:lumpy-outlier-normal-demand', @owner_id, '2026-01-15 09:00:00'
WHERE @raw_stl_c45_35_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @raw_stl_c45_35_id, 'out', 120.0000, 'SALE', 'synthetic_order', 10053, 'synthetic:lumpy-outlier-spike-demand', @owner_id, '2026-02-15 09:00:00'
WHERE @raw_stl_c45_35_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @raw_stl_c45_35_id, 'out', 16.0000, 'SALE', 'synthetic_order', 10054, 'synthetic:lumpy-outlier-normal-demand', @owner_id, '2026-03-15 09:00:00'
WHERE @raw_stl_c45_35_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @raw_stl_c45_35_id, 'out', 14.0000, 'SALE', 'synthetic_order', 10055, 'synthetic:lumpy-outlier-normal-demand', @owner_id, '2026-04-15 09:00:00'
WHERE @raw_stl_c45_35_id IS NOT NULL;

-- Case H: intermittent demand / Croston demo
INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @raw_gsk_epdm3_id, 'in', 180.0000, 'INITIAL_STOCK', 'synthetic_seed', NULL, 'synthetic:intermittent-croston-demo-initial-stock', @owner_id, '2025-10-20 09:00:00'
WHERE @raw_gsk_epdm3_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @raw_gsk_epdm3_id, 'out', 20.0000, 'SALE', 'synthetic_order', 10061, 'synthetic:intermittent-croston-demo', @owner_id, '2025-11-20 09:00:00'
WHERE @raw_gsk_epdm3_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @raw_gsk_epdm3_id, 'out', 25.0000, 'SALE', 'synthetic_order', 10062, 'synthetic:intermittent-croston-demo', @owner_id, '2026-01-20 09:00:00'
WHERE @raw_gsk_epdm3_id IS NOT NULL;

INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT @raw_gsk_epdm3_id, 'out', 22.0000, 'SALE', 'synthetic_order', 10063, 'synthetic:intermittent-croston-demo', @owner_id, '2026-04-20 09:00:00'
WHERE @raw_gsk_epdm3_id IS NOT NULL;
```

---

### 4.6 RFQ requests

```sql
-- Open RFQ
INSERT INTO rfq_requests
(client_id, component_id, supplier_id, quantity_requested, status, client_message, quoted_price, supplier_message, deadline_date, revision_id, is_blind, auto_triggered, decision_note, decided_by, decided_at, created_at, updated_at)
SELECT @client_casablanca_id, @cmp_hyd_flt10_id, @supplier_casatech_id, 25.0000, 'open',
'synthetic:rfq-demo:open-filter-restock - Need hydraulic filter elements for HPU maintenance planning.',
NULL, NULL, '2026-05-20', 1, 0, 0, NULL, NULL, NULL, '2026-05-03 09:00:00', '2026-05-03 09:00:00'
WHERE @cmp_hyd_flt10_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM rfq_requests WHERE client_message LIKE 'synthetic:rfq-demo:open-filter-restock%');

-- Quoted RFQ
INSERT INTO rfq_requests
(client_id, component_id, supplier_id, quantity_requested, status, client_message, quoted_price, supplier_message, deadline_date, revision_id, is_blind, auto_triggered, decision_note, decided_by, decided_at, created_at, updated_at)
SELECT @client_atlas_maintenance_id, @cmp_seal_mech25_id, @supplier_iberica_id, 12.0000, 'quoted',
'synthetic:rfq-demo:quoted-mechanical-seal - Requesting quote for urgent pump seal replacement stock.',
310.00, 'Synthetic supplier quote: 310.00 MAD per unit, available in 7 working days.', '2026-05-18', 1, 0, 0, NULL, NULL, NULL, '2026-05-02 10:00:00', '2026-05-03 11:30:00'
WHERE @cmp_seal_mech25_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM rfq_requests WHERE client_message LIKE 'synthetic:rfq-demo:quoted-mechanical-seal%');

-- Accepted bearing RFQ
INSERT INTO rfq_requests
(client_id, component_id, supplier_id, quantity_requested, status, client_message, quoted_price, supplier_message, deadline_date, revision_id, is_blind, auto_triggered, decision_note, decided_by, decided_at, created_at, updated_at)
SELECT @client_tanger_automotive_id, @cmp_brg_6205_id, @supplier_rhein_precision_id, 80.0000, 'accepted',
'synthetic:rfq-demo:accepted-bearing-order - Bearing replenishment for conveyor maintenance plan.',
44.50, 'Synthetic supplier quote: 44.50 MAD per bearing, batch quantity available immediately.', '2026-05-15', 1, 0, 0,
'Accepted for replenishment. Stock movement must happen only after purchase lot finalization.',
@owner_id, '2026-05-03 12:00:00', '2026-05-01 10:00:00', '2026-05-03 12:00:00'
WHERE @cmp_brg_6205_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM rfq_requests WHERE client_message LIKE 'synthetic:rfq-demo:accepted-bearing-order%');

-- Accepted critical relief valve RFQ
INSERT INTO rfq_requests
(client_id, component_id, supplier_id, quantity_requested, status, client_message, quoted_price, supplier_message, deadline_date, revision_id, is_blind, auto_triggered, decision_note, decided_by, decided_at, created_at, updated_at)
SELECT @client_ahmed_id, @cmp_hyd_rv210_id, @supplier_casatech_id, 15.0000, 'accepted',
'synthetic:rfq-demo:accepted-critical-relief-valve - Critical hydraulic relief valve replenishment after zero stock alert.',
780.00, 'Synthetic supplier quote: 780.00 MAD per valve, expedited delivery available.', '2026-05-10', 1, 0, 1,
'Accepted because the part is VED Vital and stock is critical.',
@owner_id, '2026-05-03 12:30:00', '2026-05-01 09:30:00', '2026-05-03 12:30:00'
WHERE @cmp_hyd_rv210_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM rfq_requests WHERE client_message LIKE 'synthetic:rfq-demo:accepted-critical-relief-valve%');

-- Rejected RFQ
INSERT INTO rfq_requests
(client_id, component_id, supplier_id, quantity_requested, status, client_message, quoted_price, supplier_message, deadline_date, revision_id, is_blind, auto_triggered, decision_note, decided_by, decided_at, created_at, updated_at)
SELECT @client_meknes_agro_id, @asm_hyd_hpu80_id, @supplier_maghreb_pumps_id, 2.0000, 'rejected',
'synthetic:rfq-demo:rejected-hydraulic-power-unit - Request for hydraulic power unit replacement.',
42500.00, 'Synthetic supplier quote: price high due to imported motor-pump package.', '2026-05-12', 1, 0, 0,
'Rejected for demo: price exceeds current procurement budget.',
@owner_id, '2026-05-03 13:00:00', '2026-05-01 14:00:00', '2026-05-03 13:00:00'
WHERE @asm_hyd_hpu80_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM rfq_requests WHERE client_message LIKE 'synthetic:rfq-demo:rejected-hydraulic-power-unit%');

-- Expired RFQ
INSERT INTO rfq_requests
(client_id, component_id, supplier_id, quantity_requested, status, client_message, quoted_price, supplier_message, deadline_date, revision_id, is_blind, auto_triggered, decision_note, decided_by, decided_at, created_at, updated_at)
SELECT @client_casablanca_id, @raw_gsk_epdm3_id, @supplier_iberica_id, 50.0000, 'expired',
'synthetic:rfq-demo:expired-epdm-gasket-sheet - Request for EPDM gasket sheets, supplier did not respond before deadline.',
NULL, NULL, '2026-04-25', 1, 0, 0,
'Expired because deadline passed without supplier quote.',
@owner_id, '2026-04-26 09:00:00', '2026-04-20 09:00:00', '2026-04-26 09:00:00'
WHERE @raw_gsk_epdm3_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM rfq_requests WHERE client_message LIKE 'synthetic:rfq-demo:expired-epdm-gasket-sheet%');
```

---

### 4.7 RFQ revisions

```sql
INSERT INTO rfq_revisions
(rfq_id, revision_id, supplier_id, quoted_price, lead_time_days, supplier_note, created_at)
SELECT r.id, 1, r.supplier_id, 310.00, 7,
'synthetic:rfq-revision:initial quote for mechanical seals. Supplier can deliver from available sealing inventory.',
'2026-05-03 11:30:00'
FROM rfq_requests r
WHERE r.client_message LIKE 'synthetic:rfq-demo:quoted-mechanical-seal%'
  AND r.supplier_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM rfq_revisions rr
    WHERE rr.rfq_id = r.id AND rr.revision_id = 1
      AND rr.supplier_note LIKE 'synthetic:rfq-revision:initial quote for mechanical seals%'
  );

INSERT INTO rfq_revisions
(rfq_id, revision_id, supplier_id, quoted_price, lead_time_days, supplier_note, created_at)
SELECT r.id, 1, r.supplier_id, 44.50, 5,
'synthetic:rfq-revision:accepted bearing quote. Supplier confirms batch availability for conveyor maintenance replenishment.',
'2026-05-03 11:45:00'
FROM rfq_requests r
WHERE r.client_message LIKE 'synthetic:rfq-demo:accepted-bearing-order%'
  AND r.supplier_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM rfq_revisions rr
    WHERE rr.rfq_id = r.id AND rr.revision_id = 1
      AND rr.supplier_note LIKE 'synthetic:rfq-revision:accepted bearing quote%'
  );

INSERT INTO rfq_revisions
(rfq_id, revision_id, supplier_id, quoted_price, lead_time_days, supplier_note, created_at)
SELECT r.id, 1, r.supplier_id, 780.00, 3,
'synthetic:rfq-revision:critical relief valve quote. Expedited delivery available due to VED Vital classification.',
'2026-05-03 12:15:00'
FROM rfq_requests r
WHERE r.client_message LIKE 'synthetic:rfq-demo:accepted-critical-relief-valve%'
  AND r.supplier_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM rfq_revisions rr
    WHERE rr.rfq_id = r.id AND rr.revision_id = 1
      AND rr.supplier_note LIKE 'synthetic:rfq-revision:critical relief valve quote%'
  );

INSERT INTO rfq_revisions
(rfq_id, revision_id, supplier_id, quoted_price, lead_time_days, supplier_note, created_at)
SELECT r.id, 1, r.supplier_id, 42500.00, 21,
'synthetic:rfq-revision:hydraulic power unit quote. High price due to imported motor-pump package and long lead time.',
'2026-05-02 15:00:00'
FROM rfq_requests r
WHERE r.client_message LIKE 'synthetic:rfq-demo:rejected-hydraulic-power-unit%'
  AND r.supplier_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM rfq_revisions rr
    WHERE rr.rfq_id = r.id AND rr.revision_id = 1
      AND rr.supplier_note LIKE 'synthetic:rfq-revision:hydraulic power unit quote%'
  );

-- Second revision for negotiation history
INSERT INTO rfq_revisions
(rfq_id, revision_id, supplier_id, quoted_price, lead_time_days, supplier_note, created_at)
SELECT r.id, 2, r.supplier_id, 42.75, 5,
'synthetic:rfq-revision:revised bearing quote after owner negotiation. Slight discount applied for batch quantity.',
'2026-05-03 12:00:00'
FROM rfq_requests r
WHERE r.client_message LIKE 'synthetic:rfq-demo:accepted-bearing-order%'
  AND r.supplier_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM rfq_revisions rr
    WHERE rr.rfq_id = r.id AND rr.revision_id = 2
      AND rr.supplier_note LIKE 'synthetic:rfq-revision:revised bearing quote%'
  );

UPDATE rfq_requests
SET
  revision_id = 2,
  quoted_price = 42.75,
  supplier_message = 'Synthetic supplier revised quote: 42.75 MAD per bearing after batch negotiation.',
  updated_at = '2026-05-03 12:00:00'
WHERE client_message LIKE 'synthetic:rfq-demo:accepted-bearing-order%';
```

---

### 4.8 Purchase lots linked to accepted RFQs

```sql
-- Purchase lot for accepted bearing RFQ
INSERT INTO purchase_lots
(component_id, supplier_id, quantity_received, supplier_unit_price, supplier_total_price, transport_cost, customs_cost, handling_cost, packaging_cost, order_preparation_cost, other_cost, total_purchase_cost, unit_purchase_cost, status, purchase_date, reference_type, reference_id, notes, created_by, created_at)
SELECT r.component_id, r.supplier_id, 80.0000, 44.50, 3560.00, 250.00, 120.00, 50.00, 30.00, 40.00, 0.00, 4050.00, 50.6250, 'finalized', '2026-05-04', 'rfq_request', r.id,
'synthetic:rfq-demo:purchase-lot:accepted-bearing-order', @owner_id, '2026-05-04 10:00:00'
FROM rfq_requests r
WHERE r.client_message LIKE 'synthetic:rfq-demo:accepted-bearing-order%'
  AND r.status = 'accepted'
  AND NOT EXISTS (
    SELECT 1 FROM purchase_lots pl
    WHERE pl.reference_type = 'rfq_request'
      AND pl.reference_id = r.id
      AND pl.notes = 'synthetic:rfq-demo:purchase-lot:accepted-bearing-order'
  );

-- Purchase lot for accepted critical relief valve RFQ
INSERT INTO purchase_lots
(component_id, supplier_id, quantity_received, supplier_unit_price, supplier_total_price, transport_cost, customs_cost, handling_cost, packaging_cost, order_preparation_cost, other_cost, total_purchase_cost, unit_purchase_cost, status, purchase_date, reference_type, reference_id, notes, created_by, created_at)
SELECT r.component_id, r.supplier_id, 15.0000, 780.00, 11700.00, 600.00, 0.00, 150.00, 80.00, 70.00, 0.00, 12600.00, 840.0000, 'finalized', '2026-05-04', 'rfq_request', r.id,
'synthetic:rfq-demo:purchase-lot:accepted-critical-relief-valve', @owner_id, '2026-05-04 10:30:00'
FROM rfq_requests r
WHERE r.client_message LIKE 'synthetic:rfq-demo:accepted-critical-relief-valve%'
  AND r.status = 'accepted'
  AND NOT EXISTS (
    SELECT 1 FROM purchase_lots pl
    WHERE pl.reference_type = 'rfq_request'
      AND pl.reference_id = r.id
      AND pl.notes = 'synthetic:rfq-demo:purchase-lot:accepted-critical-relief-valve'
  );
```

### 4.9 Stock IN from finalized purchase lots

```sql
INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT
  pl.component_id,
  'in',
  pl.quantity_received,
  'PURCHASE_RECEIVED',
  'purchase_lot',
  pl.id,
  CONCAT('synthetic:rfq-demo:stock-in:purchase-lot:', pl.id),
  @owner_id,
  pl.created_at
FROM purchase_lots pl
WHERE pl.notes IN (
  'synthetic:rfq-demo:purchase-lot:accepted-bearing-order',
  'synthetic:rfq-demo:purchase-lot:accepted-critical-relief-valve'
)
  AND pl.status = 'finalized'
  AND NOT EXISTS (
    SELECT 1 FROM stock_movements sm
    WHERE sm.reference_type = 'purchase_lot'
      AND sm.reference_id = pl.id
      AND sm.reason = 'PURCHASE_RECEIVED'
  );
```

---

### 4.10 Orders and order items

```sql
-- Delivered hydraulic filters order
INSERT INTO orders
(client_id, status, stripe_payment_id, total_amount, shipping_address, notes, created_at, updated_at)
SELECT @client_casablanca_id, 'delivered', 'synthetic_pi_order_hyd_filters_001', 1800.00,
'Zone Industrielle Sidi Bernoussi, Casablanca, Morocco',
'synthetic:order-demo:delivered-hydraulic-filters',
'2026-05-01 09:00:00', '2026-05-03 15:00:00'
WHERE NOT EXISTS (SELECT 1 FROM orders WHERE notes = 'synthetic:order-demo:delivered-hydraulic-filters');

-- Delivered bearings order
INSERT INTO orders
(client_id, status, stripe_payment_id, total_amount, shipping_address, notes, created_at, updated_at)
SELECT @client_tanger_automotive_id, 'delivered', 'synthetic_pi_order_bearings_001', 1375.00,
'Tanger Automotive Free Zone, Tangier, Morocco',
'synthetic:order-demo:delivered-bearings',
'2026-05-01 10:00:00', '2026-05-03 16:00:00'
WHERE NOT EXISTS (SELECT 1 FROM orders WHERE notes = 'synthetic:order-demo:delivered-bearings');

-- Cancelled mechanical seals order
INSERT INTO orders
(client_id, status, stripe_payment_id, total_amount, shipping_address, notes, created_at, updated_at)
SELECT @client_atlas_maintenance_id, 'cancelled', 'synthetic_pi_order_cancelled_seals_001', 1550.00,
'Maintenance Depot Ain Sebaa, Casablanca, Morocco',
'synthetic:order-demo:cancelled-mechanical-seals',
'2026-05-02 09:30:00', '2026-05-03 14:00:00'
WHERE NOT EXISTS (SELECT 1 FROM orders WHERE notes = 'synthetic:order-demo:cancelled-mechanical-seals');

-- Pending EPDM gasket order
INSERT INTO orders
(client_id, status, stripe_payment_id, total_amount, shipping_address, notes, created_at, updated_at)
SELECT @client_meknes_agro_id, 'pending', NULL, 2250.00,
'Agro Industrial Zone, Meknes, Morocco',
'synthetic:order-demo:pending-epdm-gasket-sheets',
'2026-05-03 09:00:00', '2026-05-03 09:00:00'
WHERE NOT EXISTS (SELECT 1 FROM orders WHERE notes = 'synthetic:order-demo:pending-epdm-gasket-sheets');

-- Shipped relief valves order
INSERT INTO orders
(client_id, status, stripe_payment_id, total_amount, shipping_address, notes, created_at, updated_at)
SELECT @client_ahmed_id, 'shipped', 'synthetic_pi_order_relief_valves_001', 2340.00,
'PME Industrie Casablanca, Casablanca, Morocco',
'synthetic:order-demo:shipped-relief-valves',
'2026-05-02 11:00:00', '2026-05-03 13:00:00'
WHERE NOT EXISTS (SELECT 1 FROM orders WHERE notes = 'synthetic:order-demo:shipped-relief-valves');

-- Order items
INSERT INTO order_items
(order_id, component_id, quantity, unit_price, supplier_id, created_at)
SELECT o.id, @cmp_hyd_flt10_id, 12.0000, 150.00, @supplier_casatech_id, '2026-05-01 09:05:00'
FROM orders o
WHERE o.notes = 'synthetic:order-demo:delivered-hydraulic-filters'
  AND @cmp_hyd_flt10_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.component_id = @cmp_hyd_flt10_id);

INSERT INTO order_items
(order_id, component_id, quantity, unit_price, supplier_id, created_at)
SELECT o.id, @cmp_brg_6205_id, 25.0000, 55.00, @supplier_rhein_precision_id, '2026-05-01 10:05:00'
FROM orders o
WHERE o.notes = 'synthetic:order-demo:delivered-bearings'
  AND @cmp_brg_6205_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.component_id = @cmp_brg_6205_id);

INSERT INTO order_items
(order_id, component_id, quantity, unit_price, supplier_id, created_at)
SELECT o.id, @cmp_seal_mech25_id, 5.0000, 310.00, @supplier_iberica_id, '2026-05-02 09:35:00'
FROM orders o
WHERE o.notes = 'synthetic:order-demo:cancelled-mechanical-seals'
  AND @cmp_seal_mech25_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.component_id = @cmp_seal_mech25_id);

INSERT INTO order_items
(order_id, component_id, quantity, unit_price, supplier_id, created_at)
SELECT o.id, @raw_gsk_epdm3_id, 30.0000, 75.00, @supplier_iberica_id, '2026-05-03 09:05:00'
FROM orders o
WHERE o.notes = 'synthetic:order-demo:pending-epdm-gasket-sheets'
  AND @raw_gsk_epdm3_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.component_id = @raw_gsk_epdm3_id);

INSERT INTO order_items
(order_id, component_id, quantity, unit_price, supplier_id, created_at)
SELECT o.id, @cmp_hyd_rv210_id, 3.0000, 780.00, @supplier_casatech_id, '2026-05-02 11:05:00'
FROM orders o
WHERE o.notes = 'synthetic:order-demo:shipped-relief-valves'
  AND @cmp_hyd_rv210_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.component_id = @cmp_hyd_rv210_id);
```

### 4.11 Order stock movements

```sql
-- SALE OUT for delivered orders
INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT oi.component_id, 'out', oi.quantity, 'SALE', 'order', o.id,
CONCAT('synthetic:order-demo:sale-out:delivered-order:', o.id),
@owner_id, o.updated_at
FROM orders o
JOIN order_items oi ON oi.order_id = o.id
WHERE o.notes IN (
  'synthetic:order-demo:delivered-hydraulic-filters',
  'synthetic:order-demo:delivered-bearings'
)
  AND o.status = 'delivered'
  AND NOT EXISTS (
    SELECT 1 FROM stock_movements sm
    WHERE sm.reference_type = 'order'
      AND sm.reference_id = o.id
      AND sm.component_id = oi.component_id
      AND sm.reason = 'SALE'
      AND sm.notes LIKE 'synthetic:order-demo:sale-out:%'
  );

-- Cancelled order original SALE OUT
INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT oi.component_id, 'out', oi.quantity, 'SALE', 'order', o.id,
CONCAT('synthetic:order-demo:sale-out-before-cancel:', o.id),
@owner_id, '2026-05-02 10:00:00'
FROM orders o
JOIN order_items oi ON oi.order_id = o.id
WHERE o.notes = 'synthetic:order-demo:cancelled-mechanical-seals'
  AND o.status = 'cancelled'
  AND NOT EXISTS (
    SELECT 1 FROM stock_movements sm
    WHERE sm.reference_type = 'order'
      AND sm.reference_id = o.id
      AND sm.component_id = oi.component_id
      AND sm.reason = 'SALE'
      AND sm.notes LIKE 'synthetic:order-demo:sale-out-before-cancel:%'
  );

-- Cancelled order restore movement
INSERT INTO stock_movements
(component_id, type, quantity, reason, reference_type, reference_id, notes, created_by, created_at)
SELECT oi.component_id, 'in', oi.quantity, 'CANCELLED_ORDER_RESTORE', 'order', o.id,
CONCAT('synthetic:order-demo:cancelled-restore:', o.id),
@owner_id, o.updated_at
FROM orders o
JOIN order_items oi ON oi.order_id = o.id
WHERE o.notes = 'synthetic:order-demo:cancelled-mechanical-seals'
  AND o.status = 'cancelled'
  AND NOT EXISTS (
    SELECT 1 FROM stock_movements sm
    WHERE sm.reference_type = 'order'
      AND sm.reference_id = o.id
      AND sm.component_id = oi.component_id
      AND sm.reason = 'CANCELLED_ORDER_RESTORE'
      AND sm.notes LIKE 'synthetic:order-demo:cancelled-restore:%'
  );
```

---

### 4.12 Dependencies

```sql
-- Technical structure
INSERT INTO dependencies
(parent_id, child_id, qty_required, uom, is_phantom, notes, relation_type, created_at, updated_at)
SELECT @asm_pmp_xr200_id, @sub_pmp_wet50_id, 1.0000, 'pcs', 0,
'synthetic:dependency-demo:technical-structure:xr200-wet-end-module',
'technical_structure', '2026-05-03 10:00:00', '2026-05-03 10:00:00'
WHERE @asm_pmp_xr200_id IS NOT NULL AND @sub_pmp_wet50_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM dependencies WHERE parent_id = @asm_pmp_xr200_id AND child_id = @sub_pmp_wet50_id AND relation_type = 'technical_structure');

INSERT INTO dependencies
(parent_id, child_id, qty_required, uom, is_phantom, notes, relation_type, created_at, updated_at)
SELECT @asm_pmp_xr200_id, @sub_mtr_pkg75_id, 1.0000, 'pcs', 0,
'synthetic:dependency-demo:technical-structure:xr200-motor-package',
'technical_structure', '2026-05-03 10:01:00', '2026-05-03 10:01:00'
WHERE @asm_pmp_xr200_id IS NOT NULL AND @sub_mtr_pkg75_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM dependencies WHERE parent_id = @asm_pmp_xr200_id AND child_id = @sub_mtr_pkg75_id AND relation_type = 'technical_structure');

INSERT INTO dependencies
(parent_id, child_id, qty_required, uom, is_phantom, notes, relation_type, created_at, updated_at)
SELECT @asm_hyd_hpu80_id, @sub_hyd_pump11_id, 1.0000, 'pcs', 0,
'synthetic:dependency-demo:technical-structure:hpu80-hydraulic-pump-subassembly',
'technical_structure', '2026-05-03 10:02:00', '2026-05-03 10:02:00'
WHERE @asm_hyd_hpu80_id IS NOT NULL AND @sub_hyd_pump11_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM dependencies WHERE parent_id = @asm_hyd_hpu80_id AND child_id = @sub_hyd_pump11_id AND relation_type = 'technical_structure');

-- Spare parts
INSERT INTO dependencies
(parent_id, child_id, qty_required, uom, is_phantom, notes, relation_type, created_at, updated_at)
SELECT @asm_pmp_xr200_id, @cmp_seal_mech25_id, 1.0000, 'pcs', 0,
'synthetic:dependency-demo:spare-part:xr200-mechanical-seal',
'spare_part', '2026-05-03 10:03:00', '2026-05-03 10:03:00'
WHERE @asm_pmp_xr200_id IS NOT NULL AND @cmp_seal_mech25_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM dependencies WHERE parent_id = @asm_pmp_xr200_id AND child_id = @cmp_seal_mech25_id AND relation_type = 'spare_part');

INSERT INTO dependencies
(parent_id, child_id, qty_required, uom, is_phantom, notes, relation_type, created_at, updated_at)
SELECT @asm_hyd_hpu80_id, @cmp_hyd_flt10_id, 2.0000, 'pcs', 0,
'synthetic:dependency-demo:spare-part:hpu80-hydraulic-filter-elements',
'spare_part', '2026-05-03 10:04:00', '2026-05-03 10:04:00'
WHERE @asm_hyd_hpu80_id IS NOT NULL AND @cmp_hyd_flt10_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM dependencies WHERE parent_id = @asm_hyd_hpu80_id AND child_id = @cmp_hyd_flt10_id AND relation_type = 'spare_part');

INSERT INTO dependencies
(parent_id, child_id, qty_required, uom, is_phantom, notes, relation_type, created_at, updated_at)
SELECT @asm_hyd_hpu80_id, @cmp_hyd_rv210_id, 1.0000, 'pcs', 0,
'synthetic:dependency-demo:spare-part:hpu80-relief-valve',
'spare_part', '2026-05-03 10:05:00', '2026-05-03 10:05:00'
WHERE @asm_hyd_hpu80_id IS NOT NULL AND @cmp_hyd_rv210_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM dependencies WHERE parent_id = @asm_hyd_hpu80_id AND child_id = @cmp_hyd_rv210_id AND relation_type = 'spare_part');

-- Compatible alternatives / related relationships
INSERT INTO dependencies
(parent_id, child_id, qty_required, uom, is_phantom, notes, relation_type, created_at, updated_at)
SELECT @asm_pmp_xr200_id, @asm_pmp_xr300_id, 1.0000, 'pcs', 0,
'synthetic:dependency-demo:compatible-alternative:xr300-upgrade-path-for-xr200',
'compatible_alternative', '2026-05-03 10:06:00', '2026-05-03 10:06:00'
WHERE @asm_pmp_xr200_id IS NOT NULL AND @asm_pmp_xr300_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM dependencies WHERE parent_id = @asm_pmp_xr200_id AND child_id = @asm_pmp_xr300_id AND relation_type = 'compatible_alternative');

INSERT INTO dependencies
(parent_id, child_id, qty_required, uom, is_phantom, notes, relation_type, created_at, updated_at)
SELECT @cmp_seal_mech25_id, @cmp_oring_epdm_kit_id, 1.0000, 'kit', 0,
'synthetic:dependency-demo:compatible-part:seal-maintenance-oring-kit',
'compatible_part', '2026-05-03 10:07:00', '2026-05-03 10:07:00'
WHERE @cmp_seal_mech25_id IS NOT NULL AND @cmp_oring_epdm_kit_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM dependencies WHERE parent_id = @cmp_seal_mech25_id AND child_id = @cmp_oring_epdm_kit_id AND relation_type = 'compatible_part');

INSERT INTO dependencies
(parent_id, child_id, qty_required, uom, is_phantom, notes, relation_type, created_at, updated_at)
SELECT @cmp_hyd_flt10_id, @raw_gsk_epdm3_id, 1.0000, 'pcs', 0,
'synthetic:dependency-demo:frequently-bought-together:filter-and-gasket-sheet-maintenance-bundle',
'frequently_bought_together', '2026-05-03 10:08:00', '2026-05-03 10:08:00'
WHERE @cmp_hyd_flt10_id IS NOT NULL AND @raw_gsk_epdm3_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM dependencies WHERE parent_id = @cmp_hyd_flt10_id AND child_id = @raw_gsk_epdm3_id AND relation_type = 'frequently_bought_together');

INSERT INTO dependencies
(parent_id, child_id, qty_required, uom, is_phantom, notes, relation_type, created_at, updated_at)
SELECT @cmp_hyd_gp11_id, @cmp_hyd_flt10_id, 2.0000, 'pcs', 0,
'synthetic:dependency-demo:frequently-bought-together:hydraulic-pump-and-filter-service-bundle',
'frequently_bought_together', '2026-05-03 10:09:00', '2026-05-03 10:09:00'
WHERE @cmp_hyd_gp11_id IS NOT NULL AND @cmp_hyd_flt10_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM dependencies WHERE parent_id = @cmp_hyd_gp11_id AND child_id = @cmp_hyd_flt10_id AND relation_type = 'frequently_bought_together');
```

---

### 4.13 Part sources

```sql
-- Hydraulic parts
INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @cmp_hyd_flt10_id, @supplier_casatech_id, 'SYN-CASA-HYD-FLT10', 120.00, 6, 5.0000, 1, '2026-01-01', NULL, '2026-05-03 10:00:00', '2026-05-03 10:00:00'
WHERE @cmp_hyd_flt10_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @cmp_hyd_flt10_id AND supplier_id = @supplier_casatech_id AND supplier_part_num = 'SYN-CASA-HYD-FLT10');

INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @cmp_hyd_flt10_id, @supplier_lombardia_id, 'SYN-LOMB-HYD-FLT10-EU', 135.00, 12, 10.0000, 0, '2026-01-01', NULL, '2026-05-03 10:01:00', '2026-05-03 10:01:00'
WHERE @cmp_hyd_flt10_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @cmp_hyd_flt10_id AND supplier_id = @supplier_lombardia_id AND supplier_part_num = 'SYN-LOMB-HYD-FLT10-EU');

INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @cmp_hyd_rv210_id, @supplier_casatech_id, 'SYN-CASA-HYD-RV210', 780.00, 3, 1.0000, 1, '2026-01-01', NULL, '2026-05-03 10:02:00', '2026-05-03 10:02:00'
WHERE @cmp_hyd_rv210_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @cmp_hyd_rv210_id AND supplier_id = @supplier_casatech_id AND supplier_part_num = 'SYN-CASA-HYD-RV210');

INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @cmp_hyd_gp11_id, @supplier_lombardia_id, 'SYN-LOMB-HYD-GP11', 2450.00, 14, 1.0000, 1, '2026-01-01', NULL, '2026-05-03 10:03:00', '2026-05-03 10:03:00'
WHERE @cmp_hyd_gp11_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @cmp_hyd_gp11_id AND supplier_id = @supplier_lombardia_id AND supplier_part_num = 'SYN-LOMB-HYD-GP11');

-- Bearings
INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @cmp_brg_6205_id, @supplier_rhein_precision_id, 'SYN-RHEIN-BRG-6205-2RS', 42.00, 5, 20.0000, 1, '2026-01-01', NULL, '2026-05-03 10:04:00', '2026-05-03 10:04:00'
WHERE @cmp_brg_6205_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @cmp_brg_6205_id AND supplier_id = @supplier_rhein_precision_id AND supplier_part_num = 'SYN-RHEIN-BRG-6205-2RS');

INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @cmp_brg_6205_id, @supplier_nordmeca_id, 'SYN-NORD-BRG-6205-GEN', 48.00, 2, 10.0000, 0, '2026-01-01', NULL, '2026-05-03 10:05:00', '2026-05-03 10:05:00'
WHERE @cmp_brg_6205_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @cmp_brg_6205_id AND supplier_id = @supplier_nordmeca_id AND supplier_part_num = 'SYN-NORD-BRG-6205-GEN');

INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @cmp_brg_ucp207_id, @supplier_rhein_precision_id, 'SYN-RHEIN-UCP207', 165.00, 8, 4.0000, 1, '2026-01-01', NULL, '2026-05-03 10:06:00', '2026-05-03 10:06:00'
WHERE @cmp_brg_ucp207_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @cmp_brg_ucp207_id AND supplier_id = @supplier_rhein_precision_id AND supplier_part_num = 'SYN-RHEIN-UCP207');

-- Seals and gasket materials
INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @cmp_seal_mech25_id, @supplier_iberica_id, 'SYN-IBER-SEAL-MECH25', 290.00, 7, 2.0000, 1, '2026-01-01', NULL, '2026-05-03 10:07:00', '2026-05-03 10:07:00'
WHERE @cmp_seal_mech25_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @cmp_seal_mech25_id AND supplier_id = @supplier_iberica_id AND supplier_part_num = 'SYN-IBER-SEAL-MECH25');

INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @cmp_oring_epdm_kit_id, @supplier_iberica_id, 'SYN-IBER-ORING-EPDM-KIT', 95.00, 6, 3.0000, 1, '2026-01-01', NULL, '2026-05-03 10:08:00', '2026-05-03 10:08:00'
WHERE @cmp_oring_epdm_kit_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @cmp_oring_epdm_kit_id AND supplier_id = @supplier_iberica_id AND supplier_part_num = 'SYN-IBER-ORING-EPDM-KIT');

INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @raw_gsk_epdm3_id, @supplier_iberica_id, 'SYN-IBER-RAW-EPDM3', 52.00, 10, 10.0000, 1, '2026-01-01', NULL, '2026-05-03 10:09:00', '2026-05-03 10:09:00'
WHERE @raw_gsk_epdm3_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @raw_gsk_epdm3_id AND supplier_id = @supplier_iberica_id AND supplier_part_num = 'SYN-IBER-RAW-EPDM3');

-- Fasteners and raw materials
INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @cmp_fst_m10bolt_id, @supplier_atlas_id, 'SYN-ATLAS-M10-BOLT', 1.80, 2, 100.0000, 1, '2026-01-01', NULL, '2026-05-03 10:10:00', '2026-05-03 10:10:00'
WHERE @cmp_fst_m10bolt_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @cmp_fst_m10bolt_id AND supplier_id = @supplier_atlas_id AND supplier_part_num = 'SYN-ATLAS-M10-BOLT');

INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @cmp_fst_m10wash_id, @supplier_atlas_id, 'SYN-ATLAS-M10-WASHER', 0.45, 2, 100.0000, 1, '2026-01-01', NULL, '2026-05-03 10:11:00', '2026-05-03 10:11:00'
WHERE @cmp_fst_m10wash_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @cmp_fst_m10wash_id AND supplier_id = @supplier_atlas_id AND supplier_part_num = 'SYN-ATLAS-M10-WASHER');

INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @raw_stl_c45_35_id, @supplier_tangier_mechanical_id, 'SYN-TMW-C45-35MM-BAR', 38.00, 9, 25.0000, 1, '2026-01-01', NULL, '2026-05-03 10:12:00', '2026-05-03 10:12:00'
WHERE @raw_stl_c45_35_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @raw_stl_c45_35_id AND supplier_id = @supplier_tangier_mechanical_id AND supplier_part_num = 'SYN-TMW-C45-35MM-BAR');

-- Assemblies
INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @asm_pmp_xr200_id, @supplier_maghreb_pumps_id, 'SYN-MAGHREB-ASM-XR200', 18500.00, 18, 1.0000, 1, '2026-01-01', NULL, '2026-05-03 10:13:00', '2026-05-03 10:13:00'
WHERE @asm_pmp_xr200_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @asm_pmp_xr200_id AND supplier_id = @supplier_maghreb_pumps_id AND supplier_part_num = 'SYN-MAGHREB-ASM-XR200');

INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @asm_pmp_xr300_id, @supplier_maghreb_pumps_id, 'SYN-MAGHREB-ASM-XR300', 24750.00, 24, 1.0000, 1, '2026-01-01', NULL, '2026-05-03 10:14:00', '2026-05-03 10:14:00'
WHERE @asm_pmp_xr300_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @asm_pmp_xr300_id AND supplier_id = @supplier_maghreb_pumps_id AND supplier_part_num = 'SYN-MAGHREB-ASM-XR300');

INSERT INTO part_sources
(component_id, supplier_id, supplier_part_num, unit_cost, lead_time_days, min_order_qty, is_preferred, valid_from, valid_to, created_at, updated_at)
SELECT @asm_hyd_hpu80_id, @supplier_casatech_id, 'SYN-CASA-ASM-HPU80', 42500.00, 21, 1.0000, 1, '2026-01-01', NULL, '2026-05-03 10:15:00', '2026-05-03 10:15:00'
WHERE @asm_hyd_hpu80_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM part_sources WHERE component_id = @asm_hyd_hpu80_id AND supplier_id = @supplier_casatech_id AND supplier_part_num = 'SYN-CASA-ASM-HPU80');
```

---

### 4.14 Notifications

```sql
-- RFQ assigned to fournisseur
INSERT INTO notifications
(user_id, role, type, title, message, reference_type, reference_id, is_read, created_at, read_at)
SELECT @supplier_casatech_user_id, 'fournisseur', 'RFQ_ASSIGNED', 'New RFQ assigned',
CONCAT('synthetic:notification-demo:rfq-assigned - You have been assigned RFQ #', r.id, ' for hydraulic filter elements.'),
'rfq_request', r.id, 0, '2026-05-03 09:05:00', NULL
FROM rfq_requests r
WHERE r.client_message LIKE 'synthetic:rfq-demo:open-filter-restock%'
  AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.type = 'RFQ_ASSIGNED' AND n.reference_type = 'rfq_request' AND n.reference_id = r.id AND n.message LIKE 'synthetic:notification-demo:rfq-assigned%');

-- RFQ quoted notification to owner
INSERT INTO notifications
(user_id, role, type, title, message, reference_type, reference_id, is_read, created_at, read_at)
SELECT @owner_id, 'owner', 'RFQ_QUOTED', 'Supplier submitted a quote',
CONCAT('synthetic:notification-demo:rfq-quoted - Supplier submitted a quote for RFQ #', r.id, '.'),
'rfq_request', r.id, 0, '2026-05-03 11:35:00', NULL
FROM rfq_requests r
WHERE r.client_message LIKE 'synthetic:rfq-demo:quoted-mechanical-seal%'
  AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.type = 'RFQ_QUOTED' AND n.reference_type = 'rfq_request' AND n.reference_id = r.id AND n.message LIKE 'synthetic:notification-demo:rfq-quoted%');

-- RFQ accepted notification to fournisseur
INSERT INTO notifications
(user_id, role, type, title, message, reference_type, reference_id, is_read, created_at, read_at)
SELECT @supplier_rhein_precision_user_id, 'fournisseur', 'RFQ_ACCEPTED_BY_OWNER', 'RFQ accepted by owner',
CONCAT('synthetic:notification-demo:rfq-accepted - Your bearing quote was accepted for RFQ #', r.id, '.'),
'rfq_request', r.id, 0, '2026-05-03 12:05:00', NULL
FROM rfq_requests r
WHERE r.client_message LIKE 'synthetic:rfq-demo:accepted-bearing-order%'
  AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.type = 'RFQ_ACCEPTED_BY_OWNER' AND n.reference_type = 'rfq_request' AND n.reference_id = r.id AND n.message LIKE 'synthetic:notification-demo:rfq-accepted%');

-- RFQ rejected notification to fournisseur
INSERT INTO notifications
(user_id, role, type, title, message, reference_type, reference_id, is_read, created_at, read_at)
SELECT @supplier_maghreb_pumps_user_id, 'fournisseur', 'RFQ_REJECTED_BY_OWNER', 'RFQ rejected by owner',
CONCAT('synthetic:notification-demo:rfq-rejected - Your hydraulic power unit quote was rejected for RFQ #', r.id, '.'),
'rfq_request', r.id, 1, '2026-05-03 13:05:00', '2026-05-03 14:00:00'
FROM rfq_requests r
WHERE r.client_message LIKE 'synthetic:rfq-demo:rejected-hydraulic-power-unit%'
  AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.type = 'RFQ_REJECTED_BY_OWNER' AND n.reference_type = 'rfq_request' AND n.reference_id = r.id AND n.message LIKE 'synthetic:notification-demo:rfq-rejected%');

-- Purchase lot needs finalization
INSERT INTO notifications
(user_id, role, type, title, message, reference_type, reference_id, is_read, created_at, read_at)
SELECT @owner_id, 'owner', 'PURCHASE_LOT_NEEDS_FINALIZATION', 'Purchase lot needs finalization',
CONCAT('synthetic:notification-demo:purchase-lot-needs-finalization - Purchase lot #', pl.id, ' needs owner review before stock is updated.'),
'purchase_lot', pl.id, 0, '2026-05-04 09:30:00', NULL
FROM purchase_lots pl
WHERE pl.notes LIKE 'synthetic:rfq-demo:purchase-lot:%'
  AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.type = 'PURCHASE_LOT_NEEDS_FINALIZATION' AND n.reference_type = 'purchase_lot' AND n.reference_id = pl.id AND n.message LIKE 'synthetic:notification-demo:purchase-lot-needs-finalization%');

-- Purchase lot finalized
INSERT INTO notifications
(user_id, role, type, title, message, reference_type, reference_id, is_read, created_at, read_at)
SELECT @owner_id, 'owner', 'PURCHASE_LOT_FINALIZED', 'Purchase lot finalized',
CONCAT('synthetic:notification-demo:purchase-lot-finalized - Purchase lot #', pl.id, ' was finalized and stock movement was created.'),
'purchase_lot', pl.id, 0, '2026-05-04 10:45:00', NULL
FROM purchase_lots pl
WHERE pl.notes LIKE 'synthetic:rfq-demo:purchase-lot:%'
  AND pl.status = 'finalized'
  AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.type = 'PURCHASE_LOT_FINALIZED' AND n.reference_type = 'purchase_lot' AND n.reference_id = pl.id AND n.message LIKE 'synthetic:notification-demo:purchase-lot-finalized%');

-- Low stock alert
INSERT INTO notifications
(user_id, role, type, title, message, reference_type, reference_id, is_read, created_at, read_at)
SELECT @owner_id, 'owner', 'LOW_STOCK_ALERT', 'Low stock alert',
CONCAT('synthetic:notification-demo:low-stock - ', c.sku, ' is below its low stock threshold. Current stock: ', c.stock_qty, '. Threshold: ', c.low_stock_threshold, '.'),
'component', c.id, 0, '2026-05-03 17:30:00', NULL
FROM components c
WHERE c.sku = 'CMP-HYD-FLT10'
  AND c.stock_qty < c.low_stock_threshold
  AND c.stock_qty > 0
  AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.type = 'LOW_STOCK_ALERT' AND n.reference_type = 'component' AND n.reference_id = c.id AND n.message LIKE 'synthetic:notification-demo:low-stock%');

-- Out of stock alert
INSERT INTO notifications
(user_id, role, type, title, message, reference_type, reference_id, is_read, created_at, read_at)
SELECT @owner_id, 'owner', 'OUT_OF_STOCK_ALERT', 'Out of stock alert',
CONCAT('synthetic:notification-demo:out-of-stock - ', c.sku, ' is out of stock and marked VED ', COALESCE(c.ved_class, 'unclassified'), '.'),
'component', c.id, 0, '2026-05-03 17:35:00', NULL
FROM components c
WHERE c.sku = 'CMP-HYD-RV210'
  AND c.stock_qty = 0
  AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.type = 'OUT_OF_STOCK_ALERT' AND n.reference_type = 'component' AND n.reference_id = c.id AND n.message LIKE 'synthetic:notification-demo:out-of-stock%');

-- Order created notifications to owner
INSERT INTO notifications
(user_id, role, type, title, message, reference_type, reference_id, is_read, created_at, read_at)
SELECT @owner_id, 'owner', 'ORDER_CREATED', 'New client order created',
CONCAT('synthetic:notification-demo:order-created - New order #', o.id, ' created by client ', o.client_id, '.'),
'order', o.id, 0, o.created_at, NULL
FROM orders o
WHERE o.notes LIKE 'synthetic:order-demo:%'
  AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.type = 'ORDER_CREATED' AND n.reference_type = 'order' AND n.reference_id = o.id AND n.message LIKE 'synthetic:notification-demo:order-created%');

-- Delivered order status notification to client
INSERT INTO notifications
(user_id, role, type, title, message, reference_type, reference_id, is_read, created_at, read_at)
SELECT o.client_id, 'client', 'ORDER_STATUS_CHANGED', 'Order delivered',
CONCAT('synthetic:notification-demo:order-delivered - Your order #', o.id, ' has been delivered.'),
'order', o.id, 0, o.updated_at, NULL
FROM orders o
WHERE o.notes IN ('synthetic:order-demo:delivered-hydraulic-filters', 'synthetic:order-demo:delivered-bearings')
  AND o.status = 'delivered'
  AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.type = 'ORDER_STATUS_CHANGED' AND n.reference_type = 'order' AND n.reference_id = o.id AND n.message LIKE 'synthetic:notification-demo:order-delivered%');

-- Cancelled order notification to client
INSERT INTO notifications
(user_id, role, type, title, message, reference_type, reference_id, is_read, created_at, read_at)
SELECT o.client_id, 'client', 'ORDER_STATUS_CHANGED', 'Order cancelled',
CONCAT('synthetic:notification-demo:order-cancelled - Your order #', o.id, ' was cancelled and stock was restored where applicable.'),
'order', o.id, 1, o.updated_at, '2026-05-03 15:00:00'
FROM orders o
WHERE o.notes = 'synthetic:order-demo:cancelled-mechanical-seals'
  AND o.status = 'cancelled'
  AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.type = 'ORDER_STATUS_CHANGED' AND n.reference_type = 'order' AND n.reference_id = o.id AND n.message LIKE 'synthetic:notification-demo:order-cancelled%');
```

---

### 4.15 Final stock sync

Run this after all stock movement sections.

```sql
UPDATE components c
LEFT JOIN (
  SELECT
    component_id,
    SUM(
      CASE
        WHEN type = 'in' THEN quantity
        WHEN type = 'out' THEN -quantity
        ELSE 0
      END
    ) AS calculated_stock
  FROM stock_movements
  GROUP BY component_id
) sm ON sm.component_id = c.id
SET c.stock_qty = COALESCE(sm.calculated_stock, 0)
WHERE c.sku IN (
  'CMP-FST-M10BOLT',
  'CMP-HYD-FLT10',
  'CMP-HYD-RV210',
  'ASM-HYD-HPU80',
  'CMP-SEAL-MECH25',
  'CMP-BRG-6205',
  'RAW-STL-C45-35',
  'RAW-GSK-EPDM3'
);
```

---

## 5. Verification queries

### 5.1 Verify no invalid stock quantity

```sql
SELECT *
FROM stock_movements
WHERE quantity <= 0;
```

Expected result:

```txt
0 rows
```

### 5.2 Verify synthetic stock movements

```sql
SELECT
  c.sku,
  c.name,
  sm.type,
  sm.quantity,
  sm.reason,
  sm.reference_type,
  sm.reference_id,
  sm.notes,
  sm.created_at
FROM stock_movements sm
JOIN components c ON c.id = sm.component_id
WHERE sm.notes LIKE 'synthetic:%'
ORDER BY c.sku, sm.created_at;
```

### 5.3 Verify current stock by movement formula

```sql
SELECT
  c.sku,
  c.name,
  c.ved_class,
  c.low_stock_threshold,
  c.stock_qty AS stored_stock_qty,
  COALESCE(SUM(
    CASE
      WHEN sm.type = 'in' THEN sm.quantity
      WHEN sm.type = 'out' THEN -sm.quantity
      ELSE 0
    END
  ), 0) AS calculated_stock_qty,
  COUNT(sm.id) AS movement_count
FROM components c
LEFT JOIN stock_movements sm ON sm.component_id = c.id
WHERE c.sku IN (
  'CMP-FST-M10BOLT',
  'CMP-HYD-FLT10',
  'CMP-HYD-RV210',
  'ASM-HYD-HPU80',
  'CMP-SEAL-MECH25',
  'CMP-BRG-6205',
  'RAW-STL-C45-35',
  'RAW-GSK-EPDM3'
)
GROUP BY c.id, c.sku, c.name, c.ved_class, c.low_stock_threshold, c.stock_qty
ORDER BY c.sku;
```

### 5.4 Verify reorder demo cases

```sql
SELECT
  c.sku,
  c.name,
  c.ved_class,
  c.low_stock_threshold,
  c.stock_qty,
  CASE
    WHEN c.stock_qty = 0 THEN 'CRITICAL_ZERO_STOCK'
    WHEN c.stock_qty < c.low_stock_threshold THEN 'BELOW_THRESHOLD'
    ELSE 'ABOVE_THRESHOLD'
  END AS stock_status
FROM components c
WHERE c.sku IN (
  'CMP-FST-M10BOLT',
  'CMP-HYD-FLT10',
  'CMP-HYD-RV210',
  'ASM-HYD-HPU80',
  'CMP-SEAL-MECH25',
  'CMP-BRG-6205',
  'RAW-STL-C45-35',
  'RAW-GSK-EPDM3'
)
ORDER BY stock_status, c.sku;
```

### 5.5 Verify RFQs

```sql
SELECT
  r.id,
  r.status,
  r.client_id,
  r.supplier_id,
  c.sku,
  r.quantity_requested,
  r.quoted_price,
  r.deadline_date,
  r.decision_note,
  r.decided_by,
  r.decided_at,
  r.client_message
FROM rfq_requests r
JOIN components c ON c.id = r.component_id
WHERE r.client_message LIKE 'synthetic:rfq-demo:%'
ORDER BY r.id;
```

### 5.6 Verify accepted RFQs did not directly create stock movement

```sql
SELECT *
FROM stock_movements
WHERE reference_type = 'rfq_request'
  AND notes LIKE 'synthetic:rfq-demo:%';
```

Expected result:

```txt
0 rows
```

### 5.7 Verify RFQ revisions

```sql
SELECT
  rr.id,
  rr.rfq_id,
  rr.revision_id,
  rr.supplier_id,
  s.name AS supplier_name,
  c.sku,
  r.status AS rfq_status,
  rr.quoted_price,
  rr.lead_time_days,
  rr.supplier_note,
  rr.created_at
FROM rfq_revisions rr
JOIN rfq_requests r ON r.id = rr.rfq_id
JOIN components c ON c.id = r.component_id
JOIN suppliers s ON s.id = rr.supplier_id
WHERE r.client_message LIKE 'synthetic:rfq-demo:%'
ORDER BY rr.rfq_id, rr.revision_id;
```

### 5.8 Verify purchase lots linked to RFQs

```sql
SELECT
  pl.id AS purchase_lot_id,
  pl.reference_type,
  pl.reference_id AS rfq_request_id,
  r.status AS rfq_status,
  c.sku,
  pl.quantity_received,
  pl.status AS purchase_lot_status,
  pl.total_purchase_cost,
  pl.unit_purchase_cost,
  pl.notes
FROM purchase_lots pl
JOIN rfq_requests r ON r.id = pl.reference_id
JOIN components c ON c.id = pl.component_id
WHERE pl.reference_type = 'rfq_request'
  AND pl.notes LIKE 'synthetic:rfq-demo:%'
ORDER BY pl.id;
```

### 5.9 Verify stock IN came from purchase lots only

```sql
SELECT
  sm.id AS stock_movement_id,
  sm.reference_type,
  sm.reference_id AS purchase_lot_id,
  c.sku,
  sm.type,
  sm.quantity,
  sm.reason,
  sm.notes
FROM stock_movements sm
JOIN components c ON c.id = sm.component_id
WHERE sm.reference_type = 'purchase_lot'
  AND sm.reason = 'PURCHASE_RECEIVED'
  AND sm.notes LIKE 'synthetic:rfq-demo:%'
ORDER BY sm.id;
```

### 5.10 Verify synthetic orders

```sql
SELECT
  o.id,
  o.client_id,
  o.status,
  o.total_amount,
  o.stripe_payment_id,
  o.shipping_address,
  o.notes,
  o.created_at,
  o.updated_at
FROM orders o
WHERE o.notes LIKE 'synthetic:order-demo:%'
ORDER BY o.id;
```

### 5.11 Verify synthetic order items

```sql
SELECT
  o.id AS order_id,
  o.status,
  o.notes AS order_notes,
  oi.id AS order_item_id,
  c.sku,
  c.name,
  oi.quantity,
  oi.unit_price,
  oi.supplier_id
FROM orders o
JOIN order_items oi ON oi.order_id = o.id
JOIN components c ON c.id = oi.component_id
WHERE o.notes LIKE 'synthetic:order-demo:%'
ORDER BY o.id, oi.id;
```

### 5.12 Verify order stock movements

```sql
SELECT
  o.id AS order_id,
  o.status,
  o.notes AS order_notes,
  c.sku,
  sm.type,
  sm.quantity,
  sm.reason,
  sm.notes AS movement_notes
FROM orders o
LEFT JOIN stock_movements sm
  ON sm.reference_type = 'order'
 AND sm.reference_id = o.id
LEFT JOIN components c
  ON c.id = sm.component_id
WHERE o.notes LIKE 'synthetic:order-demo:%'
ORDER BY o.id, sm.created_at;
```

Expected behavior:

```txt
delivered orders → SALE OUT
cancelled order → SALE OUT + CANCELLED_ORDER_RESTORE IN
pending order → no stock movement
shipped order → no stock movement in this seed
```

### 5.13 Verify dependencies

```sql
SELECT
  d.id,
  parent.sku AS parent_sku,
  parent.name AS parent_name,
  child.sku AS child_sku,
  child.name AS child_name,
  d.qty_required,
  d.uom,
  d.is_phantom,
  d.relation_type,
  d.notes
FROM dependencies d
JOIN components parent ON parent.id = d.parent_id
JOIN components child ON child.id = d.child_id
WHERE d.notes LIKE 'synthetic:dependency-demo:%'
ORDER BY parent.sku, d.relation_type, child.sku;
```

### 5.14 Verify part sources

```sql
SELECT
  ps.id,
  c.sku,
  c.name AS component_name,
  s.name AS supplier_name,
  ps.supplier_part_num,
  ps.unit_cost,
  ps.lead_time_days,
  ps.min_order_qty,
  ps.is_preferred,
  ps.valid_from,
  ps.valid_to
FROM part_sources ps
JOIN components c ON c.id = ps.component_id
JOIN suppliers s ON s.id = ps.supplier_id
WHERE ps.supplier_part_num LIKE 'SYN-%'
ORDER BY c.sku, ps.is_preferred DESC, ps.unit_cost ASC;
```

### 5.15 Verify notifications

```sql
SELECT
  n.id,
  n.user_id,
  n.role,
  n.type,
  n.title,
  n.reference_type,
  n.reference_id,
  n.is_read,
  n.created_at,
  n.read_at,
  n.message
FROM notifications n
WHERE n.message LIKE 'synthetic:notification-demo:%'
ORDER BY n.created_at, n.id;
```

### 5.16 Verify notification reference validity

```sql
SELECT
  n.id,
  n.type,
  n.reference_type,
  n.reference_id,
  CASE
    WHEN n.reference_type = 'rfq_request' AND r.id IS NOT NULL THEN 'valid'
    WHEN n.reference_type = 'purchase_lot' AND pl.id IS NOT NULL THEN 'valid'
    WHEN n.reference_type = 'component' AND c.id IS NOT NULL THEN 'valid'
    WHEN n.reference_type = 'order' AND o.id IS NOT NULL THEN 'valid'
    ELSE 'missing_reference'
  END AS reference_status
FROM notifications n
LEFT JOIN rfq_requests r
  ON n.reference_type = 'rfq_request'
 AND n.reference_id = r.id
LEFT JOIN purchase_lots pl
  ON n.reference_type = 'purchase_lot'
 AND n.reference_id = pl.id
LEFT JOIN components c
  ON n.reference_type = 'component'
 AND n.reference_id = c.id
LEFT JOIN orders o
  ON n.reference_type = 'order'
 AND n.reference_id = o.id
WHERE n.message LIKE 'synthetic:notification-demo:%'
ORDER BY n.id;
```

---

## 6. Recommended execution order

Run the seed in this order:

```txt
1. API users already done
2. Global SQL variables
3. Optional synthetic-only cleanup, only if re-running
4. VED classification updates
5. Threshold setup
6. Stock intelligence synthetic movements
7. RFQ requests
8. RFQ revisions
9. Purchase lots linked to accepted RFQs
10. Stock IN from finalized purchase lots
11. Orders
12. Order items
13. Order stock movements
14. Dependencies
15. Part sources
16. Final stock sync
17. Notifications
18. Verification queries
```

Recommended endpoint test after seeding:

```http
GET /stock/intelligence/reorder-recommendations
```

---

## 7. Expected demo outcomes

```txt
CMP-FST-M10BOLT
- stock above threshold
- no recommendation expected

CMP-HYD-FLT10
- stock below threshold
- reorder recommendation expected

CMP-HYD-RV210
- zero stock originally in synthetic demand scenario
- VED Vital
- critical reorder recommendation expected before purchase lot/order replenishment changes

ASM-HYD-HPU80
- VED Vital
- no OUT history
- criticality-only recommendation expected

CMP-SEAL-MECH25
- limited OUT history
- low confidence recommendation expected

CMP-BRG-6205
- frequent smooth OUT history
- moving average / smoothing eligible

RAW-STL-C45-35
- lumpy demand with outlier
- outlier flag eligible

RAW-GSK-EPDM3
- intermittent demand
- Croston/SBA eligible later
```

---

## 8. Important caveats

1. The seed assumes supplier IDs `1` through `8` already exist.
2. The seed assumes the listed SKUs already exist in `components`.
3. The variables resolve component IDs by SKU. If a SKU is missing, that section silently skips the insert because of `WHERE @component_id IS NOT NULL`.
4. The optional cleanup section should not be widened beyond synthetic rows.
5. If you run the stock intelligence movement seed multiple times without cleanup, duplicate stock movements may be inserted because those specific inserts were intentionally simple. Use the synthetic cleanup before re-running.
6. RFQ, purchase lot, order, dependency, part source, and notification inserts mostly use `NOT EXISTS` guards.
7. Notifications for low/out-of-stock depend on the stock state at the time they are run. Run the final stock sync before notifications.
8. For strict demo testing of zero-stock cases, test the stock intelligence endpoint before running replenishment purchase lots or order movements that change the same component stock.

---

## 9. Summary of what was accomplished

We prepared a complete safe additive Rabbit demo seed covering:

- API-created users.
- Real user ID mapping.
- VED classification.
- Low-stock thresholds.
- Stock intelligence scenarios.
- RFQ lifecycle cases.
- RFQ revision history.
- Finalized purchase lots.
- Purchase-lot-based stock increases.
- Orders and order items.
- Sale stock decreases.
- Cancelled order stock restore.
- Dependency/product relationship data.
- Supplier source/pricing data.
- Notifications for RFQs, stock, purchase lots, and orders.
- Verification SQL for every section.

The seed respects Rabbit's core rule: the platform manages direct purchasing and stocking of all item levels, while dependencies are for data relationships and intelligence, not manufacturing consumption.
