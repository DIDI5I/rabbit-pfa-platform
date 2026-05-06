# Stock Intelligence Engine Meaning

Rabbit does not use one forecasting model for every product.

The backend uses a Stock Intelligence Engine with model selection.

The engine can use or expose paths such as:

```text
criticality_only
threshold_only
moving_average
simple_exponential_smoothing
croston_sba
outlier_lumpy_adjustment
trend_flag
seasonal_index
```

The goal is not false precision.

The goal is to answer:

```text
Should the owner reorder this product?
How urgent is it?
How confident is the system?
Why?
```

The chatbot explanation layer summarizes those backend decisions.
