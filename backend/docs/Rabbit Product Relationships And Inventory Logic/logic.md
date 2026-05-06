# Rabbit — Product Relationships, Stock Logic, Cost Intelligence, and Custom Kits

## 1. Core Business Reality

Rabbit is **not a production or assembly system**.

Rabbit does **not** manufacture products, assemble pumps, consume child components to create parent products, or physically build assemblies from parts.

Rabbit is an industrial MRO / e-commerce / procurement platform where the company can **buy, stock, and sell items at every level**:

* assemblies
* sub-assemblies
* components
* raw materials

Example:

```text
Pump XR200 stock       = bought directly as complete pumps
Motor Unit stock       = bought directly as motor units
Bearing 6205 stock     = bought directly as bearings
Vis M8x25 stock        = bought directly as screws
Seal Kit stock         = bought directly as kits
```

Each item has its own stock, its own suppliers, its own price, and can be bought/sold independently.

---

## 2. Important Rule: Dependencies Are Not Physical Stock Containment

The relationship:

```text
Pump XR200 → Bearing 6205
```

must **not** mean:

```text
The Bearing 6205 stock is physically inside Pump XR200 stock.
```

It means:

```text
Bearing 6205 is logically/technically/commercially related to Pump XR200.
```

So if the system sells one Pump XR200, only Pump XR200 stock decreases.

```text
OUT Pump XR200 x1
```

It does **not** automatically do:

```text
OUT Bearing 6205 x2
OUT Vis M8x25 x6
OUT Seal x1
```

That would only make sense in a production/assembly system, which Rabbit is not.

---

## 3. Independent Stock Model

Every product in `components` is independently stocked.

Examples:

```text
Pump XR200 stock       = 5
Motor Unit stock       = 10
Bearing 6205 stock     = 100
Vis M8x25 stock        = 500
Hydraulic Cylinder     = 20
```

These stock quantities are separate.

Selling one item affects only that item unless the sale is explicitly a bundle/custom kit containing multiple separate items.

---

## 4. Correct Selling Behavior

### Selling a pump

If a client buys:

```text
Pump XR200 x1
```

Stock movement:

```text
OUT Pump XR200 x1
```

No bearing stock changes.
No screw stock changes.
No seal stock changes.

---

### Selling screws

If a client buys:

```text
Vis M8x25 x10
```

Stock movement:

```text
OUT Vis M8x25 x10
```

No pump stock changes.

---

### Buying from supplier through RFQ

If RFQ is accepted for:

```text
Bearing 6205 x100
```

Stock movement:

```text
IN Bearing 6205 x100
```

If RFQ is accepted for:

```text
Pump XR200 x5
```

Stock movement:

```text
IN Pump XR200 x5
```

Again, each item remains independent.

---

## 5. Product Relationships Instead of Pure BOM

The old term `dependencies` feels too manufacturing-oriented.

For Rabbit, the better concept is:

```text
Product relationships
```

The table may still be called `dependencies` internally for now, but the business meaning should evolve.

Instead of only saying:

```text
Parent product depends on child product.
```

Rabbit should express:

```text
This product is connected to another product in a specific way.
```

---

## 6. Relationship Types

The same relationship:

```text
Pump XR200 → Bearing 6205
```

can mean different things depending on `relation_type`.

### 6.1 `internal_structure`

Meaning:

```text
Bearing 6205 is part of the pump’s technical/internal structure.
```

Useful for:

* product tree display
* technical understanding
* maintenance explanation
* reference structure value
* AI assistant answers

Stock behavior:

```text
No automatic stock movement between parent and child.
```

---

### 6.2 `replacement_part`

Meaning:

```text
Bearing 6205 can replace a worn part in Pump XR200.
```

Useful for:

* spare parts recommendations
* maintenance support
* after-sales service
* frontend product page suggestions

Stock behavior:

```text
Bearing stock is independent.
Pump stock is independent.
```

---

### 6.3 `compatible_part`

Meaning:

```text
This part is compatible with the parent product.
```

Example:

```text
Pump XR200 → Hydraulic Cylinder CYL-50
Pump XR200 → Hose Kit H-20
```

Useful for:

* compatibility suggestions
* custom configuration
* procurement recommendations

---

### 6.4 `compatible_alternative`

Meaning:

```text
This item can be used as an alternative to another related item.
```

Example:

```text
Pump XR200
├── Internal structure
│   └── Bearing 6205 Generic
│
└── Compatible alternatives
    ├── SKF Bearing 6205
    ├── NSK Bearing 6205
    └── FAG Bearing 6205
```

