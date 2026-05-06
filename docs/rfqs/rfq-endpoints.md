# RFQ Endpoints

# RFQ Endpoints

POST   /rfqs
GET    /rfqs
GET    /rfqs/{id}
POST   /rfqs/open
POST   /rfqs/accept
POST   /rfqs/reject
POST   /rfqs/expire
PATCH  /rfqs/{id}/quote
GET    /rfqs/{id}/actions

## 🎯 Overview

The RFQ system exposes a set of REST API endpoints to manage the full procurement workflow.

All endpoints use JSON for request and response formats.

---

## 📌 Base Resource

```txt
/rfqs
🟢 Core Endpoints
1. Create RFQ
POST /rfqs

Creates a new RFQ in draft status.

Request
{
  "component_id": 24,
  "supplier_id": 3,
  "quantity_requested": 10
}
Response
{
  "message": "RFQ created successfully",
  "data": {
    "rfq_id": 5,
    "status": "draft"
  }
}
2. List RFQs
GET /rfqs

Returns RFQs based on user role.

Behavior
owner       → all RFQs
fournisseur → only assigned RFQs
client      → not allowed
3. RFQ Detail
GET /rfqs/{id}

Returns full details of a specific RFQ.

🔄 Workflow Endpoints
4. Open RFQ
POST /rfqs/open
Request
{
  "id": 5
}

Changes status:

draft → open
5. Accept RFQ
POST /rfqs/accept
Request
{
  "id": 5
}

Changes status:

quoted → accepted
6. Reject RFQ
POST /rfqs/reject
Request
{
  "id": 5
}

Changes status:

quoted → rejected
7. Expire RFQ
POST /rfqs/expire
Request
{
  "id": 5
}

Changes status:

open/quoted → expired
🟣 Supplier Endpoint
8. Submit Quote
PATCH /rfqs/{id}/quote

Allows a supplier to submit a price.

Request
{
  "quoted_price": 250.75
}
Rules
- Only fournisseur role
- RFQ must be open
- supplier_id must match supplier_company_id
Response
{
  "message": "RFQ quoted successfully",
  "data": {
    "rfq_id": 5,
    "quoted_price": 250.75,
    "status": "quoted"
  }
}
🔍 Meta Endpoint
9. Get Allowed Actions
GET /rfqs/{id}/actions

Returns allowed actions based on current RFQ status.

Example Response
{
  "message": "Allowed actions fetched successfully",
  "data": ["accept", "reject", "expire"]
}
🔐 Authorization

Access to endpoints is controlled via middleware:

OwnerMiddleware      → create, open, accept, reject, expire
SupplierMiddleware   → quote
AuthMiddleware       → list, detail, actions
⚠️ Notes
- All endpoints return JSON
- Validation errors use standardized format
- Invalid state transitions are blocked
- RFQs are internal and not exposed to clients
✅ Summary
- Full RFQ lifecycle is exposed via API
- Role-based access enforced via middleware
- Supplier interaction limited to quoting
- Endpoints align with procurement workflow