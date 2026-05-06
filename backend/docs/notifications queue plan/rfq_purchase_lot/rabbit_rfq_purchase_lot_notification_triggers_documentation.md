# Rabbit — RFQ & Purchase Lot Notification Trigger Integration Documentation

## 1. Goal

This document records the completed notification trigger integration for Rabbit’s RFQ and purchase lot workflow.

The notification infrastructure already existed:

```text
notifications table
NotificationQuery
NotificationRepository
NotificationService
NotificationController
notification routes
```

This stage connected real backend workflow events to notification creation.

The main tested flow:

```text
owner creates RFQ
→ owner opens RFQ
→ supplier receives RFQ assignment notification
→ supplier quotes RFQ
→ owner receives RFQ quoted notification
→ owner accepts RFQ
→ supplier receives quote accepted notification
→ owner receives purchase lot finalization-needed notification
→ draft purchase lot is created
→ owner finalizes purchase lot
→ stock increases
→ owner receives purchase lot finalized notification
```

---

## 2. Important Design Decision: No Notification Spam

We decided not to notify every action.

Rabbit notifications should only be created when:

```text
1. A user needs to take action.
2. There is an important workflow handoff.
3. There is a risk/alert.
4. A user-facing status changed.
```

Do not notify every internal state change.

This avoids a noisy notification system.

---

## 3. Trimmed Notification Set for This Stage

Implemented / targeted in this stage:

```text
RFQ_ASSIGNED
RFQ_QUOTED
RFQ_ACCEPTED_BY_OWNER
RFQ_REJECTED_BY_OWNER
PURCHASE_LOT_NEEDS_FINALIZATION
PURCHASE_LOT_FINALIZED
```

Skipped intentionally:

```text
RFQ_CREATED
RFQ_OPENED
RFQ_EXPIRED
PURCHASE_LOT_DRAFT_CREATED_FROM_RFQ
STOCK_IN_PURCHASE_RECEIVED
```

Reason:

```text
RFQ_CREATED: owner created it themselves.
RFQ_OPENED: duplicate of RFQ_ASSIGNED from supplier perspective.
RFQ_EXPIRED: useful later, not essential now.
PURCHASE_LOT_DRAFT_CREATED_FROM_RFQ: redundant with PURCHASE_LOT_NEEDS_FINALIZATION.
STOCK_IN_PURCHASE_RECEIVED: redundant with PURCHASE_LOT_FINALIZED.
```

Final clean rule:

```text
Notification quality > notification quantity.
```

---

## 4. UserRepository Addition

To notify the correct supplier users, we added a supplier-company lookup method.

File:

```text
app/Repositories/UserRepository.php
```

Method:

```php
public function findSupplierUsersByCompanyId(int $supplierCompanyId): array
{
    return $this
        ->query(
            "SELECT id, name, email, role, supplier_company_id
             FROM users
             WHERE role = ?
             AND supplier_company_id = ?
             AND is_active = 1",
            ['fournisseur', $supplierCompanyId]
        )
        ->fetchMany();
}
```

Purpose:

```text
Notify only supplier users linked to the RFQ supplier company.
Do not notify all suppliers.
```

Important role rule:

```text
users.supplier_company_id = suppliers.id
rfq_requests.supplier_id = suppliers.id
```

So supplier notification target is:

```text
users where role = fournisseur
and users.supplier_company_id = rfq_requests.supplier_id
```

---

## 5. RfqService Helper Methods

File:

```text
app/Services/RfqService.php
```

Imports added:

```php
use App\Repositories\UserRepository;
use App\Services\NotificationService;
```

Helper for owner notifications:

```php
private function notifyOwner(
    string $type,
    string $title,
    string $message,
    string $referenceType,
    int $referenceId
): void {
    $notifications = new NotificationService();

    $notifications->notifyRole(
        'owner',
        $type,
        $title,
        $message,
        $referenceType,
        $referenceId
    );
}
```

Helper for supplier-company notifications:

```php
private function notifySupplierCompany(
    int $supplierCompanyId,
    string $type,
    string $title,
    string $message,
    string $referenceType,
    int $referenceId
): void {
    $userRepository = new UserRepository();

    $supplierUsers = $userRepository->findSupplierUsersByCompanyId($supplierCompanyId);

    $notifications = new NotificationService();

    foreach ($supplierUsers as $supplierUser) {
        $notifications->notifyUser(
            (int) $supplierUser['id'],
            $type,
            $title,
            $message,
            $referenceType,
            $referenceId
        );
    }
}
```

---

## 6. RFQ Open Trigger

When owner opens an RFQ, the assigned supplier should be notified.

Trigger:

```text
RfqService::open()
→ RFQ_ASSIGNED
```

Notification:

