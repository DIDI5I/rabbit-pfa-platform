# Reviews Database

## Table

```sql
CREATE TABLE product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_id INT NOT NULL,
    user_id INT NOT NULL,

    rating TINYINT NOT NULL,
    title VARCHAR(150) NULL,
    comment TEXT NULL,

    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_product_reviews_component
        FOREIGN KEY (component_id) REFERENCES components(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_product_reviews_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT chk_product_reviews_rating
        CHECK (rating >= 1 AND rating <= 5)
);
```

## Recommended Indexes

```sql
CREATE INDEX idx_product_reviews_component_status
ON product_reviews (component_id, status);

CREATE INDEX idx_product_reviews_user
ON product_reviews (user_id);
```

## Status Values

```text
pending
approved
rejected
```

## Rating Values

```text
1
2
3
4
5
```

Only ratings from approved reviews should be used in public catalogue summaries.

