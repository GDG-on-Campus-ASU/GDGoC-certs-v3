## 2025-12-22 - Missing Composite Indexes on Foreign Keys

**Learning:** Laravel's `foreignId()->constrained()` creates a foreign key constraint and usually an index on the foreign key column. However, it does not automatically create composite indexes for common access patterns like `WHERE user_id = ? ORDER BY created_at DESC`. This can lead to `filesort` operations which degrade performance as tables grow.

**Action:** When adding a foreign key that will be used for filtering in a list view that is sorted by another column (e.g., `latest()`), always consider adding a composite index `(foreign_key, sort_column)`.

## 2025-12-27 - Eloquent Select Optimization for Text Columns

**Learning:** Eloquent's `get()` selects all columns by default (`SELECT *`). When models contain large text or blob columns (like `body` or `content` in templates), fetching a list of these models can consume excessive memory and bandwidth, even if those specific columns are not used in the view.

**Action:** When listing models that have potentially large columns not needed for the list view, always use `select('id', 'name', ...)` to fetch only the necessary columns.
