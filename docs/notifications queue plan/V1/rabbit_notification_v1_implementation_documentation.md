# Rabbit — Notification Queue V1 Implementation Documentation

## 1. Goal

Notification Queue V1 creates a simple database-backed notification inbox for Rabbit.

This is not a real asynchronous queue.

No Redis.
No workers.
No background jobs.
No external infrastructure.

For Rabbit V1, “notification queue” means:

```text
important backend event
→ insert notification row into database
→ frontend fetches notifications
→ user sees alert/message
```

This keeps the project simple and school-demo friendly.

---

## 2. Core Rule

Notifications must be generated from real backend events.

Do not let the frontend invent notifications.

Do not let AI invent notifications.

Notification rows should be created inside backend services such as:

```text
RfqService
PurchaseLotService
StockService
OrderService
```

V1 implementation created the notification infrastructure first.

Trigger integration comes next.

---

## 3. Database Table

Created table:

```sql
CREATE TABLE IF NOT EXISTS notifications (
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

---

## 4. user_id vs role

Notifications support two recipient styles.

### Specific user notification

Use `user_id`.

Example:

```text
user_id = 4
role = NULL
```

Use this for:

```text
client order status update
specific supplier user notification
specific user-only event
```

### Role notification

Use `role`.

Example:

```text
user_id = NULL
role = owner
```

Use this for:

```text
owner-wide alert
RFQ quoted notification for owner
low-stock alert for owner
purchase lot finalization alert
```

The notification listing logic returns notifications where:

```text
user_id = current user id
OR role = current user role
```

---

## 5. Implemented Files

Created:

```text
app/Queries/NotificationQuery.php
app/Repositories/NotificationRepository.php
app/Services/NotificationService.php
app/Controllers/NotificationController.php
```

Routes file updated with:

```php
use App\Controllers\NotificationController;
```

---

# 6. NotificationQuery.php

File:

```text
app/Queries/NotificationQuery.php
```

Created methods:

```php
public static function insert(): string
public static function listForUser(): string
public static function countForUser(): string
public static function unreadCountForUser(): string
public static function markAsRead(): string
public static function markAllAsRead(): string
```

## 6.1 insert()

Used by repository to create a notification.

```sql
INSERT INTO notifications (
    user_id,
    role,
    type,
    title,
    message,
    reference_type,
    reference_id
) VALUES (?, ?, ?, ?, ?, ?, ?)
```

## 6.2 listForUser()

Base query:

```sql
SELECT
    id,
    user_id,
    role,
    type,
    title,
    message,
    reference_type,
    reference_id,
    is_read,
    created_at,
    read_at
FROM notifications
WHERE
    (user_id = ? OR role = ?)
```

Important:

```text
No ORDER BY or LIMIT is inside this query.
Pagination is added safely in the repository.
```

## 6.3 countForUser()

Counts all visible notifications for a user/role.

## 6.4 unreadCountForUser()

Counts unread notifications where:

```sql
is_read = 0
```

## 6.5 markAsRead()

Marks one notification as read only if the current user is allowed to access it:

```sql
UPDATE notifications
SET
    is_read = 1,
    read_at = NOW()
WHERE id = ?
AND (user_id = ? OR role = ?)
```

## 6.6 markAllAsRead()

Marks all visible unread notifications as read:

```sql
UPDATE notifications
SET
    is_read = 1,
    read_at = NOW()
WHERE
    (user_id = ? OR role = ?)
    AND is_read = 0
