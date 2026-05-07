# Pipeline Test Data Approach

Final RFQ accept testing used the real backend pipeline:

```text
register supplier user
login as supplier
create RFQ as owner
open RFQ as owner
quote RFQ as supplier
accept RFQ through chatbot as owner
verify RFQ
verify purchase lot
```

This is cleaner than manually inserting SQL because it proves normal endpoints and chatbot write actions work together.

Do not commit cookie/session files:

```text
owner_cookies.txt
supplier_cookies.txt
new_supplier_cookies.txt
cookies.txt
```
