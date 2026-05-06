# Rabbit — Notification Queue Plan

## 1. Goal

The notification queue gives Rabbit users a simple notification inbox.

It records important backend events so the frontend can show useful alerts instead of forcing users to manually check every page.

Rabbit V1 should not use Redis, workers, async jobs, or a complex message queue.

For this school project, the notification queue means:

```text
database-backed notification inbox
```

Basic flow:

```text
important event happens
→ backend inserts row into notifications table
→ frontend calls GET /notifications
→ user sees unread/read notifications
```

Example:

```text
Supplier submits quote
→ backend creates RFQ_QUOTED notification for owner
→ owner sees "Nouveau devis reçu"
```

---

## 2. Core Design Rule

Notifications must be created from real backend events only.

Do not create fake alerts from frontend assumptions.

Do not use AI to invent notifications.

Notifications should be triggered inside backend services such as:

```text
RfqService
PurchaseLotService
StockService
OrderService
Inventory/Dashboard service later if needed
```

---

## 3. Recommended Table

Table name:

```text
notifications
```

Suggested columns:

```text
id
user_id nullable
role nullable
type
title
message
reference_type nullable
reference_id nullable
is_read
created_at
read_at nullable
```

Recommended SQL shape:

```sql
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,
    role VARCHAR(50) NULL,

    type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,

    reference_type VARCHAR(100) NULL,
    reference_id INT NULL,

    is_read TINYINT(1) NOT NULL DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,

    INDEX idx_notifications_user_id (user_id),
    INDEX idx_notifications_role (role),
    INDEX idx_notifications_is_read (is_read),
    INDEX idx_notifications_created_at (created_at),
    INDEX idx_notifications_reference (reference_type, reference_id)
);
```

Optional later:

```text
priority
category
expires_at
metadata JSON
```

Do not add these in V1 unless needed.

---

## 4. user_id vs role

Use `user_id` when the notification is for a specific user.

Example:

```text
client order status changed
→ notify that exact client
```

Use `role` when the notification is for all users with a role.

Example:

```text
supplier quoted RFQ
→ notify role = owner
```

Recommended V1 query logic:

```text
return notifications where:
user_id = current_user_id
OR role = current_user_role
```

---

## 5. Notification Endpoints

### 5.1 List notifications

```http
GET /notifications
```

Optional filters:

```http
GET /notifications?unread_only=true
GET /notifications?page=1&limit=20
```

Response:

```json
{
  "message": "Notifications fetched successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "type": "RFQ_QUOTED",
        "title": "Nouveau devis reçu",
        "message": "Le fournisseur a répondu à la RFQ #12.",
        "reference_type": "rfq",
        "reference_id": 12,
        "is_read": false,
        "created_at": "2026-05-02 15:20:00",
        "read_at": null
      }
    ],
    "unread_count": 1,
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 1,
      "total_pages": 1
    }
  }
}
```

### 5.2 Unread count

```http
GET /notifications/unread-count
```

Response:

```json
{
  "message": "Unread notification count fetched successfully",
  "data": {
    "unread_count": 4
  }
}
```

### 5.3 Mark one as read

```http
PATCH /notifications/{id}/read
```

Response:

```json
{
  "message": "Notification marked as read successfully",
  "data": {
    "id": 1,
    "is_read": true,
    "read_at": "2026-05-02 15:25:00"
  }
}
```

### 5.4 Mark all as read

```http
PATCH /notifications/read-all
```

Response:

```json
{
  "message": "All notifications marked as read successfully",
  "data": {
    "updated_count": 4
  }
}
```

---

## 6. V1 Notifications to Implement First

These are enough for the school project demo.

Do not implement every possible notification at once.

### 6.1 RFQ notifications

#### RFQ_ASSIGNED

Recipient:

```text
supplier user / supplier role linked to supplier_company_id
```

Trigger:

```text
RFQ is opened and assigned to a supplier
```

When to create:

```text
RfqService::open()
```

Reference:

```text
reference_type = rfq
reference_id = rfq id
```

Example title/message:

```text
Nouvelle RFQ assignée
Une nouvelle demande de devis vous a été envoyée.
```