```

---

# 7. NotificationRepository.php

File:

```text
app/Repositories/NotificationRepository.php
```

Created methods:

```php
public function create(...): void
public function listForUser(...): array
public function countForUser(...): int
public function unreadCountForUser(...): int
public function markAsRead(...): bool
public function markAllAsRead(...): void
```

## 7.1 create()

Inserts a notification row.

Signature:

```php
public function create(
    ?int $userId,
    ?string $role,
    string $type,
    string $title,
    string $message,
    ?string $referenceType = null,
    ?int $referenceId = null
): void
```

## 7.2 listForUser()

Supports:

```text
user_id
role
unread_only
limit
offset
```

Adds safely:

```sql
ORDER BY created_at DESC
LIMIT {$limit}
OFFSET {$offset}
```

Limits are sanitized:

```php
$limit = max(1, min($limit, 100));
$offset = max(0, $offset);
```

## 7.3 countForUser()

If `unread_only = true`, counts unread.

Otherwise counts all.

## 7.4 unreadCountForUser()

Used by:

```text
GET /notifications/unread-count
```

and by:

```text
GET /notifications
```

so the frontend can show unread badge.

## 7.5 markAsRead()

Marks a single notification read only if visible to the current user.

## 7.6 markAllAsRead()

Marks all visible unread notifications read.

## 7.7 Row Casting

The repository casts:

```text
id → int
user_id → int|null
reference_id → int|null
is_read → bool
```

---

# 8. NotificationService.php

File:

```text
app/Services/NotificationService.php
```

Created methods:

```php
public function notifyUser(...): void
public function notifyRole(...): void
public function list(array $filters = []): array
public function unreadCount(): array
public function markAsRead(int $notificationId): array
public function markAllAsRead(): array
```

## 8.1 notifyUser()

Use when notification belongs to one specific user.

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

Example future use:

```php
$notificationService->notifyUser(
    $clientUserId,
    'ORDER_STATUS_CHANGED',
    'Statut de commande mis à jour',
    'Votre commande est maintenant en traitement.',
    'order',
    $orderId
);
```

## 8.2 notifyRole()

Use when notification belongs to all users with a role.

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

Example future use:

```php
$notificationService->notifyRole(
    'owner',
    'RFQ_QUOTED',
    'Nouveau devis reçu',
    'Un fournisseur a répondu à une RFQ.',
    'rfq',
    $rfqId
);
```

## 8.3 list()

Reads current user from:

```php
Auth::id()
Auth::role()
```

Supports filters:

```text
page
limit
unread_only
```

Returns Rabbit standard response:

```json
{
  "message": "Notifications fetched successfully",
  "data": {
    "items": [],
    "unread_count": 0,
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 0,
      "total_pages": 0
    }
  }
}
```

## 8.4 unreadCount()

Returns:

```json
{
  "message": "Unread notification count fetched successfully",
  "data": {
    "unread_count": 0
  }
}
```

## 8.5 markAsRead()

Returns:

```json
{
  "message": "Notification marked as read successfully",
  "data": {
    "id": 1,
    "is_read": true
  }
}
```

## 8.6 markAllAsRead()

Returns:

```json
{
  "message": "All notifications marked as read successfully",
  "data": {
    "updated": true
  }
}
```

---

# 9. NotificationController.php

File:

```text
app/Controllers/NotificationController.php
```

Created methods:

```php
public function index(): array
public function unreadCount(): array
public function markAsRead(int $id): array
public function markAllAsRead(): array
```

Controller code:

```php
<?php

namespace App\Controllers;

use App\Services\NotificationService;

class NotificationController
{
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    public function index(): array
    {
        return $this->notificationService->list($_GET);
    }

    public function unreadCount(): array
    {
        return $this->notificationService->unreadCount();
    }

    public function markAsRead(int $id): array
    {
        return $this->notificationService->markAsRead($id);
    }

    public function markAllAsRead(): array
    {
        return $this->notificationService->markAllAsRead();
    }
}
```

---

# 10. Routes

Add import:

```php
use App\Controllers\NotificationController;
```

Routes:

```php
// NOTIFICATIONS
$router->getRoute('/notifications', [NotificationController::class, 'index'])
       ->only([AuthMiddleware::class]);

$router->getRoute('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
       ->only([AuthMiddleware::class]);

$router->patchRoute('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
       ->only([AuthMiddleware::class]);

$router->patchRoute('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
       ->only([AuthMiddleware::class]);
```

Important order:

```text
/notifications
/notifications/unread-count
/notifications/read-all
/notifications/{id}/read
```

Specific routes must come before the dynamic `{id}` route.

---

# 11. Manual Test Insert

Insert one owner notification:

```sql
INSERT INTO notifications (
    role,
    type,
    title,
    message,
    reference_type,
    reference_id
)
VALUES (
    'owner',
    'RFQ_QUOTED',
    'Nouveau devis reçu',
    'Un fournisseur a répondu à une RFQ.',
    'rfq',
    1
);
```

Then log in as owner and test:

```http
GET /notifications
```

Expected:

```json
{
  "message": "Notifications fetched successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "user_id": null,
        "role": "owner",
        "type": "RFQ_QUOTED",
        "title": "Nouveau devis reçu",
        "message": "Un fournisseur a répondu à une RFQ.",
        "reference_type": "rfq",
        "reference_id": 1,
        "is_read": false,
        "created_at": "...",
        "read_at": null
      }
    ],
    "unread_count": 1,
    "pagination": {}
  }
}
```

Test unread count:

```http
GET /notifications/unread-count
```

Expected:

```json
{
  "message": "Unread notification count fetched successfully",
  "data": {
    "unread_count": 1
  }
}
```

Mark one read:

```http
PATCH /notifications/1/read
```

Expected:

```json
{
  "message": "Notification marked as read successfully",
  "data": {
    "id": 1,
    "is_read": true
  }
}
```

Mark all read:

```http
PATCH /notifications/read-all
```

Expected:

```json
{
  "message": "All notifications marked as read successfully",
  "data": {
    "updated": true
  }
}
```

---

# 12. Supported Query Parameters

For:

```http
GET /notifications
```

Supported filters:

```text
page
limit
unread_only
```

Examples:

```http
GET /notifications?page=1&limit=20
GET /notifications?unread_only=true
GET /notifications?unread_only=false&page=2&limit=10
```

---

# 13. V1 Notification Types Planned

The infrastructure supports all notification types, but triggers are not integrated yet.

Recommended V1 types:

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

---

# 14. Trigger Integration Comes Next

The notification infrastructure is now built.

Next step is to add calls to `NotificationService` inside existing services.

## 14.1 RfqService

Planned triggers:

```text
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
→ RFQ_EXPIRED later or V1 if easy
```

## 14.2 PurchaseLotService

Planned triggers:

```text
create()
→ PURCHASE_LOT_NEEDS_FINALIZATION if draft

