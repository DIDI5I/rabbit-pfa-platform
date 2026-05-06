# ProductIntelligenceSnapshotPresenter Changes

The presenter summary now includes:

```text
active_promotions_count
review_count
average_rating
```

The `items_preview` now includes a commercial section:

```json
{
  "section": "commercial",
  "active_promotions_count": 0,
  "review_count": 0,
  "average_rating": null,
  "rating_distribution": {
    "1": 0,
    "2": 0,
    "3": 0,
    "4": 0,
    "5": 0
  }
}
```

If active promotions exist, the presenter can add:

```text
Active promotions
```

If approved reviews exist, the presenter can add:

```text
Recent reviews
```

The answer text was also extended so future products with promotions/reviews can say:

```text
It has X active promotion(s).
It has an average rating of Y from Z review(s).
```
