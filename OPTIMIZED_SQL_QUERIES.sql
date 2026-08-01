-- Optimized SQL query patterns for OBHS.
-- Generated from static audit on 2026-08-01.
-- Replace placeholders with mysqli prepared statement parameters.

-- 1. Feedback detail export: replaces per-passenger feedback queries.
SELECT
  p.unique_id,
  p.id AS passenger_id,
  p.created,
  p.seat_no,
  p.coach_no,
  p.name,
  p.pnr_number,
  p.ph_number,
  p.train_no,
  p.grade,
  p.coach_type,
  f.feed_param,
  f.value
FROM OBHS_passenger p
LEFT JOIN OBHS_feedback f
  ON f.passenger_id = p.id
WHERE p.station_id = ?
  AND (? IS NULL OR p.train_no = ?)
  AND (? IS NULL OR p.grade = ?)
  AND (? IS NULL OR p.coach_type = ?)
  AND p.created >= ?
  AND p.created < ?
ORDER BY p.created DESC, p.id ASC, f.feed_param ASC;

-- 2. Dashboard/activity counts with range predicates.
SELECT
  DATE(created_at) AS report_date,
  COUNT(*) AS day_count,
  SUM(created_at >= ? AND created_at < ?) AS today_count,
  SUM(created_at >= ? AND created_at < ?) AS month_count
FROM base_attendance
WHERE station_id = ?
  AND created_at >= ?
  AND created_at < ?
GROUP BY DATE(created_at)
ORDER BY report_date ASC;

-- 3. Feedback counts with indexed passenger date range.
SELECT
  COUNT(DISTINCT CASE WHEN p.created >= ? AND p.created < ? THEN p.id END) AS today_count,
  COUNT(DISTINCT CASE WHEN p.created >= ? AND p.created < ? THEN p.id END) AS month_count
FROM OBHS_feedback f
JOIN OBHS_passenger p ON p.id = f.passenger_id
WHERE p.station_id = ?
  AND p.created >= ?
  AND p.created < ?;

-- 4. Photo report: one query for selected range, group in PHP by coach/date/slot.
SELECT
  id,
  train_no,
  grade,
  coach_no,
  coach_type,
  photo,
  cleaning_area,
  time_of_cleaning,
  janitor,
  location,
  location_link,
  created_at,
  DATE(created_at) AS report_date,
  CASE
    WHEN TIME(created_at) BETWEEN '06:00:00' AND '10:00:00' THEN '06_10'
    WHEN TIME(created_at) BETWEEN '12:00:00' AND '14:00:00' THEN '12_14'
    WHEN TIME(created_at) BETWEEN '16:00:00' AND '21:00:00' THEN '16_21'
    ELSE 'other'
  END AS time_slot
FROM base_photo_report
WHERE station_id = ?
  AND train_no = ?
  AND grade = ?
  AND created_at >= ?
  AND created_at < ?
ORDER BY coach_no ASC, created_at ASC;

-- 5. Before/after photo report: one query, group in PHP by coach/date/time_of_cleaning.
SELECT
  id,
  coach_no,
  photo,
  time_of_cleaning,
  location,
  created_at,
  DATE(created_at) AS report_date
FROM base_photo_report
WHERE station_id = ?
  AND train_no = ?
  AND grade = ?
  AND time_of_cleaning IN ('Before', 'After')
  AND created_at >= ?
  AND created_at < ?
ORDER BY coach_no ASC, time_of_cleaning ASC, created_at ASC;

