# Supplier Mapping Discovery

During pipeline testing, a supplier login issue was found.

The backend checks:

```text
users.supplier_company_id === rfqs.supplier_id
```

It does not rely on the displayed company name.

Conclusion:

```text
RFQ quote authorization was correct.
Seed/user data had inconsistent company naming.
```

Clean test path:

```text
register or use a supplier user whose supplier_company_id matches a real supplier row
create RFQ assigned to that supplier_id
open RFQ
quote as matching supplier
accept/reject as owner through chatbot
```
