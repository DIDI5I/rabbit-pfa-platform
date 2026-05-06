# ChatbotService Patch

`ChatbotService` was updated so write actions are not globally blocked too early.

Final order:

```text
classify message
unsupported check
confirm/cancel check
permission guard
notification write preview path
generic write-action block
normal read-only execution
```

This allows supported write actions to create pending previews, while unsupported write actions are still blocked.

Important:

```text
notification write previews run only after PermissionGuard passes
```
