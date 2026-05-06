# Tested Outputs

## Zero unread mark-all

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"mark all notifications as read\"}"
```

Passed:

```text
No notification action needed.
You have no unread notifications to mark as read.
pending_action = false
confirmation_required = false
```

Then:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"confirm\"}"
```

Passed:

```text
There is no pending action to confirm or cancel.
```

## Invalid notification ID

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"mark notification 999999 as read\"}"
```

Preview still created safely.

Then confirm:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"confirm\"}"
```

Passed:

```text
Chatbot action could not be executed.
Notification #999999 was not found, was already read, or is not accessible.
executed = false
pending_action = false
```

Then confirm again:

```text
There is no pending action to confirm or cancel.
```
