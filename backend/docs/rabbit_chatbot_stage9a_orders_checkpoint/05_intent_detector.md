# OrderIntentDetector

The detector recognizes English and French order wording.

Examples:

```text
show orders
debug orders
show recent orders
debug recent orders
show pending orders
debug pending orders
show order 6
debug order 6
show commande 6
commandes en attente
```

Status aliases supported:

```text
pending / en attente
processing / en cours / traitement
shipped / expédiée / expediee
delivered / livrée / livree
cancelled / annulée / annulee
```

Detector ordering note:

`NotificationIntentDetector` runs before `OrderIntentDetector`, so:

```text
show order notifications
```

can still map to notification tools instead of order tools.
