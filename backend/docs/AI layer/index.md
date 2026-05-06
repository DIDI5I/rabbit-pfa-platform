# 🤖 AI LAYER / ASSISTANT SYSTEM (PLANNED)

Rabbit will include an AI assistant layer.

Purpose:
- help users query products, suppliers, RFQs, stock, and BOM data
- assist with procurement decisions
- explain low-stock situations
- recommend actions like creating RFQs
- later support demand forecasting / prévision de demande

Current status:
- AI/chatbot was explored earlier
- frontend may contain demo chat logic
- backend AI layer is NOT yet part of the finalized core contract
- do NOT make core backend depend on AI

Planned AI capabilities:
1. Natural language search
   Example:
   "Show me low stock components"
   "Find suppliers for bearings"

2. Procurement assistant
   Example:
   "Which RFQs need action?"
   "Which supplier quoted the best price?"

3. BOM assistant
   Example:
   "What components are inside Pump XR200?"
   "What is the cost rollup for this assembly?"

4. Inventory assistant
   Example:
   "Why is stock low?"
   "What stock movements happened this month?"

5. Forecasting assistant
   Example:
   "Predict demand for this component"
   "Recommend reorder quantity"

Architecture rule:
AI layer must sit ABOVE the existing services.

Correct flow:
AI Assistant
→ Intent Parser
→ Existing Services
→ API Response

AI must NOT bypass:
- Auth
- Services
- Repositories
- Validation
- Role permissions

Important:
AI is an interface layer, not the source of truth.
The existing backend services remain the source of truth.

Possible future files:
app/Controllers/ChatController.php
app/Services/AI/ChatService.php
app/Services/AI/IntentParser.php
app/Services/AI/ToolRouter.php
app/Services/AI/Tools/ProductTool.php
app/Services/AI/Tools/RfqTool.php
app/Services/AI/Tools/InventoryTool.php

Possible endpoint:
POST /chat

Request:
{
  "message": "Show me low stock components"
}

Response:
{
  "message": "Assistant response generated successfully",
  "data": {
    "reply": "...",
    "intent": "low_stock_products",
    "results": []
  }
}

Critical rule:
Do NOT implement AI until core backend + frontend integration is stable.
AI comes after:
1. Auth integration
2. Products integration
3. RFQ integration
4. Stock movement layer
5. Inventory layer