---

#### RFQ_QUOTED

Recipient:

```text
owner
```

Trigger:

```text
supplier submits a quote
```

When to create:

```text
RfqService::quote()
```

Reference:

```text
reference_type = rfq
reference_id = rfq id
```

Example:

```text
Nouveau devis reçu
Un fournisseur a répondu à une RFQ.
```

This is one of the most important V1 notifications.

---

#### RFQ_ACCEPTED_BY_OWNER

Recipient:

```text
supplier assigned to the RFQ
```

Trigger:

```text
owner accepts supplier quote
```

When to create:

```text
RfqService::accept()
```

Example:

```text
Devis accepté
Votre devis a été accepté par le propriétaire.
```

---

#### RFQ_REJECTED_BY_OWNER

Recipient:

```text
supplier assigned to the RFQ
```

Trigger:

```text
owner rejects quote
```

When to create:

```text
RfqService::reject()
```

Example:

```text
Devis rejeté
Votre devis n’a pas été retenu.
```

---

#### RFQ_EXPIRED

Recipient:

```text
supplier assigned to RFQ
owner optional
```

Trigger:

```text
RFQ expires or is manually marked expired
```

When to create:

```text
RfqService::expire()
```

Add in V1 if easy; otherwise Phase 2.

---

### 6.2 Purchase lot notifications

#### PURCHASE_LOT_DRAFT_CREATED_FROM_RFQ

Recipient:

```text
owner
```

Trigger:

```text
owner accepts RFQ and backend creates draft purchase lot
```

When to create:

```text
RfqService::accept()
after draft purchase_lot is created
```

Example:

```text
Lot d’achat brouillon créé
Une RFQ acceptée a généré un lot d’achat à finaliser.
```

This is important because Rabbit’s current flow is:

```text
RFQ accepted
→ draft purchase lot
→ later finalize
→ stock IN
```

---

#### PURCHASE_LOT_NEEDS_FINALIZATION

Recipient:

```text
owner
```

Trigger:

```text
draft purchase lot exists after RFQ acceptance or manual creation
```

When to create:

```text
PurchaseLotService::create()
if status = draft
```

or:

```text
RfqService::accept()
after creating draft lot
```

Example:

```text
Lot d’achat à finaliser
Un lot d’achat attend les coûts finaux et la réception.
```

---

#### PURCHASE_LOT_FINALIZED

Recipient:

```text
owner
```

Trigger:

```text
purchase lot is finalized
```

When to create:

```text
PurchaseLotService::finalize()
```

Example:

```text
Lot d’achat finalisé
Le coût d’achat final a été calculé.
```

---

### 6.3 Stock notifications

#### STOCK_IN_PURCHASE_RECEIVED

Recipient:

```text
owner
```

Trigger:

```text
purchase lot finalization creates stock movement type=in
```

When to create:

```text
PurchaseLotService::finalize()
after stock movement creation
```

Example:

```text
Stock reçu
Le stock a été augmenté après réception d’un lot d’achat.
```

---

#### LOW_STOCK_ALERT

Recipient:

```text
owner
```

Trigger:

```text
after stock movement, current_stock <= low_stock_threshold and current_stock > 0
```

When to create:

```text
StockService::recordMovement()
after creating movement and recomputing stock
```

Example:

```text
Stock faible
Un produit est passé sous son seuil de stock.
```

---

#### OUT_OF_STOCK_ALERT

Recipient:

```text
owner
```

Trigger:

```text
after stock movement, current_stock <= 0
```

When to create:

```text
StockService::recordMovement()
```

Example:

```text
Rupture de stock
Un produit est en rupture de stock.
```

---

### 6.4 Order notifications

#### ORDER_CREATED

Recipient:

```text
owner
```

Trigger:

```text
client creates an order
```

When to create:

```text
OrderService::create()
```

Example:

```text
Nouvelle commande
Un client a créé une nouvelle commande.
```

---

#### ORDER_STATUS_CHANGED

Recipient:

```text
client who owns the order
```

Trigger:

```text
owner changes order status
```

When to create:

```text
OrderService::updateStatus()
```

