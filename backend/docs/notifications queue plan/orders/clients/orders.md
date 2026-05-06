# Rabbit Backend Update — Order Notifications Implemented

## Status

Order notifications are now implemented and tested successfully.

Completed notification types:

```text
ORDER_CREATED
ORDER_STATUS_CHANGED

This completes the lean V1 notification system.

Purpose

Order notifications cover two user-facing order events:

1. A new order is created.
2. An existing order status changes.

The goal is to keep:

owner informed about new customer activity
client informed about progress on their own order
Files Modified

Main file modified:

app/Services/OrderService.php

Also updated or confirmed:

app/Repositories/OrderRepository.php

Important repository improvement:

OrderRepository::findById() should return client_name using a JOIN with users.

Example query shape:

SELECT
    o.id,
    o.client_id,
    u.name AS client_name,
    o.status,
    o.shipping_address,
    o.total_amount,
    o.created_at,
    o.updated_at
FROM orders o
LEFT JOIN users u ON u.id = o.client_id
WHERE o.id = ?
LIMIT 1

This allows notifications to show a human-readable client name instead of only client_id.

ORDER_CREATED
Trigger

Triggered when a new order is created successfully.

Location:

OrderService::create()

Placed after:

$order = $this->orders->findById($orderId);
$order['items'] = $this->orders->findItemsByOrderId($orderId);

Final shape:

$order = $this->orders->findById($orderId);
$order['items'] = $this->orders->findItemsByOrderId($orderId);

$this->notifyOwnerOrderCreated($order);

return ApiResponse::success('Order created successfully', $order);
Recipient

Sent to:

role = owner

Using:

NotificationService::notifyRole()
Notification Data
type = ORDER_CREATED
title = Nouvelle commande
reference_type = order
reference_id = created order id

Example message:

Une nouvelle commande #5 a été créée par Client Test Rabbit. Montant total: 100 MAD.

Earlier version used:

client #2

This was improved to use:

client_name

from the users table.

ORDER_STATUS_CHANGED
Trigger

Triggered when an order status is successfully changed.

Location:

OrderService::updateStatus()

Placed after:

$updatedOrder = $this->orders->findById($orderId);
$updatedOrder['items'] = $this->orders->findItemsByOrderId($orderId);

Final shape:

$updatedOrder = $this->orders->findById($orderId);
$updatedOrder['items'] = $this->orders->findItemsByOrderId($orderId);

$this->notifyClientOrderStatusChanged($updatedOrder, $order['status'], $newStatus);

return ApiResponse::success('Order status updated successfully', $updatedOrder);
Recipient

Sent to:

user_id = order.client_id

Using:

NotificationService::notifyUser()
Notification Data
type = ORDER_STATUS_CHANGED
title = Statut de commande modifié
reference_type = order
reference_id = updated order id

Example message:

Le statut de votre commande #5 est passé de pending à processing.

The notification message can use raw backend statuses for now:

pending
processing
cancelled
completed

Frontend can later map them to French labels.

Helper Methods Added
notifyOwnerOrderCreated()
private function notifyOwnerOrderCreated(array $order): void
{
    $notifications = new NotificationService();

    $orderId = (int) $order['id'];
    $clientName = $order['client_name'] ?? 'Client inconnu';
    $totalAmount = (float) $order['total_amount'];

    $notifications->notifyRole(
        'owner',
        'ORDER_CREATED',
        'Nouvelle commande',
        "Une nouvelle commande #{$orderId} a été créée par {$clientName}. Montant total: {$totalAmount} MAD.",
        'order',
        $orderId
    );
}
notifyClientOrderStatusChanged()
private function notifyClientOrderStatusChanged(
    array $order,
    string $oldStatus,
    string $newStatus
): void {
    if ($oldStatus === $newStatus) {
        return;
    }

    $notifications = new NotificationService();

    $orderId = (int) $order['id'];
    $clientId = (int) $order['client_id'];

    $notifications->notifyUser(
        $clientId,
        'ORDER_STATUS_CHANGED',
        'Statut de commande modifié',
        "Le statut de votre commande #{$orderId} est passé de {$oldStatus} à {$newStatus}.",
        'order',
        $orderId
    );
}

Important guard:

if ($oldStatus === $newStatus) {
    return;
}

This prevents duplicate/no-op status notifications.

Interaction With Stock Movements

Existing order stock logic remains unchanged.

Important rule:

pending → no stock movement
processing → SALE stock OUT
cancelled after processing → CANCELLED_ORDER_RESTORE stock IN

When an order status changes to:

processing

the existing order service creates stock OUT movements.

Therefore, this action may also trigger:

LOW_STOCK_ALERT
OUT_OF_STOCK_ALERT

This is correct.

Reason:

ORDER_STATUS_CHANGED notifies the client.
LOW_STOCK_ALERT / OUT_OF_STOCK_ALERT notifies the owner about stock risk.

They are separate concerns.

Tests Completed
Test Client Registration

A new client was registered successfully.

Example body:

{
  "name": "Client Test Rabbit",
  "email": "client.test.rabbit@example.com",
  "password": "password",
  "role": "client"
}

The created client received:

user_id = 11
role = client
Test 1 — ORDER_CREATED

A new order was created for the test client.

Example body:

{
  "client_id": 11,
  "shipping_address": "Test address, Casablanca",
  "items": [
    {
      "component_id": 1,
      "quantity": 1,
      "unit_price": 100
    }
  ]
}

Expected result:

Owner receives ORDER_CREATED notification.

Result:

Test passed.

Owner notification example:

type = ORDER_CREATED
title = Nouvelle commande
reference_type = order
reference_id = 5

Message now uses client name instead of client id.

Test 2 — ORDER_STATUS_CHANGED

The owner changed the order status:

PATCH /orders/5/status

Body:

{
  "status": "processing"
}

Expected result:

Client receives ORDER_STATUS_CHANGED notification.

Result:

Test passed.

Actual notification response:

{
  "message": "Notifications fetched successfully",
  "data": {
    "items": [
      {
        "id": 14,
        "user_id": 11,
        "role": null,
        "type": "ORDER_STATUS_CHANGED",
        "title": "Statut de commande modifié",
        "message": "Le statut de votre commande #5 est passé de pending à processing.",
        "reference_type": "order",
        "reference_id": 5,
        "is_read": false,
        "created_at": "2026-05-03 11:25:43",
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

Confirmed:

notification type = ORDER_STATUS_CHANGED
recipient user_id = 11
reference_type = order
reference_id = 5
status transition = pending → processing
unread_count = 1
Current Notification System Status

The full lean V1 notification set is now implemented and tested.

RFQ_ASSIGNED                      done
RFQ_QUOTED                        done
RFQ_ACCEPTED_BY_OWNER              done
RFQ_REJECTED_BY_OWNER              done
PURCHASE_LOT_NEEDS_FINALIZATION    done
PURCHASE_LOT_FINALIZED             done
LOW_STOCK_ALERT                    done
OUT_OF_STOCK_ALERT                 done
ORDER_CREATED                      done
ORDER_STATUS_CHANGED               done
Notification Philosophy Confirmed

Rabbit notifications should remain lean.

Notify only for:

action required
workflow handoff
risk/alert
important user-facing status change

Do not notify for every internal event.

Skipped intentionally:

RFQ_CREATED
RFQ_OPENED
RFQ_EXPIRED
PURCHASE_LOT_DRAFT_CREATED_FROM_RFQ
STOCK_IN_PURCHASE_RECEIVED

Reason:

They are redundant, low-signal, or internal workflow events.
Frontend Notification Labels

Frontend should support:

const NOTIFICATION_TYPE_LABELS = {
  RFQ_ASSIGNED: "RFQ assignée",
  RFQ_QUOTED: "Devis reçu",
  RFQ_ACCEPTED_BY_OWNER: "Devis accepté",
  RFQ_REJECTED_BY_OWNER: "Devis rejeté",
  PURCHASE_LOT_NEEDS_FINALIZATION: "Lot d’achat à finaliser",
  PURCHASE_LOT_FINALIZED: "Lot d’achat finalisé",
  LOW_STOCK_ALERT: "Stock faible",
  OUT_OF_STOCK_ALERT: "Rupture de stock",
  ORDER_CREATED: "Nouvelle commande",
  ORDER_STATUS_CHANGED: "Statut de commande modifié"
};

Optional frontend status labels:

const ORDER_STATUS_LABELS = {
  pending: "En attente",
  processing: "En traitement",
  completed: "Terminée",
  cancelled: "Annulée"
};
Backend Completion Update

Completed recently:

stock alert notifications
RFQ rejection notification test
order creation notification
order status changed notification