Important:

Compatible alternatives should **not all be counted** in cost estimates by default.

Only the selected/reference/default item should be used for a reference estimate.

---

### 6.5 `accessory`

Meaning:

```text
This item is useful with the product, but not inside it.
```

Example:

```text
Pump XR200 → Installation Kit
Pump XR200 → Maintenance Tool Set
Pump XR200 → Mounting Bracket
```

Useful for:

* upselling
* recommended bundles
* frontend product pages

---

### 6.6 `related_product`

Meaning:

```text
This product is commercially or functionally related, but not structurally part of the parent.
```

Example:

```text
Pump XR200 → Pump XR300
Pump XR200 → Control Panel CP-100
Pump XR200 → Hose Kit
```

Useful for:

* related products carousel
* AI recommendations
* product discovery

---

## 7. Proposed Relationship Tree for Frontend

Instead of showing one flat dependency list, Rabbit should show a grouped product relationship tree.

Example:

```text
Pump XR200
├── Internal structure
│   ├── Motor Unit
│   ├── Shaft Assembly
│   └── Bearing 6205
│
├── Replacement parts
│   ├── Bearing 6205
│   ├── Mechanical Seal
│   └── Bolt M8x25
│
├── Compatible parts
│   ├── Hydraulic Cylinder CYL-50
│   ├── Hose Kit H-20
│   └── Seal Kit XR-Series
│
├── Accessories
│   ├── Mounting Bracket
│   └── Maintenance Tool Set
│
└── Related products
    ├── Pump XR300
    └── Control Panel CP-100
```

This is more natural for an MRO/e-commerce platform than a pure manufacturing BOM tree.

---

## 8. Backend Implication: Add `relation_type`

Current `dependencies` table:

```text
id
parent_id
child_id
qty_required
uom
is_phantom
notes
created_at
```

Recommended addition:

```sql
ALTER TABLE dependencies
ADD COLUMN relation_type ENUM(
  'internal_structure',
  'replacement_part',
  'compatible_part',
  'compatible_alternative',
  'spare_part',
  'accessory',
  'related_product'
) NOT NULL DEFAULT 'internal_structure';
```

This allows Rabbit to keep the existing table while making the logic more expressive.

Long-term, the table could be renamed conceptually to:

```text
product_relations
```

But renaming is not required immediately.

---

## 9. API Naming Recommendation

Even if the database table remains `dependencies`, the frontend/API should expose the concept as relationships.

Prefer:

```text
GET /products/{id}/relationships
```

Instead of:

```text
GET /products/{id}/dependencies
```

Response shape should be grouped by relation type.

Example:

```json
{
  "message": "Product relationships fetched successfully",
  "data": {
    "product": {
      "id": 1,
      "name": "Pump XR200",
      "sku": "PUMP-XR200"
    },
    "relationships": {
      "internal_structure": [],
      "replacement_parts": [],
      "compatible_parts": [],
      "accessories": [],
      "related_products": []
    }
  }
}
```

---

## 10. Cost Rollup Problem

Traditional cost rollup means:

```text
Cost of parent = sum of child component costs
```

That makes sense in manufacturing.

But Rabbit is not manufacturing.

So this interpretation is wrong:

```text
Pump XR200 actual cost = sum of Bearing + Seal + Screws + Shaft
```

Rabbit buys Pump XR200 directly from a supplier.

Therefore, actual Pump XR200 procurement cost comes from:

```text
part_sources where component_id = Pump XR200 id
```

Not from child relationships.

---

## 11. Correct Cost Logic for Rabbit

### 11.1 Actual product supplier cost

For Pump XR200:

```text
Actual procurement cost = supplier price of Pump XR200
```

Source:

```text
part_sources.component_id = Pump XR200
```

Example:

```text
Pump XR200
Supplier A: 1200 MAD
Supplier B: 1350 MAD
Supplier C: 1180 MAD
```

---

### 11.2 Relationship-based estimated value

For related parts:

```text
Bearing 6205 x2
Mechanical Seal x1
Bolt M8x25 x6
```

Rabbit can calculate:

```text
estimated related parts value = sum(preferred supplier price × qty)
```

But this is not the actual pump cost.

It is a reference estimate.

---

## 12. Repurposing Cost Rollup

The old “cost rollup” feature should be repurposed into **Product Cost Intelligence**.

Possible calculations:

### 12.1 Reference Structure Value

Answers:

```text
What is the estimated value of the internal/reference parts related to this product?
```

Example:

