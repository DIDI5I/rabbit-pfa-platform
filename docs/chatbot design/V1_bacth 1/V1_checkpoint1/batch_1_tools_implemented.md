# Batch 1 Tools Implemented

## Public / Guest-Safe Tools

```text
role_help
navigate, only when target is role-authorized
catalog_search
catalog_product_details
active_promotions
```

## Owner-Only Tools

```text
inventory_summary
inventory_alerts
cost_rollup
reorder_recommendations
dashboard_stock_summary
```

## Blocked in V1

All write actions remain blocked:

```text
create/update/delete products
create/update/delete dependencies
create reviews
create/update/delete promotions
attach/detach promotion products
create/finalize purchase lots
RFQ create/open/accept/reject/expire/quote
stock movement mutation
order creation/status update
notification read mutation
review moderation mutation
```

## Role Permission Behavior

Owner-only resources return permission denial to guest/client/supplier roles.

Navigation targets are also role-checked. Navigation does not bypass frontend guards or backend middleware.
