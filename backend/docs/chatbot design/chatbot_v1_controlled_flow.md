# Rabbit Chatbot V1 Controlled Flow

## Purpose

Rabbit Chatbot V1 is a controlled backend workflow assistant.

It translates natural language into safe backend operations while respecting:

- authentication state
- user role
- allowed tools
- memory scope
- record-level access
- backend response convention
- final security verification

It is not a fully autonomous agent.

## Flow Overview

```text
Stage 0: User Identification
    ↓
Stage 1: Role Identification
    ↓
Stage 2: Deterministic Intent Parser
    ↓
Stage 3: API Intent Parser Fallback, only if needed
    ↓
Stage 4: Operation Type Classification
    ↓
Stage 5: Exact Tool / Resource Selection
    ↓
Stage 6: Permission + Scope Verification
    ↓
Stage 7: Tool Execution
    ↓
Stage 8: Deterministic Answer Composer
    ↓
Stage 10: API Answer Composer Fallback, only if needed
    ↓
Stage 9: Security / Result Verification
    ↓
User Result or Fail Sequence
```

---

## Stage 0 — User Identification

The system starts by reading the current session/auth state.

Decision:

```text
Authenticated?
```

### If not authenticated

The user follows the guest path.

Guest path allows only:

- public-safe help
- login/register suggestion
- account creation guidance
- no internal tools
- no owner/client/supplier data

Guest response must follow backend convention.

Example:

```json
{
  "message": "Authentication required.",
  "data": {
    "answer": "Please log in or create an account to use Rabbit assistant features.",
    "intent": "authentication_required",
    "confidence": "high",
    "role": "guest",
    "operation_type": "authentication",
    "sources": [],
    "limitations": [
      "No authenticated session was found."
    ],
    "suggested_actions": [
      "login",
      "register"
    ]
  }
}
```

---

## Stage 1 — Role Identification

If authenticated, identify role from session.

Possible roles:

```text
owner
client
supplier
```

Also referred to as:

```text
fournisseur = supplier
```

Based on role, load:

- RoleProfile
- ToolRegistry
- MemoryPolicy
- SystemProfile/SystemPrompt
- AccessScope

This stage determines what the chatbot is allowed to consider.

---

## Stage 2 — Deterministic Intent Parser

This is a non-API parser.

It uses rules and keyword logic first.

It extracts:

- intent
- parameters
- confidence score

Example:

```json
{
  "intent": "inventory_alerts",
  "params": {},
  "confidence": 0.92,
  "source": "rules"
}
```

Decision:

```text
High confidence?
```

If yes, continue to Stage 4.

If no, continue to Stage 3.

---

## Stage 3 — API Intent Parser Fallback

Only used when deterministic intent parsing has low confidence.

Rules:

- API returns structured intent JSON only.
- API does not answer the user.
- API does not execute tools.
- API does not decide permissions.
- API output must be validated.

Valid API output example:

```json
{
  "intent": "inventory_alerts",
  "params": {},
  "confidence": 0.84
}
```

If valid, rejoin at Stage 4.

If invalid, go to Fail Sequence.

---

## Stage 4 — Operation Type Classification

Classify the validated intent as one of:

```text
read_only
write_action
navigation
unsupported
```

### Unsupported

Go to Fail Sequence.

### Write action

For Chatbot V1, write actions are disabled.

Return safe informational response.

Future versions may support:

```text
draft action → user confirmation → backend execution → audit log
```

### Read-only or navigation

Continue to Stage 5.

---

## Stage 5 — Exact Tool / Resource Selection

Select only the exact backend tool/resource required by the intent.

Rules:

- never unlock all tools
- never expose unnecessary resources
- determine required params
- use role-safe memory only to resolve missing params
- if memory cannot resolve missing params, return a needs-more-information response

Example mappings:

```text
inventory_alerts → InventoryQuery::alerts / InventoryService
inventory_summary → InventoryQuery::all / InventoryService
reorder_recommendations → StockIntelligenceService
cost_rollup → CostRollupService
active_promotions → CatalogPromotionService
product_reviews → CatalogReviewService
supplier RFQs → supplier-scoped RFQ service
```

Unknown intent unlocks nothing.

---

## Stage 6 — Permission + Scope Verification

Before tool execution, verify:

- role permission
- sensitivity
- record-level scope
- required parameters
- read/write mode
- confirmation requirement

Decision:

```text
Access allowed?
```

If no:

- log event
- return access-safe message
- suggest allowed actions

If yes, continue to Stage 7.

---

## Stage 7 — Tool Execution

Execute only approved backend tools.

Rules:

- call existing backend service/query/controller logic
- no raw AI-generated SQL
- no permission bypass
- normalize tool output

Tool output should be normalized before answer composition.

Example:

```json
{
  "tool": "inventory_alerts",
  "status": "success",
  "data": []
}
```

---

## Stage 8 — Deterministic Answer Composer

Produce answer without AI first.

The answer composer:

- builds backend-convention response
- uses only tool output
- includes sources
- includes limitations
- produces confidence score

Decision:

```text
High deterministic answer confidence?
```

If yes, continue to Stage 9.

If no, continue to Stage 10.

---

## Stage 10 — API Answer Composer Fallback

Only used if deterministic answer composition lacks confidence.

Rules:

- AI rewrites/summarizes allowed fields only
- AI adds no new facts
- AI sees no forbidden data
- AI output must be validated

If valid, continue to Stage 9.

If invalid, go to Fail Sequence.

---

## Stage 9 — Security / Result Verification

Final check before user output.

Verify:

- output is safe for role
- no forbidden data leakage
- grounded only in tool result
- no hallucinated stock/cost/supplier/reorder data
- response follows backend convention

Decision:

```text
Pass security verification?
```

If yes, return User Result.

If no, go to Fail Sequence.

---

## Fail Sequence

Triggered by:

- invalid intent
- unsupported operation
- invalid API intent
- invalid API answer
- failed security verification
- unsafe output
- backend tool failure where no safe answer can be produced

Fail sequence:

- log event
- execute no tools
- return generic safe response

Example:

```json
{
  "message": "Chatbot could not answer this request.",
  "data": {
    "answer": "I could not understand that request safely. Please try asking in a more specific way.",
    "intent": "unknown",
    "confidence": "none",
    "sources": [],
    "limitations": [
      "No valid safe intent matched the request."
    ],
    "suggested_actions": []
  }
}
```
