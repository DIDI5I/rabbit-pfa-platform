# Role-Based Access Design

Rabbit chatbot behavior depends on the current authenticated user's role.

Supported roles:

```text
owner
client
supplier / fournisseur
guest
```

## Guest

Guest users are not authenticated.

Allowed:

- public-safe help
- login/register suggestion
- basic Rabbit explanation

Forbidden:

- internal tools
- inventory data
- order data
- RFQ data
- supplier data
- stock intelligence
- dashboards
- memory from authenticated users

## Owner

Owner can access internal operational tools.

Owner can ask about:

- inventory
- inventory alerts
- stock intelligence
- reorder recommendations
- dashboard metrics
- cost rollup
- purchase lots
- RFQs
- orders
- internal promotions
- internal reviews
- suppliers
- stock movements

Owner may see:

- stock quantities
- purchase costs
- supplier names
- lead times
- reorder recommendations
- dashboard analytics
- internal RFQ data
- review moderation info

## Client

Client can access public/client-safe tools and own account data.

Client can ask about:

- catalogue products
- public product details
- product relations
- active promotions
- product reviews
- rating summaries
- own orders
- own notifications
- review creation, later with confirmation

Client must not see:

- internal inventory quantities
- stock intelligence
- reorder recommendations
- purchase costs
- supplier analytics
- owner dashboard
- purchase lots
- internal RFQs
- other clients' orders

If client asks for internal data, return a permission-safe response.

## Supplier / Fournisseur

Supplier can access supplier-facing tools scoped to their own records.

Supplier can ask about:

- RFQs assigned to them
- RFQ details for assigned RFQs
- their own quotes
- quote status
- their own notifications
- limited product details related to assigned RFQs

Supplier must not see:

- other suppliers' RFQs
- other suppliers' quotes
- owner dashboard
- full inventory
- cost rollup
- stock intelligence
- client private data
- owner analytics

Record-level access is mandatory.

Example:

```text
supplier can access RFQ only if RFQ is assigned to that supplier
```

## Tool Matrix

| Tool / Intent | Guest | Client | Supplier | Owner |
|---|---:|---:|---:|---:|
| public_help | yes | yes | yes | yes |
| catalog_search | maybe | yes | limited | yes |
| catalog_product_details | maybe | yes | limited | yes |
| catalog_product_reviews | yes | yes | maybe | yes |
| catalog_rating_summary | yes | yes | maybe | yes |
| active_promotions | yes | yes | maybe | yes |
| product_relations | maybe | yes | limited | yes |
| inventory_summary | no | no | no | yes |
| inventory_alerts | no | no | no | yes |
| stock_movements | no | no | no | yes |
| reorder_recommendations | no | no | no | yes |
| dashboard_stock_summary | no | no | no | yes |
| cost_rollup | no | no | no | yes |
| purchase_lots | no | no | no | yes |
| all_rfqs | no | no | no | yes |
| supplier_assigned_rfqs | no | no | yes | maybe |
| submit_quote | no | no | later | later |
| my_orders | no | yes | no | yes/all |
| notifications | no | own | own | own/all |
