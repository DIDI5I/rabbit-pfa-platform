# Notification Presenters

Presenters convert tool output into the standard chatbot response format:

```text
answer
summary
items_preview
result_meta
sources
limitations
suggested_actions
```

## NotificationSummaryPresenter

Example:

```text
You have 20 notifications, including 18 unread. Showing 5.
```

## UnreadNotificationsPresenter

Example:

```text
You have 18 unread notifications. Showing 3.
```

## NotificationsByTypePresenter

Example:

```text
I found 3 notifications matching STOCK. Showing 3.
```

Preview fields:

```text
id
type
title
message
is_read
reference_type
reference_id
created_at
```
