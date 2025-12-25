## 2025-12-22 - Missing Composite Indexes on Foreign Keys

**Learning:** Laravel's `foreignId()->constrained()` creates a foreign key constraint and usually an index on the foreign key column. However, it does not automatically create composite indexes for common access patterns like `WHERE user_id = ? ORDER BY created_at DESC`. This can lead to `filesort` operations which degrade performance as tables grow.

**Action:** When adding a foreign key that will be used for filtering in a list view that is sorted by another column (e.g., `latest()`), always consider adding a composite index `(foreign_key, sort_column)`.
