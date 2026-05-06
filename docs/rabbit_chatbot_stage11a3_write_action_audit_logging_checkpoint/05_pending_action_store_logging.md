# PendingActionStore Logging

`PendingActionStore::put()` now logs:

```text
preview_created
```

after the pending action is stored in session.

`PendingActionStore::get()` now logs:

```text
expired
```

when a pending action exists but has passed its expiry time.
