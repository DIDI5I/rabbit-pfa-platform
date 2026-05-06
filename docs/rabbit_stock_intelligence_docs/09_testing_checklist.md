# Rabbit — Stock Intelligence Testing Checklist

## Base Endpoint

```http
GET /stock/intelligence/reorder-recommendations
```

Expected:

- 200 OK
- All active products returned
- No duplicate product rows
- `model_eligibility` exists
- `outlier_analysis` exists
- `trend_analysis` exists
- `seasonality_analysis` exists

## Filter Tests

```http
GET /stock/intelligence/reorder-recommendations?only_recommended=true
GET /stock/intelligence/reorder-recommendations?priority=CRITICAL
GET /stock/intelligence/reorder-recommendations?confidence=LOW
GET /stock/intelligence/reorder-recommendations?model=moving_average
GET /stock/intelligence/reorder-recommendations?model=simple_exponential_smoothing
GET /stock/intelligence/reorder-recommendations?model=croston_sba
```

Expected:

- Filtered items match requested filter.
- Summary recalculates based on filtered items.

## Known Demo Cases

### `CMP-BRG-6205`

Expected:

```text
selected_model = moving_average
outlier_detected = true
SES rejected because outlier detected
```

### `CMP-SEAL-MECH25`

Expected:

```text
selected_model = croston_sba
intermittent demand profile confirmed
```

### `CMP-BELT-SPA1250`

Expected:

```text
selected_model = simple_exponential_smoothing
out_movement_count >= 10
outlier_detected = false
```

### `CMP-HYD-FLT10`

Expected:

```text
selected_model = moving_average
recommendation = true
priority = CRITICAL
```

## SQL Verification Queries

Check OUT movement count:

```sql
SELECT
    c.id,
    c.sku,
    c.name,
    COUNT(sm.id) AS out_movement_count,
    SUM(sm.quantity) AS stock_out_quantity,
    MIN(sm.created_at) AS first_out,
    MAX(sm.created_at) AS last_out
FROM components c
LEFT JOIN stock_movements sm
    ON sm.component_id = c.id
    AND sm.type = 'out'
    AND sm.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
WHERE c.sku = 'CMP-BELT-SPA1250'
GROUP BY c.id, c.sku, c.name;
```

Check current stock from movements:

```sql
SELECT
    c.id,
    c.sku,
    c.name,
    COALESCE(SUM(CASE WHEN sm.type = 'in' THEN sm.quantity ELSE 0 END), 0)
    -
    COALESCE(SUM(CASE WHEN sm.type = 'out' THEN sm.quantity ELSE 0 END), 0)
    AS current_stock
FROM components c
LEFT JOIN stock_movements sm
    ON sm.component_id = c.id
WHERE c.sku = 'CMP-BELT-SPA1250'
GROUP BY c.id, c.sku, c.name;
```
