# Flowchart Correction Notes

The original included flowchart image communicated the correct system logic, but the final design requires these label corrections before it is considered official:

1. Change title from `Rabbit chatbot corrected flow` to `Rabbit Chatbot V1 Controlled Flow`.
2. Change `Memory` box to `Role-safe Memory`.
3. Add `No user answer here` to Stage 3 API intent fallback.
4. Add `Allowed fields only` to Stage 10 API answer fallback.
5. Add `Grounded only in tool result` to Stage 9 security/result verification.
6. Add `navigation_type` to the navigation response metadata.
7. Make the red dashed fail path clearly labeled as fail-only / invalid-unsafe path.

The corrected Mermaid source is provided in:

```text
rabbit_chatbot_v1_controlled_flow_corrected_mermaid.md
```

This file should be used as the source of truth for regenerating the final visual flowchart.
