# Files Created for Reviews V1

## Internal Moderation

```text
app/Queries/ReviewQuery.php
app/Forms/UpdateReviewStatusRequest.php
app/Repositories/ReviewRepository.php
app/Services/ReviewService.php
app/Controllers/ReviewController.php
```

## Catalogue Reviews

```text
app/Queries/CatalogReviewQuery.php
app/Forms/StoreCatalogReviewRequest.php
app/Repositories/CatalogReviewRepository.php
app/Services/CatalogReviewService.php
app/Controllers/CatalogReviewController.php
```

## Database

```text
product_reviews
```

## Main Responsibilities

```text
ReviewQuery
→ internal moderation list/detail/status/delete SQL

ReviewRepository
→ internal moderation data access and formatting

ReviewService
→ moderation workflow, filters, validation orchestration

ReviewController
→ internal review endpoints

CatalogReviewQuery
→ client-safe review SQL

CatalogReviewRepository
→ approved review list, submission, rating summary

CatalogReviewService
→ product existence checks and catalogue review workflow

CatalogReviewController
→ catalogue review endpoints
```

