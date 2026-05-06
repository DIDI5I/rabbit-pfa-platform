# Final File Structure

The write-action system was organized by domain:

```text
app/Services/Chatbot/WriteActions/
    PendingActionStore.php
    WriteActionResponseBuilder.php
    WriteActionExecutor.php

    Contracts/
        PendingActionExecutorInterface.php

    Notification/
        NotificationWriteIntentDetector.php
        NotificationWriteToolRegistry.php
        MarkAllNotificationsReadPreviewTool.php
        MarkNotificationReadPreviewTool.php
        MarkAllNotificationsReadExecutor.php
        MarkNotificationReadExecutor.php
```

This structure keeps future write actions clean.

Future domains can be added as:

```text
WriteActions/Rfq/
WriteActions/Order/
WriteActions/Product/
WriteActions/Stock/
WriteActions/Promotion/
```
