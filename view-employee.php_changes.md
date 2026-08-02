# File Optimization Report

## File Name

view-employee.php

## Summary

Optimized the employee listing page with safer includes, centralized escaping, safer photo handling, stricter pagination validation, better prepared statement handling, reduced query payload, and small JavaScript hardening. The UI and business flow remain unchanged.

## Every Change

- Change Number: 1
- Line Number(s): 3-15
- Old Code Description: Used `include` for required dependencies and kept debug output enabled.
- New Code Description: Uses `require_once` for dependencies and disables debug output by default.
- Why this change was made: Required dependencies should fail clearly if missing, and production pages should not expose PHP errors.
- What problem it solves: Avoids duplicate includes, missing dependency continuation, and accidental error disclosure.
- Expected benefit: Better reliability and safer production behavior.
- Any risks (if any): If a required include is missing, execution now stops immediately, which is appropriate for required files.

- Change Number: 2
- Line Number(s): 17-91
- Old Code Description: Output escaping, JSON encoding, photo filename handling, photo deletion, photo lookup, and pagination validation were scattered or performed inline.
- New Code Description: Added local helper functions for HTML escaping, safe JSON encoding, photo filename normalization, photo path resolution, safe photo deletion, employee photo lookup, and per-page validation.
- Why this change was made: Repeated inline logic was harder to audit and had security gaps.
- What problem it solves: Reduces duplicated checks, prevents path traversal in photo filenames, standardizes output encoding, and keeps malformed text from breaking output.
- Expected benefit: Better maintainability, safer file handling, and less repeated work in the render loop.
- Any risks (if any): None expected; helper behavior preserves the existing default-image behavior.

- Change Number: 3
- Line Number(s): 93-94
- Old Code Description: Used the session station value directly and assigned the raw station name.
- New Code Description: Casts `station_id` to integer and escapes the station name once before it is used by the page/header.
- Why this change was made: Session values should not be trusted as already typed or safe for HTML output.
- What problem it solves: Reduces type confusion and XSS risk from station display text.
- Expected benefit: Safer query parameters and safer output.
- Any risks (if any): None expected.

- Change Number: 4
- Line Number(s): 97-124
- Old Code Description: Delete flow fetched and deleted photos with inline SQL/file logic and did not handle prepare failures.
- New Code Description: Casts the employee id, fetches the current photo with a helper, checks prepare success, and deletes only sanitized existing files.
- Why this change was made: Deletion touches both database and filesystem state and needs stricter validation.
- What problem it solves: Prevents unsafe file paths, avoids warnings on prepare failure, and keeps deletes scoped to the current station.
- Expected benefit: More secure and more reliable delete handling.
- Any risks (if any): None expected.

- Change Number: 5
- Line Number(s): 128-182
- Old Code Description: Update flow trusted posted values directly, trusted the hidden `old_photo`, accepted upload filenames based mainly on extension, and did not handle prepare failures.
- New Code Description: Casts ids, trims text values, fetches the current photo from the database, validates upload state with `UPLOAD_ERR_OK` and `is_uploaded_file`, sanitizes generated filenames, deletes old photos through the safe helper, and handles prepare failure.
- Why this change was made: Hidden fields and uploaded filenames are user-controlled inputs.
- What problem it solves: Reduces tampering, path traversal, unsafe upload naming, and silent SQL preparation errors.
- Expected benefit: Safer updates and cleaner failure behavior without changing the form workflow.
- Any risks (if any): Invalid or non-uploaded files continue to be ignored and the old photo is kept, matching the previous user-facing behavior.

- Change Number: 6
- Line Number(s): 187-209
- Old Code Description: Pagination accepted raw `per_page` and `page` values and used a very large `LIMIT` value for the `all` option.
- New Code Description: Whitelists valid page sizes, clamps the current page, calculates offsets safely, and treats `all` as an unbounded query instead of `PHP_INT_MAX`.
- Why this change was made: Query parameters should be bounded and predictable.
- What problem it solves: Avoids invalid offsets, avoids oversized artificial limits, and prevents unnecessary database work.
- Expected benefit: More predictable pagination and cleaner SQL execution.
- Any risks (if any): Invalid page-size values now fall back to 10 entries.

