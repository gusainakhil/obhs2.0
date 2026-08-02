# File Optimization Report

## File Name

`train-report.php`

## Summary

Optimized the single train report page by consolidating repeated coach-wise report queries, centralizing duplicate percentage/table-rendering code, tightening output escaping, fixing malformed detail links, and making JavaScript value handling safer. The UI and report sections remain the same.

## Every Change

### Change Number 1

- Line Number(s): 1-15
- Old Code Description: Used `include` for required dependencies and enabled `$debug = true` by default.
- New Code Description: Uses `require_once` for required files and sets `$debug = false` unless explicitly enabled for local development.
- Why this change was made: Required dependencies should fail fast if missing, and production pages should not display detailed PHP errors.
- What problem it solves: Prevents duplicate includes and avoids leaking warnings/errors to users.
- Expected benefit: Better reliability and safer production behavior.
- Any risks (if any): If a required file is missing, execution now stops immediately instead of continuing in a broken state.

### Change Number 2

- Line Number(s): 17-25
- Old Code Description: Used direct `htmlspecialchars()` in some places and raw PHP interpolation inside JavaScript.
- New Code Description: Added `trainReportEscape()` for consistent HTML escaping and `trainReportJson()` for safe JavaScript value embedding.
- Why this change was made: Repeated escaping patterns were inconsistent.
- What problem it solves: Reduces XSS risk and prevents malformed JavaScript when filter values contain quotes or special characters.
- Expected benefit: Safer output with simpler maintenance.
- Any risks (if any): None expected.

### Change Number 3

- Line Number(s): 27-53
- Old Code Description: Percentage calculations were duplicated separately in the AC, NON-AC, and TTE report blocks.
- New Code Description: Added `trainReportDefaultTargets()` and `trainReportCalculatePercentage()` to centralize target defaults and percentage calculation.
- Why this change was made: The same calculation logic existed three times.
- What problem it solves: Removes duplicate calculation code and keeps denominator handling consistent.
- Expected benefit: Easier maintenance and lower risk of future logic drift.
- Any risks (if any): Low; formula was preserved.

### Change Number 4

- Line Number(s): 55-185
- Old Code Description: The page called `feedback_calculation_coach_wise()` three times, once per coach type. Each call repeated marking, target, and question-count queries.
- New Code Description: Added `trainReportFetchFeedbackData()` to fetch AC, NON-AC, and TTE coach feedback in one aggregate query, then fetch shared marking, target, and question metadata once.
- Why this change was made: The old page performed duplicate database work for the same train/date/grade filters.
- What problem it solves: Reduces repeated SQL queries and duplicate metadata lookups.
- Expected benefit: Report data queries drop from up to 12 queries to 4 queries for the same page load.
- Any risks (if any): Low; SQL filters and percentage inputs match the old helper logic.

### Change Number 5

- Line Number(s): 187-197
- Old Code Description: Detail-link query strings were manually rebuilt in each table loop.
- New Code Description: Added `trainReportBuildDetailQuery()` to generate detail query strings from one place.
- Why this change was made: Query construction was duplicated and inconsistent.
- What problem it solves: Reduces copy/paste errors in detail links.
- Expected benefit: Cleaner, safer link generation.
- Any risks (if any): None expected.

### Change Number 6

- Line Number(s): 199-277
- Old Code Description: AC, NON-AC, and TTE tables each had separate loops, totals, percentage calculations, footer handling, and mixed escaping.
- New Code Description: Added `trainReportRenderFeedbackSection()` to render the same table structure for each coach type using configuration values.
- Why this change was made: The three blocks had the same structure with small parameter differences.
- What problem it solves: Removes duplicated table-rendering code, centralizes totals, and outputs valid `<tbody>`/`<tfoot>` order.
- Expected benefit: Smaller file, easier future changes, less risk of inconsistent fixes.
- Any risks (if any): Low; section titles, headings, no-data text, footer styles, and spacing are preserved.

### Change Number 7

- Line Number(s): 279-288
- Old Code Description: Read request values as nullable values and kept the session write lock open for the full render.
- New Code Description: Normalizes request values to strings, stores station ID once, builds report data once, and closes the PHP session write lock after required data is read.
- Why this change was made: The page does not need to write session data during report rendering.
- What problem it solves: Avoids nullable output warnings and prevents long report rendering from blocking other requests in the same session.
- Expected benefit: Better request concurrency for the logged-in user.
- Any risks (if any): Low; included sidebar/header/footer files only read page context and do not write session data.

