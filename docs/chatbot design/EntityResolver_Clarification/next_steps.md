# Next Steps

## Immediate Regression Tests

Run:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"show purchase lots for ASM-PMP-XR200\"}"
```

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"show stock movements for CMP-HYD-FLT10\"}"
```

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"show dependencies for XR200\"}"
```

Then reply:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"1\"}"
```

## Good Next Stage

Frontend chatbot integration contract/polish.

## Possible Future Backend Stage

Stage 3C RFQ read-only tools, but only after inspecting `RfqService` scoping.

Possible tools:

```text
rfq_summary
rfq_details
rfq_allowed_actions
```

## Avoid For Now

```text
AI fallback
write actions
long-term memory
free-form recommendations
```
