# Presenter Updates

## Human-Friendly Answer Labels

Presenters should use names/SKUs in `answer`.

Keep IDs in `summary` for debugging/frontend.

## PurchaseLotsByProductPresenter

Answer should use product/component label:

```text
I found 2 purchase lots for Pompe centrifuge horizontale XR200 7.5 kW (ASM-PMP-XR200). Showing 2.
```

## StockMovementsByComponentPresenter

Answer should use component label:

```text
I found 6 stock movements for Filtre retour hydraulique 10 microns (CMP-HYD-FLT10). Showing 5.
```

## OwnerProductDependenciesPresenter

Answer should use resolved product label:

```text
Pompe centrifuge horizontale XR200 7.5 kW (ASM-PMP-XR200) has 9 registered dependencies. Showing 5.
```

## ClarificationPresenter

Handles failed clarification replies:

```text
I could not match your reply to one of the listed options.
```

Successful clarification usually returns the original tool result, so the original presenter handles the response.