```php
$rfq = $this->repository->findById($request->rfqId());

if ($rfq && !empty($rfq['supplier_id'])) {
    $this->notifySupplierCompany(
        (int) $rfq['supplier_id'],
        'RFQ_ASSIGNED',
        'Nouvelle RFQ assignée',
        'Une nouvelle demande de devis vous a été envoyée.',
        'rfq',
        $request->rfqId()
    );
}
```

Recipient:

```text
supplier users linked to rfq.supplier_id
```

Reference:

```text
reference_type = rfq
reference_id = RFQ id
```

Reason:

```text
Supplier needs to take action and quote the RFQ.
```

---

## 7. RFQ Quote Trigger

When supplier quotes an RFQ, the owner should be notified.

Trigger:

```text
RfqService::quote()
→ RFQ_QUOTED
```

Notification:

```php
$this->notifyOwner(
    'RFQ_QUOTED',
    'Nouveau devis reçu',
    'Un fournisseur a répondu à une demande de devis.',
    'rfq',
    $id
);
```

Important bug fixed:

```text
Do not use $rfqId if that variable does not exist.
Use the actual method parameter, usually $id.
```

Old bad notification issue:

```text
A test notification had reference_id = null because of the earlier $rfqId bug.
```

Correct:

```text
reference_type = rfq
reference_id = actual RFQ id
```

Reason:

```text
Owner needs to decide whether to accept or reject the quote.
```

---

## 8. RFQ Accept Triggers

When owner accepts an RFQ, two notifications are useful:

```text
1. Supplier should know their quote was accepted.
2. Owner should know a purchase lot now needs finalization.
```

Triggers:

```text
RfqService::accept()
→ RFQ_ACCEPTED_BY_OWNER
→ PURCHASE_LOT_NEEDS_FINALIZATION
```

Supplier notification:

```php
$rfq = $this->repository->findById($request->rfqId());

if ($rfq && !empty($rfq['supplier_id'])) {
    $this->notifySupplierCompany(
        (int) $rfq['supplier_id'],
        'RFQ_ACCEPTED_BY_OWNER',
        'Devis accepté',
        'Votre devis a été accepté par le propriétaire.',
        'rfq',
        $request->rfqId()
    );
}
```

Owner notification:

```php
$this->notifyOwner(
    'PURCHASE_LOT_NEEDS_FINALIZATION',
    'Lot d’achat à finaliser',
    'Une RFQ acceptée a généré un lot d’achat à finaliser.',
    'rfq',
    $request->rfqId()
);
```

Important design choice:

```text
We intentionally use only PURCHASE_LOT_NEEDS_FINALIZATION.
We do not also send PURCHASE_LOT_DRAFT_CREATED_FROM_RFQ.
```

Reason:

```text
The draft purchase lot exists because the owner needs to finalize it.
One notification is enough.
```

---

## 9. RFQ Reject Trigger

When owner rejects an RFQ quote, supplier should be notified.

Trigger:

```text
RfqService::reject()
→ RFQ_REJECTED_BY_OWNER
```

Notification:

```php
$rfq = $this->repository->findById($request->rfqId());

if ($rfq && !empty($rfq['supplier_id'])) {
    $this->notifySupplierCompany(
        (int) $rfq['supplier_id'],
        'RFQ_REJECTED_BY_OWNER',
        'Devis rejeté',
        'Votre devis n’a pas été retenu.',
        'rfq',
        $request->rfqId()
    );
}
```

Status:

```text
Path prepared / intended.
```

If not fully tested yet, test later with a separate RFQ.

---

## 10. Purchase Lot Transaction Bug

During RFQ accept, this error appeared:

```text
There is already an active transaction.
```

Cause:

```text
RfqService::accept() started a transaction.
Then PurchaseLotService::createFromAcceptedRfq() called PurchaseLotService::create().
PurchaseLotService::create() tried to start another transaction.
```

Bad flow:

```text
RfqService::accept()
→ beginTransaction()
→ createFromAcceptedRfq()
→ create()
→ beginTransaction()
→ error
```

Correct architecture:

```text
The outer service owns the transaction.
Internal methods called inside that transaction should not start another transaction.
```

For RFQ accept:

```text
RfqService::accept() owns the transaction.
Purchase lot creation inside accept must not begin/commit/rollback.
```

---

## 11. PurchaseLotService Refactor

File:

```text
app/Services/PurchaseLotService.php
```

Before, `create()` directly contained transaction + insert logic.

It was refactored into two methods.

### 11.1 Public create() keeps transaction for normal API calls

```php
public function create(StorePurchaseLotRequest $request, ?int $createdBy = null): array
{
    $database = App::resolve(Database::class);
    $pdo = $database->connection();

    try {
        $pdo->beginTransaction();

        $purchaseLot = $this->createWithoutTransaction($request, $createdBy);

        $pdo->commit();

        return ApiResponse::success('Purchase lot created successfully', $purchaseLot);

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}
```