Example:

```text
Statut de commande mis à jour
Votre commande est maintenant en traitement.
```

---

## 7. Best V1 Notification Set

Implement this first:

```text
RFQ_ASSIGNED
RFQ_QUOTED
RFQ_ACCEPTED_BY_OWNER
RFQ_REJECTED_BY_OWNER

PURCHASE_LOT_DRAFT_CREATED_FROM_RFQ
PURCHASE_LOT_NEEDS_FINALIZATION
PURCHASE_LOT_FINALIZED

STOCK_IN_PURCHASE_RECEIVED
LOW_STOCK_ALERT
OUT_OF_STOCK_ALERT

ORDER_CREATED
ORDER_STATUS_CHANGED
```

This is the recommended school-project V1.

It connects the most important Rabbit workflows:

```text
RFQ
purchase lots
stock
orders
```

---

## 8. V1 Notifications by Role

### Owner receives

```text
RFQ_QUOTED
PURCHASE_LOT_DRAFT_CREATED_FROM_RFQ
PURCHASE_LOT_NEEDS_FINALIZATION
PURCHASE_LOT_FINALIZED
STOCK_IN_PURCHASE_RECEIVED
LOW_STOCK_ALERT
OUT_OF_STOCK_ALERT
ORDER_CREATED
```

### Supplier receives

```text
RFQ_ASSIGNED
RFQ_ACCEPTED_BY_OWNER
RFQ_REJECTED_BY_OWNER
RFQ_EXPIRED
```

### Client receives

```text
ORDER_STATUS_CHANGED
ORDER_SHIPPED
ORDER_DELIVERED
ORDER_CANCELLED
```

For V1, `ORDER_STATUS_CHANGED` can cover shipped, delivered, cancelled, processing, etc.

Later, split them into separate types if needed.

---

## 9. Phase 2 Notifications

Add these after V1 is stable.

### 9.1 More RFQ notifications

```text
RFQ_CREATED
RFQ_OPENED
RFQ_EXPIRED
RFQ_NEEDS_DECISION
RFQ_QUOTE_REMINDER
```

When to add:

```text
after the RFQ dashboard/views are stable
```

Why later:

```text
V1 already covers the most visible RFQ events.
Reminders and decision nudges require more timing logic.
```

---

### 9.2 More order notifications

```text
ORDER_CONFIRMED
ORDER_PROCESSING
ORDER_SHIPPED
ORDER_DELIVERED
ORDER_CANCELLED
ORDER_REQUIRES_STOCK_REVIEW
```

When to add:

```text
after order status flow is finalized and client order views are stable
```

Why later:

```text
ORDER_STATUS_CHANGED is enough for V1.
Specific order notifications are better when frontend needs separate labels/icons.
```

---

### 9.3 Stock recovered

```text
STOCK_RECOVERED
```

Trigger:

```text
product was low/out and becomes OK after stock IN
```

When to add:

```text
after low/out stock notification logic works
```

Why later:

```text
requires knowing previous stock status before movement and new stock status after movement
```

---

### 9.4 High-value low stock alerts

```text
HIGH_VALUE_LOW_STOCK_ALERT
CLASS_A_STOCK_RISK
```

Trigger:

```text
ABC Class A product becomes low stock
```

When to add:

```text
after ABC dashboard is stable and if class lookup is easy
```

Why later:

```text
requires combining stock status with ABC classification.
This should ideally use a dedicated service/helper or aggregation query.
```

This is valuable for demo but not necessary for basic notification V1.

---

## 10. Phase 3 Notifications

Add these only if the project has time.

### 10.1 Product/catalog notifications

```text
PRODUCT_CREATED
PRODUCT_UPDATED
PRODUCT_DEACTIVATED
PRODUCT_COST_MISSING
PRODUCT_SUPPLIER_MISSING
PRODUCT_RELATION_MISSING
```

When to add:

```text
after frontend catalog/admin management pages are stable
```

Why later:

```text
Useful for admin completeness but not critical to core workflows.
```

Most useful of these:

```text
PRODUCT_COST_MISSING
PRODUCT_SUPPLIER_MISSING
```

