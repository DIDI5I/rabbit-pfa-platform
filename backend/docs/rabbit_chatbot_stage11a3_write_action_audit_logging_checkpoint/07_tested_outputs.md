# Tested Outputs

Tested flow:

```text
mark notification 1 as read
confirm
mark all notifications as read
cancel
```

Confirmed chatbot behavior:

```text
confirm with no pending action -> safe no-op
mark notification 1 as read -> preview created
confirm -> action executed
mark all notifications as read -> preview created
cancel -> pending action cleared
```

Confirmed log behavior:

```text
preview_created logged
confirmed logged
executed logged
cancelled logged
```

The server initially failed because it was not running, then worked after restart. This was not a backend logic issue.
