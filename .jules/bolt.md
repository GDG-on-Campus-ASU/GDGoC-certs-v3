## 2025-12-22 - Missing Composite Indexes on Foreign Keys

**Learning:** Laravel's `foreignId()->constrained()` creates a foreign key constraint and usually an index on the foreign key column. However, it does not automatically create composite indexes for common access patterns like `WHERE user_id = ? ORDER BY created_at DESC`. This can lead to `filesort` operations which degrade performance as tables grow.

**Action:** When adding a foreign key that will be used for filtering in a list view that is sorted by another column (e.g., `latest()`), always consider adding a composite index `(foreign_key, sort_column)`.

## 2025-12-27 - Eloquent Select Optimization for Text Columns

**Learning:** Eloquent's `get()` selects all columns by default (`SELECT *`). When models contain large text or blob columns (like `body` or `content` in templates), fetching a list of these models can consume excessive memory and bandwidth, even if those specific columns are not used in the view.

**Action:** When listing models that have potentially large columns not needed for the list view, always use `select('id', 'name', ...)` to fetch only the necessary columns.

## 2025-01-08 - Sidebar/Navigation Query Optimization

**Learning:** When fetching models for sidebar navigation (e.g., list of documentation pages), controllers often use the same query as the main list view. If the model has large columns (like `content`), this adds significant overhead to every page load where the sidebar is present, even if the main content is different.

**Action:** Always create a specific query for navigation elements that selects only the necessary columns (e.g., `id`, `title`, `slug`) and excludes large text/blob fields.
