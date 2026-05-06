# Registry and Permissions

Registered tools:

```text
order_summary
recent_orders
orders_by_status
order_details
```

Allowed roles:

```text
owner
client
```

Denied roles:

```text
guest
supplier
fournisseur
```

Registry behavior:

```text
order_summary -> no required params
recent_orders -> no required params
orders_by_status -> requires status
order_details -> requires order_id
```

Sensitive:

```text
true
```

Reason:

Orders expose business/customer data.
