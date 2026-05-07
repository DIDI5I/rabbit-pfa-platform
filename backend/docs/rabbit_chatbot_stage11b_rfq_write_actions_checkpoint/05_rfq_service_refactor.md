# RFQ Service Refactor

Before Stage 11B, RFQ service methods depended on HTTP request classes:

```php
accept(AcceptRfqRequest $request)
reject(AcceptRfqRequest $request)
expire(AcceptRfqRequest $request)
open(AcceptRfqRequest $request)
```

Those request classes read from `php://input`.

Stage 11B added data-based execution methods:

```php
acceptByData(int $rfqId, ?string $decisionNote, int $userId)
rejectByData(int $rfqId, ?string $decisionNote, int $userId)
expireByData(int $rfqId, ?string $decisionNote, int $userId)
openByData(int $rfqId, ?string $decisionNote, int $userId)
```

The old controller methods remain wrappers around the new data methods.
