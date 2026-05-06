# Tested Outputs

## Confirm with no pending action

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"confirm\"}"
```

Passed:

```text
There is no pending action to confirm or cancel.
```

## Cancel with no pending action

Passed:

```text
intent = cancel_write_action
There is no pending action to confirm or cancel.
```

## Mark all notifications preview

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"mark all notifications as read\"}"
```

Passed:

```text
Chatbot action requires confirmation.
pending_action = true
unread_count = 18
ai_refined = false
```

## Confirm mark all

Passed:

```text
Confirmed. All unread notifications were marked as read.
executed = true
```

## Verify unread count

Passed:

```text
You have no unread notifications.
unread_count = 0
```

## Cancel path

Passed:

```text
pending action created
cancel cleared pending action
confirm after cancel returned no pending action
```

## Mark one notification

Passed:

```text
mark notification 1 as read
→ preview created
→ confirm executed
→ Notification #1 was marked as read
```