```text
Pump XR200 supplier cost: 1,200 MAD

Reference structure estimate:
- Bearing 6205 x2 = 25 MAD
- Mechanical Seal x1 = 80 MAD
- Bolt M8x25 x6 = 3 MAD

Total reference structure value = 108 MAD
```

This is useful for:

* technical comparison
* explaining internal structure
* detecting overpriced assemblies

But it is not actual production cost.

---

### 12.2 Maintenance Kit Estimate

Answers:

```text
How much would it cost to replace the common spare parts for this product?
```

Example:

```text
Pump XR200 maintenance kit:
- Bearing 6205 x2
- Mechanical Seal x1
- Bolt Set x6

Estimated maintenance kit cost = 108 MAD
```

This is very natural for MRO.

---

### 12.3 Replacement Parts Estimate

Answers:

```text
If the user needs to replace all critical related parts, what would it cost?
```

This can include a broader set than the maintenance kit.

Example:

```text
Pump XR200 replacement estimate:
- Bearing
- Seal
- Shaft
- Housing
```

---

### 12.4 Supplier Basket Comparison

Answers:

```text
Which supplier or supplier combination gives the best price for the related spare-parts basket?
```

Example:

```text
Pump XR200 spare basket:

Supplier A:
- Bearing 6205: 13 MAD
- Seal: 80 MAD
- Bolts: 2 MAD
Total: 95 MAD

Supplier B:
- Bearing 6205: 11 MAD
- Seal: 90 MAD
- Bolts: 3 MAD
Total: 104 MAD
```

Useful for procurement decisions.

---

### 12.5 Spare Parts Criticality Value

Answers:

```text
Which components are important because many products depend on them?
```

Example:

```text
Bearing 6205 appears in:
- Pump XR200
- Conveyor Drive Unit
- Motor Unit

Current stock: 8
Low threshold: 20
Preferred supplier lead time: 7 days
```

This helps classify Bearing 6205 as a high-impact spare part.

Possible score:

```text
impact score = relationship count × demand × unit cost × lead time
```

---

### 12.6 Repair Cost Estimate

Answers:

```text
If this product fails, how much might repair parts cost?
```

Example:

```text
Pump XR200 possible repair:
- bearing replacement
- seal replacement
- shaft replacement

Estimated repair parts cost: 320 MAD
```

Useful for:

* after-sales support
* maintenance planning
* AI assistant answers

---

### 12.7 Accessory Bundle Estimate

Answers:

```text
How much does a recommended accessory bundle cost with this product?
```

Example:

```text
Pump XR200 accessory bundle:
- Installation Kit
- Hose Kit
- Gasket Kit

Bundle estimate: 250 MAD
```

Useful for e-commerce upsell.

---

### 12.8 Commercial Value Analysis

Answers:

```text
Does this product make commercial sense compared to its related parts and selling price?
```

Example:

```text
Pump XR200 supplier cost: 1,200 MAD
Related spare parts estimate: 300 MAD
Selling price: 1,600 MAD
```

Useful as an internal owner/admin insight.

---

## 13. Best Cost Intelligence Features for Rabbit

Recommended final replacement for old cost rollup:

```text
Product Cost Intelligence
├── Reference Structure Value
├── Maintenance Kit Estimate
├── Supplier Basket Comparison
├── Critical Spare Parts
└── Custom Solution Estimate
```

---

## 14. Custom Products / Custom Kits

A strong repurpose of cost rollup is **custom solution pricing**.

Rabbit can let the owner or client configure a custom procurement package from independently stocked items.

Example:

```text
Client wants a pump solution.

Rabbit recommends:
- Pump XR200
- Hydraulic Cylinder CYL-50
- Seal Kit XR
- Hose Kit H-20
- Mounting Bracket
```

The system calculates:

```text
custom solution price = sum(selected item prices) + markup + optional service fee
```

This is not manufacturing.

It is a configurable commercial bundle.

---

## 15. Custom Solution Example

```text
Custom Hydraulic Pump Kit
├── Pump XR200                  x1   1,200 MAD
├── Hydraulic Cylinder CYL-50   x2     450 MAD each
├── Seal Kit XR                 x1      90 MAD
├── Hose Kit H-20               x1     160 MAD
└── Mounting Bracket            x1      80 MAD

Base cost = 2,430 MAD
Markup 20% = 486 MAD
Final price = 2,916 MAD
```

This is extremely useful for Rabbit because it turns relationships into a sales/procurement feature.

---

## 16. Stock Behavior for Custom Kits

If a client buys the custom kit:

```text
Custom Pump Solution #001
├── Pump XR200 x1
├── Cylinder CYL-50 x2
├── Hose Kit H-20 x1
└── Mounting Bracket x1
```

