# File Optimization Report

## File Name

`view-attendance.php`

## Summary

Optimized the attendance report page by moving repeated procedural work into local helper functions, making the date filter index-friendly, improving SQL error handling, centralizing output escaping, disabling debug output by default, and guarding sidebar JavaScript listeners. The visible filters, table columns, buttons, messages, and attendance percentage calculations remain unchanged.

## Every Change

### Change Number 1

- Line Number(s): 1-15
- Old Code Description: Used `include` for required dependencies and had `$debug = true` by default.
- New Code Description: Uses `require_once` and sets `$debug = false` unless explicitly enabled for local development.
- Why this change was made: Required dependency failures should be explicit, and production pages should not display PHP internals.
- What problem it solves: Avoids duplicate includes and reduces information disclosure from warnings/notices.
- Expected benefit: Safer and more predictable runtime behavior.
- Any risks (if any): If a required file is missing, the page now stops immediately instead of continuing in a broken state.

### Change Number 2

- Line Number(s): 17-20
- Old Code Description: Escaping was done directly with `htmlspecialchars()` in multiple output locations.
- New Code Description: Added `viewAttendanceEscape()` for consistent HTML escaping using `ENT_QUOTES` and UTF-8.
- Why this change was made: A single local escape helper makes output safety consistent.
- What problem it solves: Reduces XSS risk and duplicate escaping code.
- Expected benefit: Safer output and easier maintenance.
- Any risks (if any): None expected.

### Change Number 3

- Line Number(s): 22-44
- Old Code Description: Train list SQL was executed inline and assumed `prepare()` always succeeded.
- New Code Description: Added `viewAttendanceFetchTrains()` with prepare failure handling, typed station binding, and local return defaults.
- Why this change was made: The inline query mixed data access with page setup and lacked error handling.
- What problem it solves: Prevents fatal errors from calling methods on a failed statement and keeps train-loading logic isolated.
- Expected benefit: More robust train dropdown loading with clearer code.
- Any risks (if any): Low; the query and dropdown ordering are unchanged.

### Change Number 4

- Line Number(s): 46-49
- Old Code Description: Percentage formulas were repeated inline for Trip 1, Trip 2, and Round Trip.
- New Code Description: Added `viewAttendancePercentage()` to calculate the same percentage formula from actual count and total checkpoints.
- Why this change was made: The same calculation pattern appeared multiple times.
- What problem it solves: Reduces duplicated formula code.
- Expected benefit: Easier maintenance with identical output values.
- Any risks (if any): None expected; the formula is preserved.

### Change Number 5

- Line Number(s): 51-136
- Old Code Description: Attendance SQL and result processing were inline. The filter used `DATE(created_at) BETWEEN ? AND ?`.
- New Code Description: Added `viewAttendanceFetchData()` with the same aggregate attendance logic, prepare failure handling, integer casts for checkpoint totals, and an index-friendly date range: `created_at >= ? AND created_at < ?`.
- Why this change was made: Wrapping `created_at` in `DATE()` prevents normal index use and the inline block was difficult to maintain.
- What problem it solves: Allows a `created_at` index to be used, keeps the selected end date inclusive through a next-day exclusive bound, and makes SQL failures safer.
- Expected benefit: Faster attendance queries on larger datasets, especially when `created_at` is indexed.
- Any risks (if any): Low; date behavior is intended to match the original inclusive date filter.

### Change Number 6

- Line Number(s): 139-149
- Old Code Description: Session station values and POST values were read directly as mixed/null values.
- New Code Description: Casts station ID to integer, normalizes filter values to strings, stores the POST request state once, and pre-escapes `$station_name` for the shared header include.
- Why this change was made: Normalized inputs avoid notices and pre-escaping protects the included header, which echoes `$station_name` directly.
- What problem it solves: Reduces type ambiguity and closes an output-escaping gap.
- Expected benefit: Cleaner request setup and safer header output.
- Any risks (if any): Low; browser-visible station text remains the same.

### Change Number 7

- Line Number(s): 151-167
- Old Code Description: Attendance fetching was controlled inline by `$_SERVER['REQUEST_METHOD']`, and the PHP session stayed write-locked through the page render.
- New Code Description: Uses the `$is_post` flag and calls the data helper, then releases the session write lock with `session_write_close()`.
- Why this change was made: This page does not need to write session data after initial setup.
- What problem it solves: Prevents long report rendering from blocking other same-session requests.
- Expected benefit: Better request concurrency for logged-in users.
- Any risks (if any): Low; session values remain readable after `session_write_close()`.

### Change Number 8

- Line Number(s): 455-485, 520-530
- Old Code Description: Train options, date values, employee values, and percentages used mixed direct output and `htmlspecialchars()`.
- New Code Description: Uses `viewAttendanceEscape()` consistently for form and table output, and reuses `$is_post` for the empty-table message condition.
- Why this change was made: Output handling should be consistent and the request-method check was duplicated.
- What problem it solves: Improves XSS protection and reduces repeated superglobal access.
- Expected benefit: Safer rendered HTML with unchanged visible values.
- Any risks (if any): None expected.

### Change Number 9

- Line Number(s): 584-599
- Old Code Description: Sidebar JavaScript assumed `menuToggle`, `sidebar`, `sidebarOverlay`, and `closeSidebar` always existed.
- New Code Description: Registers event listeners only when all required elements are present.
- Why this change was made: Shared layouts can change or omit elements.
- What problem it solves: Prevents JavaScript runtime errors that could stop later scripts from running.
- Expected benefit: More resilient client-side behavior.
- Any risks (if any): None expected.

## Performance Improvements

- Replaced `DATE(created_at)` with an index-friendly timestamp range so MySQL can use a `created_at` index.
- Kept the selected `To Date` inclusive with an exclusive next-day upper bound.
- Centralized percentage calculation to remove repeated formula code.
- Released the PHP session write lock after server-side setup and data fetch.
- Left the existing train dropdown query as a single prepared query because it is already minimal for the current UI.

## Security Improvements

- Disabled debug error display by default.
- Switched required dependencies to `require_once`.
- Added consistent `ENT_QUOTES` HTML escaping.
- Pre-escaped the station name used by the shared header include.
- Added prepared statement failure handling with server-side logging.
- Kept existing prepared SQL binding for all user-controlled filters.

## Code Quality Improvements

- Split train loading, attendance loading, escaping, and percentage calculation into small local functions.
- Normalized request values to strings and station ID to integer.
- Reused `$is_post` instead of repeatedly reading `$_SERVER['REQUEST_METHOD']`.
- Guarded sidebar JavaScript event listeners.
- Left DataTables assets and the commented DataTables initialization unchanged because removing them could alter current or expected UI behavior.

## Compatibility

No business logic, database schema, UI layout, labels, filters, buttons, table columns, empty messages, or attendance percentage rules were intentionally changed. The only query behavior adjustment is replacing `DATE(created_at)` with an equivalent inclusive-date timestamp range that is friendlier to indexes.

## Final Result

`view-attendance.php` is now safer, easier to maintain, and better prepared for larger attendance tables. The main expected improvement is faster filtering by date when `base_attendance.created_at` is indexed, potentially changing a full scan into an index range scan for the date window.