because they improve dashboard/ABC data quality.

---

### 10.2 Dependency/recommendation notifications

```text
DEPENDENCY_CREATED
DEPENDENCY_UPDATED
DEPENDENCY_REMOVED
RECOMMENDATION_DATA_MISSING
```

When to add:

```text
after Recommendation V1 endpoint is fully integrated into frontend
```

Why later:

```text
Not important unless the frontend has a recommendation management workflow.
```

---

### 10.3 Supplier management notifications

```text
SUPPLIER_CREATED
SUPPLIER_UPDATED
SUPPLIER_PRICE_UPDATED
SUPPLIER_SOURCE_ADDED
PREFERRED_SUPPLIER_CHANGED
SUPPLIER_ACCOUNT_LINKED
```

When to add:

```text
after supplier/part_source admin flows are stable
```

Why later:

```text
Good for admin traceability but not required for demo.
```

---

## 11. Future Intelligence / Forecasting Notifications

These are future owner-only notifications.

Do not add them in V1.

```text
REORDER_RECOMMENDED
FORECAST_LOW_STOCK_RISK
FORECAST_OUT_OF_STOCK_RISK
CLASS_A_REORDER_RECOMMENDED
```

When to add:

```text
after forecasting/reorder endpoint exists
```

Possible future endpoint:

```http
GET /stock/forecast/reorder-recommendations
```

Important rule:

```text
Forecasting/reorder recommendations are owner-only.
```

Reason:

```text
They expose internal stock strategy and operational intelligence.
```

Trigger examples:

```text
forecast says stock after 30 days will be below threshold
forecast says Class A product should be reordered
forecast says product may reach zero stock
```

Do not implement until the forecasting module exists.

---

## 12. Future AI/Chatbot Notifications

These are optional future ideas.

```text
AI_INSIGHT_GENERATED
AI_RECOMMENDATION_REVIEW_REQUIRED
```

Avoid:

```text
AI_DATA_INSUFFICIENT as a notification
```

Reason:

```text
If data is insufficient, the chatbot should say so directly in the chat response.
It should not create a notification.
```

When to add:

```text
only after the chatbot/AI layer exists
```

Important AI rule:

```text
AI must never bypass backend permissions.
AI must not invent notifications or recommendations.
```

---

## 13. Trigger Map

### RfqService

```text
create()
→ optional RFQ_CREATED later

open()
→ RFQ_ASSIGNED

quote()
→ RFQ_QUOTED

accept()
→ RFQ_ACCEPTED_BY_OWNER
→ PURCHASE_LOT_DRAFT_CREATED_FROM_RFQ
→ PURCHASE_LOT_NEEDS_FINALIZATION

reject()
→ RFQ_REJECTED_BY_OWNER

expire()
→ RFQ_EXPIRED
```

### PurchaseLotService

```text
create()
→ PURCHASE_LOT_NEEDS_FINALIZATION if draft

finalize()
→ PURCHASE_LOT_FINALIZED
→ STOCK_IN_PURCHASE_RECEIVED

cancel()
→ PURCHASE_LOT_CANCELLED later
```

### StockService

```text
recordMovement()
→ STOCK_MOVEMENT_CREATED optional
→ LOW_STOCK_ALERT if stock crosses threshold
→ OUT_OF_STOCK_ALERT if stock <= 0
→ STOCK_RECOVERED later if stock status goes back to OK
```

### OrderService

```text
create()
→ ORDER_CREATED for owner

updateStatus()
→ ORDER_STATUS_CHANGED for client
→ STOCK_OUT_SALE optional owner notification
→ STOCK_RESTORED_CANCELLED_ORDER optional owner notification
```

### ProductService

```text
store()
→ PRODUCT_CREATED later

update()
→ PRODUCT_UPDATED later

destroy()
→ PRODUCT_DEACTIVATED later
```

---

## 14. Implementation Files

Recommended files:

```text
app/Queries/NotificationQuery.php
app/Repositories/NotificationRepository.php
app/Services/NotificationService.php
app/Controllers/NotificationController.php
```

Potential helper:

```text
app/Support/NotificationType.php
```