### Change Number 8

- Line Number(s): 373, 551-555
- Old Code Description: Header/title values used ad hoc escaping.
- New Code Description: Header/title values use `trainReportEscape()`.
- Why this change was made: Output escaping should be consistent across the file.
- What problem it solves: Improves XSS protection for station/filter values.
- Expected benefit: Safer HTML output.
- Any risks (if any): None expected.

### Change Number 9

- Line Number(s): 561-604
- Old Code Description: Three long inline report blocks called helper functions and rendered rows separately.
- New Code Description: The three sections call the shared renderer with the same visible titles, headings, no-data messages, and footer styles.
- Why this change was made: The inline blocks were large and repetitive.
- What problem it solves: Improves readability and keeps report behavior centralized.
- Expected benefit: Easier maintenance with the same UI output.
- Any risks (if any): Low; AC spacing is explicitly preserved with `mt-4`, and other sections keep `mt-6`.

### Change Number 10

- Line Number(s): 581-582, 591-602
- Old Code Description: NON-AC detail links used `coach_type=Non-AC`, while the rest of the system queries `NON-AC`; the TTE all-feedback footer URL had a space after `?`.
- New Code Description: NON-AC links now use `coach_type=NON-AC`, and all all-feedback URLs are generated without malformed spaces.
- Why this change was made: These were link bugs in the current file.
- What problem it solves: Detail links now send the coach type format expected by the backend queries, and TTE footer links are valid.
- Expected benefit: More reliable navigation to detail reports.
- Any risks (if any): Low; this fixes malformed query values rather than changing report calculations.

### Change Number 11

- Line Number(s): 621-652
- Old Code Description: JavaScript export functions embedded PHP values inside quoted JS strings directly.
- New Code Description: JavaScript export functions now receive values through `trainReportJson()`.
- Why this change was made: Raw interpolation can break JavaScript or allow script injection if values contain quotes.
- What problem it solves: Safer and more robust export/print URL construction.
- Expected benefit: Better security and fewer client-side edge-case failures.
- Any risks (if any): None expected.

### Change Number 12

- Line Number(s): 655-676
- Old Code Description: Sidebar event listeners assumed all DOM nodes existed.
- New Code Description: Sidebar listeners are registered only when all required elements are present.
- Why this change was made: Missing elements should not break the rest of the page script.
- What problem it solves: Prevents JavaScript runtime errors on partial layouts or future template changes.
- Expected benefit: More resilient client-side behavior.
- Any risks (if any): None expected.

## Performance Improvements

- Reduced repeated database calls by replacing three `feedback_calculation_coach_wise()` calls with one combined coach feedback query.
- Shared marking, target, and question-count metadata is fetched once instead of once per coach type.
- Removed repeated percentage, totals, and query-string logic from three separate loops.
- Released the PHP session write lock before rendering the full report.

## Security Improvements

- Disabled debug output by default.
- Added consistent HTML escaping with `ENT_QUOTES`.
- Added JSON-safe JavaScript value embedding.
- Escaped generated query strings before placing them in `href` attributes.
- Fixed malformed NON-AC/TTE detail URLs.

## Code Quality Improvements

- Centralized target defaults, percentage calculation, detail query building, data fetching, and table rendering.
- Removed unused variables such as `$coach_qs` and `$train_qs`.
- Removed repeated table blocks and reduced copy/paste maintenance risk.
- Improved table markup by outputting `<tbody>` before `<tfoot>` correctly.
- Kept section labels, button labels, styling classes, and no-data messages unchanged.

## Compatibility

No business logic, database schema, or UI behavior was intentionally changed. The visible report structure remains AC, NON AC, and TTe sections with the same columns, totals, export buttons, and print behavior. The only behavioral corrections are malformed detail-link query values.

## Final Result

`train-report.php` is now smaller, safer, and more maintainable. The largest improvement is database load: the page now performs up to 4 report-data queries instead of up to 12 for the same train/date/grade report, which should noticeably reduce page-load time on larger feedback datasets.
