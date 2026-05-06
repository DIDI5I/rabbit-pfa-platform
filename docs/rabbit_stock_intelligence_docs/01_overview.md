# Rabbit — Stock Intelligence V1 Overview

## Purpose

Stock Intelligence V1 provides an owner-facing backend endpoint for reorder recommendations and demand interpretation.

Main endpoint:

```http
GET /stock/intelligence/reorder-recommendations
```

The endpoint analyzes stock position, demand history, supplier lead time, VED criticality, outlier/lumpy demand, model eligibility, trend diagnostics, and seasonality diagnostics.

## Current Status

Implemented and tested:

- Base reorder recommendation endpoint
- Period and forecast configuration
- Filters
- Forecast model class refactor
- Model eligibility metadata
- Outlier/lumpy demand analysis
- Simple Exponential Smoothing model
- Croston/SBA model
- Moving Average fallback model
- Criticality-only model
- Threshold-only model
- Regression trend diagnostic
- Seasonality diagnostic

## Core Principle

Rabbit must not fake intelligence.

Every model or diagnostic must be allowed to return insufficient data, low confidence, or ineligible status.

## Stock Source of Truth

The source of truth for stock is `stock_movements`.

Stock is calculated as:

```text
current_stock = SUM(type = 'in') - SUM(type = 'out')
```

Rules:

- Movement quantity is always positive.
- Movement type controls direction.
- `in` increases stock.
- `out` decreases stock.
- Accepted RFQs do not increase stock.
- Finalized purchase lots increase stock through `PURCHASE_RECEIVED` movements.

## Main Output Concepts

Each item returns:

- Current stock
- Low stock threshold
- VED class
- OUT movement count
- Stock OUT quantity
- Average daily outflow
- Outlier analysis
- Supplier lead time
- Lead time demand
- Safety stock
- Reorder point
- Recommended reorder quantity
- Estimated reorder value
- Selected model
- Model reason
- Model eligibility
- Trend analysis
- Seasonality analysis
- Recommendation boolean
- Priority
- Confidence
- Reason codes
- Data quality flags