- Change Number: 7
- Line Number(s): 193-231
- Old Code Description: Count and list queries assumed prepare success, and the employee query used `SELECT *`.
- New Code Description: Checks prepared statement creation, selects only required columns, omits `LIMIT/OFFSET` for `all`, and precomputes each employee photo path while building the data array.
- Why this change was made: The page only renders a subset of employee columns and prepare failures should not produce fatal warnings.
- What problem it solves: Reduces database payload, handles database errors more cleanly, and removes repeated render-time photo path logic.
- Expected benefit: Lower memory use and slightly faster page rendering, especially with wider employee rows.
- Any risks (if any): None expected because all rendered columns are still selected.

- Change Number: 8
- Line Number(s): 233-240
- Old Code Description: Flash messages were read directly from `$_SESSION` in the HTML and unset inline later.
- New Code Description: Copies flash messages into local variables, clears them once, calculates the display range once, and closes the session before rendering.
- Why this change was made: Rendering does not need to keep the PHP session locked.
- What problem it solves: Reduces session lock time and simplifies template conditions.
- Expected benefit: Better concurrency for users opening multiple pages or submitting actions quickly.
- Any risks (if any): None expected.

- Change Number: 9
- Line Number(s): 510-600
- Old Code Description: Several dynamic values were echoed with inconsistent escaping, modal JavaScript arguments used string escaping with `addslashes`, and photo existence checks were performed inside the table render.
- New Code Description: Uses centralized HTML escaping, safe JSON encoding for inline JavaScript arguments, integer casts for action ids, precomputed photo paths, and the precomputed pagination display end value.
- Why this change was made: Browser output needs context-aware escaping, and render loops should stay lightweight.
- What problem it solves: Reduces XSS risk, avoids JavaScript string breakage, and removes per-row filesystem checks from the HTML loop.
- Expected benefit: Safer rendering and cleaner table output with the same visual result.
- Any risks (if any): None expected.

- Change Number: 10
- Line Number(s): 715-819
- Old Code Description: The edit modal inserted the current photo preview with `innerHTML`, and sidebar event handlers assumed all elements existed.
- New Code Description: Builds the photo preview image with DOM methods and `encodeURIComponent`, and registers sidebar listeners only when all required elements exist.
- Why this change was made: DOM construction avoids HTML injection paths and guarded listeners avoid JavaScript errors on partial layouts.
- What problem it solves: Safer modal rendering and more resilient JavaScript.
- Expected benefit: Better front-end stability with no UI change.
- Any risks (if any): None expected.

## Performance Improvements

- Reduced employee query payload by replacing `SELECT *` with only the rendered columns.
- Removed repeated table-render photo path checks by precomputing `photo_path` once per employee row.
- Avoided `LIMIT PHP_INT_MAX` for the `all` option and now runs the natural unbounded ordered query.
- Released the PHP session lock before rendering the page.
- Calculated pagination display bounds once instead of recomputing in the template.

## Security Improvements

- Disabled detailed PHP error output by default.
- Escaped station name, flash messages, employee names, ids, designations, and image paths before HTML output.
- Used substitution-safe HTML escaping so malformed text cannot blank or break output.
- Encoded edit-modal arguments with JSON hex options instead of manual JavaScript string escaping.
- Sanitized photo filenames before display or deletion.
- Stopped trusting the hidden `old_photo` value for server-side update/delete decisions.
- Sanitized generated upload filenames and verified real uploaded files with `is_uploaded_file`.
- Added prepared statement failure handling for read, update, and delete operations.

## Code Quality Improvements

- Centralized repeated escaping, photo, and pagination logic into small local helper functions.
- Improved input normalization for session, GET, and POST values.
- Kept SQL statements focused on the columns used by this page.
- Simplified flash-message rendering by copying session values into local variables.
- Made JavaScript sidebar handling more defensive.
- Left existing CSS, layout, export buttons, search behavior, modal fields, and business workflow unchanged because they were already aligned with the current UI requirements.

## Compatibility

No business logic, database structure, UI layout, user workflow, routes, form field names, or existing CRUD behavior was intentionally changed. The page still lists employees, supports pagination, search, export links, edit, photo upload, and delete using the same visible interface.

## Final Result

The file is now safer and easier to maintain, with lower query payload, less render-time work, safer upload/photo handling, safer output encoding, and better handling of database preparation failures. Expected improvement is modest for small employee lists and more noticeable on larger stations or concurrent sessions because query payload, filesystem checks, and session lock time were reduced.
