# Tested Outputs

## Owner Order Summary

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"debug orders\"}"
```

Passed with:

```text
answer = I found 11 orders. Showing 5.
total = 11
pending = 3
processing = 2
shipped = 3
delivered = 2
cancelled = 1
total_amount = 48280
```

## Owner Recent Orders

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"debug recent orders\"}"
```

Passed with:

```text
Showing 5 recent orders.
```

## Owner Orders By Status

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"debug pending orders\"}"
```

Passed with:

```text
I found 3 orders with status pending. Showing 3.
```

## Owner Order Details

Command:

```cmd
curl.exe -i -b cookies.txt -X POST http://localhost:8888/chatbot/ask -H "Content-Type: application/json" -d "{\"message\":\"debug order 6\"}"
```

Passed with:

```text
Order #6 is currently pending. It has 1 item and a total amount of 100 MAD. Client: Ahmed Acheteur.
```

Preview included:

```text
product_name = Pompe centrifuge horizontale XR200 7.5 kW
product_sku = ASM-PMP-XR200
quantity = 1
unit_price = 100
line_total = 100
```

## Guest Denial

Guest order access was expected to be denied.

Expected result:

```text
You do not have permission to access this information.
```
