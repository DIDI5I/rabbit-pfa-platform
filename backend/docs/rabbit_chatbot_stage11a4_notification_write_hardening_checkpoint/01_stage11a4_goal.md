# Stage 11A.4 Goal

The goal of this stage was to improve correctness after Stage 11A.2 and 11A.3.

Before this stage:

```text
mark all notifications as read
→ created a pending action even when unread_count = 0
```

and:

```text
mark notification 999999 as read
→ could say confirmed/executed even if the row was not updated
```

After this stage:

```text
unread_count = 0
→ no pending action is created
```

and:

```text
invalid/inaccessible notification ID
→ action could not be executed
→ executed = false
→ pending action cleared
```