or:

```text
app/Enums/NotificationType.php
```

if the project uses enums.

V1 can keep notification type strings simple.

---

## 15. NotificationService Responsibilities

The service should provide:

```php
public function notifyUser(
    int $userId,
    string $type,
    string $title,
    string $message,
    ?string $referenceType = null,
    ?int $referenceId = null
): void
```

```php
public function notifyRole(
    string $role,
    string $type,
    string $title,
    string $message,
    ?string $referenceType = null,
    ?int $referenceId = null
): void
```

```php
public function listForCurrentUser(array $filters): array
```

```php
public function unreadCountForCurrentUser(): int
```

```php
public function markAsRead(int $notificationId): array
```

```php
public function markAllAsRead(): int
```

---

## 16. Important Security Rules

A user can only read notifications that are for:

```text
their user_id
OR their role
```

A user cannot mark someone else’s notification as read.

For role-level notifications:

```text
If role = owner and no user_id, any owner can see it.
```

Potential limitation:

```text
If one owner marks role-level notification as read, it becomes read for all owners.
```

For school project V1, this is acceptable.

If needed later, create a separate `notification_reads` table to track read status per user.

Do not implement `notification_reads` in V1 unless necessary.

---

## 17. Frontend Design

Frontend should show:

```text
notification bell
unread badge
notification dropdown
full notifications page
mark as read
mark all as read
click notification to navigate to reference
```

Navigation based on reference:

```text
reference_type = rfq → RFQ detail page
reference_type = purchase_lot → purchase lot detail
reference_type = product → product detail
reference_type = order → order detail
reference_type = stock_movement → stock movement/product stock page
```

Suggested French labels:

```js
const NOTIFICATION_TYPE_LABELS = {
  RFQ_ASSIGNED: "RFQ assignée",
  RFQ_QUOTED: "Devis reçu",
  RFQ_ACCEPTED_BY_OWNER: "Devis accepté",
  RFQ_REJECTED_BY_OWNER: "Devis rejeté",

  PURCHASE_LOT_DRAFT_CREATED_FROM_RFQ: "Lot d’achat brouillon créé",
  PURCHASE_LOT_NEEDS_FINALIZATION: "Lot d’achat à finaliser",
  PURCHASE_LOT_FINALIZED: "Lot d’achat finalisé",

  STOCK_IN_PURCHASE_RECEIVED: "Stock reçu",
  LOW_STOCK_ALERT: "Stock faible",
  OUT_OF_STOCK_ALERT: "Rupture de stock",

  ORDER_CREATED: "Nouvelle commande",
  ORDER_STATUS_CHANGED: "Statut de commande modifié"
};
```

---

## 18. Current Recommendation

Build V1 only.

V1:

```text
notifications table
GET /notifications
GET /notifications/unread-count
PATCH /notifications/{id}/read
PATCH /notifications/read-all

RFQ_ASSIGNED
RFQ_QUOTED
RFQ_ACCEPTED_BY_OWNER
RFQ_REJECTED_BY_OWNER
PURCHASE_LOT_DRAFT_CREATED_FROM_RFQ
PURCHASE_LOT_NEEDS_FINALIZATION
PURCHASE_LOT_FINALIZED
STOCK_IN_PURCHASE_RECEIVED
LOW_STOCK_ALERT
OUT_OF_STOCK_ALERT
ORDER_CREATED
ORDER_STATUS_CHANGED
```

Phase 2:

```text
more order-specific notifications
RFQ reminders
stock recovered
high-value/Class A stock risk
```

Phase 3:

```text
catalog/product data quality notifications
supplier/part-source notifications
recommendation data missing
```

Future:

```text
forecasting/reorder notifications
AI/chatbot notifications
```

---

## 19. Why This Is Good for Rabbit

Notifications connect the already-built backend modules:

```text
RFQ
purchase lots
stock
orders
inventory alerts
```

They make the platform feel alive and operational.

They are useful for frontend demo because users can see workflow events without manually opening every page.

The implementation stays simple and school-project appropriate:

```text
database-backed
synchronous creation
no Redis
no workers
no async infrastructure
```
