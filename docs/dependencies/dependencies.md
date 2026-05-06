Product Relationships Update — Current Status
Status

The product relationship layer has been updated and tested.

Rabbit now supports both:

technical maintenance relationships
commercial recommendation relationships

This expands the old dependency system into a stronger product intelligence system.

Important rule remains locked:

Product relationships do not create stock movements.
Product relationships do not consume child stock.
Product relationships do not mean production or assembly.
Why This Update Was Made

Rabbit is not a manufacturing system, but it still needs to understand how products are connected.

Relationships are useful for:

maintenance support
spare part navigation
compatible part lookup
customer recommendations
commercial alternatives
cross-selling
future AI/product assistant responses

Example:

Pump XR200 → Bearing 6205

This can mean:

Bearing 6205 is technically associated with Pump XR200.
Bearing 6205 is a replacement part.
Bearing 6205 is a spare part.

But it does not mean:

Selling Pump XR200 consumes Bearing 6205 stock.
Updated Relationship Types

The old relationship type:

internal_structure

was replaced with:

technical_structure

Reason:

technical_structure is clearer and avoids confusion with manufacturing, BOM, or assembly logic.

Final supported relationship types:

technical_structure
replacement_part
compatible_part
compatible_alternative
spare_part
accessory
similar_product
commercial_alternative
frequently_bought_together
related_product
Meaning of Each Relationship Type
technical_structure

Informational technical structure used for maintenance, navigation, and product intelligence.

It does not mean production.

It does not mean assembly.

It does not consume stock.

Example:

Pump XR200 → Bearing 6205

Meaning:

Bearing 6205 is technically associated with Pump XR200 and may matter for maintenance or understanding the product.
replacement_part

A part used to replace a worn or damaged part.

Example:

Pump XR200 → Mechanical Seal 25mm
compatible_part

A product that works with the parent product.

Example:

Hydraulic Power Unit → Hose Kit DN20
compatible_alternative

A technically compatible substitute.

Example:

SKF Bearing 6205 → FAG Bearing 6205
spare_part

A maintenance spare useful for the parent product.

Example:

Pump XR200 → Seal Kit 25mm
accessory

An optional add-on used with the product.

Example:

Hydraulic Power Unit → Pressure Gauge Kit
similar_product

A product with similar function, category, or use case.

Used for customer-facing recommendations.

Example:

Pump XR200 → Pump XR300
commercial_alternative

A sellable alternative based on price, brand, availability, or performance.

Example:

Premium Bearing 6205 → Economy Bearing 6205
frequently_bought_together

A product commonly bought together with another product.

Used for future cart recommendations and cross-selling.

Example:

Bearing 6205 → Grease Cartridge
related_product

A weak/generic relationship when no stronger type fits.

Use this only when the relationship is useful but does not clearly match another type.

Database Update Completed

The dependencies.relation_type enum was updated.

Old value:

internal_structure

was migrated to:

technical_structure

The database now supports:

technical_structure
replacement_part
compatible_part
compatible_alternative
spare_part
accessory
similar_product
commercial_alternative
frequently_bought_together
related_product

Existing relation counts after migration included:

technical_structure: 36
replacement_part: 9
related_product: 3
spare_part: 6
accessory: 5
compatible_part: 1

The newly added types may not appear in counts until relationships are created with them:

similar_product
commercial_alternative
frequently_bought_together
compatible_alternative
Backend Validation Updated

The backend request validation was updated in:

StoreDependencyRequest
UpdateDependencyRequest

The old allowed list contained:

internal_structure

The new allowed list contains:

technical_structure
replacement_part
compatible_part
compatible_alternative
spare_part
accessory
similar_product
commercial_alternative
frequently_bought_together
related_product

Any remaining backend references to:

internal_structure

should be removed or replaced with:

technical_structure
Completed Milestone

The following milestone is now complete:

Product relationship enum updated
internal_structure migrated to technical_structure
Backend validation updated
New recommendation relationship types supported
Product relationship creation works with the updated types
Rabbit can now model technical and commercial relationships separately