-- 6. Round-wise summary: target plus achieved aggregate in one pass.
SELECT
  t.train_no,
  t.no_ac_coach,
  t.feed_per_ac_coach,
  t.no_non_ac_coach,
  t.feed_per_non_ac_coach,
  t.feedback_tte,
  COUNT(DISTINCT CASE WHEN p.coach_type <> 'TTE' THEN p.coach_no END) AS distinct_coaches,
  COUNT(DISTINCT CASE WHEN p.coach_type = 'AC' THEN p.coach_no END) AS ac_achieved_coaches,
  COUNT(DISTINCT CASE WHEN p.coach_type = 'NON-AC' THEN p.coach_no END) AS non_ac_achieved_coaches,
  COUNT(CASE WHEN p.coach_type = 'AC' THEN 1 END) AS ac_count,
  COUNT(CASE WHEN p.coach_type = 'NON-AC' THEN 1 END) AS non_ac_count,
  COUNT(CASE WHEN p.coach_type = 'TTE' THEN 1 END) AS tte_count
FROM base_fb_target t
LEFT JOIN OBHS_passenger p
  ON p.station_id = t.station
 AND p.train_no = t.train_no
 AND p.created >= ?
 AND p.created < ?
 AND (? IS NULL OR p.grade = ?)
WHERE t.station = ?
  AND t.train_no IN (/* prepared placeholders */)
GROUP BY
  t.train_no,
  t.no_ac_coach,
  t.feed_per_ac_coach,
  t.no_non_ac_coach,
  t.feed_per_non_ac_coach,
  t.feedback_tte
ORDER BY t.train_no ASC;

-- 7. Attendance duplicate check without DATE(created_at).
SELECT id
FROM base_attendance
WHERE employee_id = ?
  AND station_id = ?
  AND type_of_attendance = ?
  AND train_no = ?
  AND grade = ?
  AND created_at >= ?
  AND created_at < ?
ORDER BY id DESC
LIMIT 1;

-- 8. Attendance report range filter.
SELECT
  ba.employee_id,
  ba.employee_name,
  ba.train_no,
  ba.type_of_attendance,
  ba.location,
  ba.photo,
  ba.created_at,
  ba.fullLocation,
  be.photo AS employee_photo
FROM base_attendance ba
LEFT JOIN base_employees be
  ON ba.employee_id = be.employee_id
 AND be.station_id = ba.station_id
WHERE ba.station_id = ?
  AND ba.grade = ?
  AND ba.train_no IN (?, ?)
  AND ba.created_at >= ?
  AND ba.created_at < ?
ORDER BY ba.employee_name, ba.train_no,
  FIELD(ba.type_of_attendance, 'Start of journey', 'Mid of journey', 'End of journey');

-- 9. Sidebar menu with active-report filter.
SELECT reports_name, link
FROM OBHS_reports
WHERE user_id = ?
  AND status = 1
ORDER BY id ASC;

-- 10. Questions and marking metadata.
SELECT id, eng_question, hin_question
FROM OBHS_questions
WHERE station_id = ?
  AND type = ?
ORDER BY id ASC;

SELECT category, value
FROM OBHS_marking
WHERE station_id = ?
ORDER BY value DESC;

-- 11. Salary report without GROUP_CONCAT/FIND_IN_SET.
SELECT
  employee_id,
  employee_name,
  desination,
  train_no,
  toc,
  employee_name_unique,
  SUM(toc = 'AC' AND desination = 'Janitor') AS AC_count,
  SUM(toc = 'Non-AC' AND desination = 'Janitor') AS Non_AC_count,
  SUM(desination = 'Supervisor') AS Supervisor_count
FROM base_attendance
WHERE station_id = ?
  AND employee_name IS NOT NULL
  AND created_at >= ?
  AND created_at < ?
GROUP BY employee_id, employee_name, desination, train_no, toc, employee_name_unique
HAVING
  SUM(type_of_attendance = 'Start of journey') > 0
  AND SUM(type_of_attendance = 'Mid of journey') > 0
  AND SUM(type_of_attendance = 'End of journey') > 0
ORDER BY employee_name ASC;

-- 12. Safe round-wise parameter lookup.
SELECT id, eng_question, hin_question, type
FROM OBHS_questions
WHERE station_id = ?
  AND type = ?
ORDER BY id ASC;

SELECT category, value
FROM OBHS_marking
WHERE station_id = ?
ORDER BY value DESC;