### 11.2 Internal createWithoutTransaction()

```php
public function createWithoutTransaction(StorePurchaseLotRequest $request, ?int $createdBy = null): array
{
    $data = $request->data();
    $data['created_by'] = $createdBy;

    $purchaseLotId = $this->purchaseLotRepository->create($data);

    return $this->purchaseLotRepository->findById($purchaseLotId);
}
```

Difference:

```text
create()
→ returns full API response
→ owns transaction

createWithoutTransaction()
→ returns raw purchase lot array
→ does not begin/commit/rollback
```

---

## 12. createFromAcceptedRfq() Fix

File:

```text
app/Services/PurchaseLotService.php
```

The method creates a draft purchase lot from accepted RFQ.

Important final line changed from:

```php
return $this->create($request, $createdBy);
```

to:

```php
return $this->createWithoutTransaction($request, $createdBy);
```

Result:

```text
RFQ accept no longer throws nested transaction error.
```

---

## 13. Purchase Lot Finalized Notification

We decided to use only one notification:

```text
PURCHASE_LOT_FINALIZED
```

Skipped:

```text
STOCK_IN_PURCHASE_RECEIVED
```

Reason:

```text
Finalizing the purchase lot already implies stock was received/updated.
A second stock notification would be redundant.
```

Trigger:

```text
PurchaseLotService::finalize()
→ PURCHASE_LOT_FINALIZED
```

Correct placement:

```text
after purchase lot status becomes finalized
after stock IN movement is created
before return ApiResponse::success(...)
```

Correct notification:

```php
$notificationService = new NotificationService();

$notificationService->notifyRole(
    'owner',
    'PURCHASE_LOT_FINALIZED',
    'Lot d’achat finalisé',
    'Un lot d’achat a été finalisé et le stock correspondant a été mis à jour.',
    'purchase_lot',
    $purchaseLotId
);
```

Bug fixed:

```text
The notification was initially placed inside:
if ($purchaseLot['status'] === 'finalized')
which only runs when the lot is already finalized and throws an error.
```

Correct behavior:

```text
if already finalized → throw validation error, no notification
if finalization succeeds → create notification once
```

---

## 14. Validated End-to-End Flow

The full procurement notification flow now works.

Validated:

```text
RFQ created ✅
RFQ opened ✅
supplier quoted ✅
owner accepted ✅
draft purchase lot created ✅
notifications working ✅
stock does not increase on RFQ accept ✅
purchase lot finalized ✅
stock increases on finalization ✅
owner notified ✅
supplier notified ✅
```

Important business rule confirmed:

```text
RFQ accepted ≠ stock received
Purchase lot finalized = stock received
```

---

## 15. Current Final Notification Set

Currently implemented/confirmed for workflow:

```text
RFQ_ASSIGNED
RFQ_QUOTED
RFQ_ACCEPTED_BY_OWNER
PURCHASE_LOT_NEEDS_FINALIZATION
PURCHASE_LOT_FINALIZED
```

Prepared/intended:

```text
RFQ_REJECTED_BY_OWNER
```

Next planned notification types:

```text
LOW_STOCK_ALERT
OUT_OF_STOCK_ALERT
ORDER_CREATED
ORDER_STATUS_CHANGED
```

---

## 16. Next Stage

Next stage:

```text
Stock alert notifications
```

Only two:

```text
LOW_STOCK_ALERT
OUT_OF_STOCK_ALERT
```

Important rule for next stage:

```text
Only notify when stock crosses into LOW or OUT status.
Do not notify every time a stock movement happens while the product is already low/out.
```

Correct behavior:

```text
OK → LOW
→ create LOW_STOCK_ALERT

OK → OUT
→ create OUT_OF_STOCK_ALERT

LOW → OUT
→ create OUT_OF_STOCK_ALERT

LOW → LOW
→ no new notification

OUT → OUT
→ no new notification

LOW/OUT → OK
→ optional STOCK_RECOVERED later, not V1
```

---

## 17. Recommended Next Implementation Order

```text
1. Inspect current StockService / stock movement creation method.
2. Identify where stock movement is inserted.
3. Calculate stock status before movement.
4. Insert movement.
5. Calculate stock status after movement.
6. If status crossed into low/out, notify owner.
7. Test with controlled stock movement.
```

Do not add order notifications yet until stock alerts are done.

---

## 18. Current Rabbit Backend Status After This Stage

```text
products/catalog ✅
product filtering/search ✅
dependencies ✅
recommendations V1 ✅
stock movements ✅
inventory ✅
orders basic ✅
RFQs basic ✅
purchase lots/cost intelligence ✅
RFQ → purchase lot → stock flow ✅
dashboard stock V1 ✅
ABC/Gini ✅
notification infrastructure ✅
RFQ notifications ✅
purchase lot finalization notification ✅
partial role hardening ✅
```