finalize()
→ PURCHASE_LOT_FINALIZED
→ STOCK_IN_PURCHASE_RECEIVED

cancel()
→ PURCHASE_LOT_CANCELLED later
```

## 14.3 StockService

Planned triggers:

```text
recordMovement()
→ LOW_STOCK_ALERT
→ OUT_OF_STOCK_ALERT
```

Optional:

```text
STOCK_MOVEMENT_CREATED
STOCK_MANUAL_ADJUSTMENT
STOCK_RECOVERED
```

Add later.

## 14.4 OrderService

Planned triggers:

```text
create()
→ ORDER_CREATED for owner

updateStatus()
→ ORDER_STATUS_CHANGED for client
```

Optional later:

```text
ORDER_SHIPPED
ORDER_DELIVERED
ORDER_CANCELLED
```

For V1, `ORDER_STATUS_CHANGED` is enough.

---

# 15. Role Targets

## Owner receives

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

Implementation:

```php
$notificationService->notifyRole('owner', ...);
```

## Supplier receives

```text
RFQ_ASSIGNED
RFQ_ACCEPTED_BY_OWNER
RFQ_REJECTED_BY_OWNER
RFQ_EXPIRED
```

Implementation can be:

```php
$notificationService->notifyUser($supplierUserId, ...);
```

or:

```php
notify role = fournisseur
```

But supplier notifications should ideally target the specific supplier user linked by:

```text
users.supplier_company_id = rfq_requests.supplier_id
```

If that lookup is not ready yet, use role-level supplier notifications carefully.

## Client receives

```text
ORDER_STATUS_CHANGED
```

Implementation:

```php
$notificationService->notifyUser($orderOwnerUserId, ...);
```

---

# 16. Important Security Rules

A user can only read notifications where:

```text
notification.user_id = current_user_id
OR notification.role = current_user_role
```

A user cannot mark another user’s notification as read.

Current implementation enforces this in:

```text
NotificationQuery::markAsRead()
NotificationQuery::markAllAsRead()
NotificationQuery::listForUser()
NotificationQuery::countForUser()
NotificationQuery::unreadCountForUser()
```

---

# 17. Known V1 Limitation

Role-level notifications have shared read state.

Example:

```text
role = owner
is_read = false
```

If one owner marks it as read, it becomes read for all owners.

For school project V1, this is acceptable.

If needed later, add:

```text
notification_reads
```

to track read status per user.

Do not add `notification_reads` now unless necessary.

---

# 18. Frontend Usage

Frontend should implement:

```text
notification bell
unread count badge
notification dropdown
notifications page
mark one as read
mark all as read
click notification to navigate to reference
```

Reference navigation:

```text
reference_type = rfq → RFQ detail page
reference_type = purchase_lot → purchase lot detail page
reference_type = product → product detail page
reference_type = order → order detail page
reference_type = stock_movement → stock/product movement page
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

# 19. Current Status

Notification Queue V1 infrastructure:

```text
database table ✅
query class ✅
repository ✅
service ✅
controller ✅
routes planned ✅
manual test flow defined ✅
```

Still remaining:

```text
run endpoint tests
integrate notification triggers into services
document trigger implementation after completion
```

---

# 20. Recommended Next Step

Next step:

```text
Test notification endpoints manually.
```

Then:

```text
Integrate RFQ notification triggers first.
```

Recommended order:

```text
1. Test GET /notifications
2. Test GET /notifications/unread-count
3. Test PATCH /notifications/{id}/read
4. Test PATCH /notifications/read-all
5. Add RFQ_QUOTED notification in RfqService::quote()
6. Add RFQ_ASSIGNED notification in RfqService::open()
7. Add accepted/rejected supplier notifications
```