Stock movements are:

```text
OUT Pump XR200 x1
OUT Cylinder CYL-50 x2
OUT Hose Kit H-20 x1
OUT Mounting Bracket x1
```

There is no hidden manufacturing/assembly operation.

The kit is a commercial grouping of independently stocked items.

---

## 17. Distinction Between Relationships and Custom Kits

### Product relationships

Answer:

```text
What products are logically connected?
```

Example:

```text
Pump XR200 is compatible with Cylinder CYL-50.
```

No sale.
No stock movement.

---

### Custom kit

Answer:

```text
What exact items are being sold together as a package?
```

Example:

```text
Custom Pump Solution #001 contains:
- Pump XR200 x1
- Cylinder CYL-50 x2
- Hose Kit x1
```

When sold, stock moves for each kit item independently.

---

## 18. Future Database Tables for Custom Kits

Possible future tables:

```text
custom_kits
custom_kit_items
```

or:

```text
product_bundles
product_bundle_items
```

Recommended structure:

```text
custom_kits
- id
- name
- description
- base_product_id
- created_by
- status
- markup_percent
- service_fee
- created_at
- updated_at
```

```text
custom_kit_items
- id
- kit_id
- component_id
- quantity
- unit_price_snapshot
- source_type
- notes
- created_at
```

Important:

`custom_kits` should be separate from `dependencies`.

Relationships suggest possible combinations.

Custom kits define exact sellable packages.

---

## 19. Stock Movement Reasons

Because Rabbit does not manufacture or assemble, avoid stock movement reasons like:

```text
ASSEMBLY_CONSUMPTION
ASSEMBLY_PRODUCTION
```

Recommended stock movement reasons:

```text
INITIAL_STOCK
PURCHASE_RECEIVED
RFQ_ACCEPTED
SALE
MANUAL_ADJUSTMENT
RETURN
DAMAGED
CANCELLED_ORDER_RESTORE
```

If custom kits are introduced, use:

```text
CUSTOM_KIT_SALE
```

But this still means each item in the kit gets its own stock movement.

Example:

```text
OUT Pump XR200 x1 reason = CUSTOM_KIT_SALE
OUT Cylinder CYL-50 x2 reason = CUSTOM_KIT_SALE
OUT Hose Kit x1 reason = CUSTOM_KIT_SALE
```

---

## 20. Final Conceptual Model

Rabbit should be understood like this:

```text
components
= sellable/buyable/stockable items

part_sources
= supplier prices and lead times for each item

dependencies/product_relations
= product relationship intelligence

stock_movements
= physical inventory history

custom_kits
= commercial bundles/configured solutions

cost intelligence
= decision-support calculations, not manufacturing cost
```

---

## 21. Final Rules to Preserve

1. Rabbit does not produce or assemble products.
2. All item levels can be bought and stocked directly.
3. Dependencies do not cause stock movements.
4. Selling a parent does not reduce child stock.
5. Selling a child does not affect parent stock.
6. Actual product cost comes from `part_sources` for that exact product.
7. Relationship-based cost calculations are estimates, not production costs.
8. Product relationships should be grouped by type.
9. Custom kits are sellable bundles of independently stocked items.
10. AI assistant later should use these relationships for recommendations, not as a source of physical inventory truth.

---

## 22. Best Next Backend Decision

Before implementing stock movements, clarify and update the relationship model.

Recommended immediate schema change:

```sql
ALTER TABLE dependencies
ADD COLUMN relation_type ENUM(
  'internal_structure',
  'replacement_part',
  'compatible_part',
  'compatible_alternative',
  'spare_part',
  'accessory',
  'related_product'
) NOT NULL DEFAULT 'internal_structure';
```

Then update backend language from:

```text
dependencies
```

to:

```text
relationships
```

at the API/frontend level.

Recommended endpoint direction:

```text
GET /products/{id}/relationships
```

Later, add:

```text
GET /products/{id}/cost-intelligence
POST /custom-kits
GET /custom-kits/{id}/estimate
```

---

## 23. Short Summary

Rabbit should not treat product relationships as manufacturing dependencies.

It should treat them as an industrial product knowledge graph.

That graph supports:

* frontend product navigation
* spare part discovery
* compatibility suggestions
* custom solution building
* procurement recommendations
* supplier comparison
* maintenance estimates
* AI assistant reasoning

Stock remains independent.

Cost rollup becomes cost intelligence.

Custom kits become the clean way to sell configured solutions without pretending Rabbit manufactures anything.
