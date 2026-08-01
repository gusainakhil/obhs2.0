-- OBHS index recommendations generated from static audit on 2026-08-01.
-- Run in staging first. For indexes that may already exist, check SHOW INDEX before applying.
-- DDL statements autocommit in MySQL/MariaDB.

-- 0. Required duplicate checks before unique indexes.
SELECT `id`, COUNT(*) AS duplicate_count
FROM `OBHS_passenger`
GROUP BY `id`
HAVING COUNT(*) > 1;

SELECT `station_id`, `employee_id`, COUNT(*) AS duplicate_count
FROM `base_employees`
GROUP BY `station_id`, `employee_id`
HAVING COUNT(*) > 1;

-- 1. Critical feedback join key.
ALTER TABLE `OBHS_passenger`
  ADD UNIQUE KEY `uq_obhs_passenger_public_id` (`id`);

ALTER TABLE `OBHS_feedback`
  ADD KEY `idx_feedback_passenger_feed_value` (`passenger_id`, `feed_param`, `value`),
  ADD KEY `idx_feedback_created_passenger` (`created`, `passenger_id`);

-- 2. Passenger report filters.
ALTER TABLE `OBHS_passenger`
  ADD KEY `idx_passenger_station_train_grade_type_created` (`station_id`, `train_no`, `grade`, `coach_type`, `created`),
  ADD KEY `idx_passenger_station_created_train` (`station_id`, `created`, `train_no`),
  ADD KEY `idx_passenger_train_station_created` (`train_no`, `station_id`, `created`);

-- 3. Feedback target lookup.
ALTER TABLE `base_fb_target`
  ADD KEY `idx_fb_target_station_train` (`station`, `train_no`),
  ADD KEY `idx_fb_target_station_created` (`station`, `created_at`);

-- 4. Attendance duplicate checks and reports.
ALTER TABLE `base_attendance`
  ADD KEY `idx_attendance_station_train_grade_type_created` (`station_id`, `train_no`, `grade`, `type_of_attendance`, `created_at`),
  ADD KEY `idx_attendance_employee_station_id` (`employee_id`, `station_id`, `id`),
  ADD KEY `idx_attendance_station_created_train` (`station_id`, `created_at`, `train_no`),
  ADD KEY `idx_attendance_duplicate_day` (`employee_id`, `station_id`, `type_of_attendance`, `train_no`, `grade`, `created_at`, `id`);

-- 5. Photo reports.
ALTER TABLE `base_photo_report`
  ADD KEY `idx_photo_station_train_grade_coach_created` (`station_id`, `train_no`, `grade`, `coach_no`, `created_at`),
  ADD KEY `idx_photo_station_train_grade_clean_created` (`station_id`, `train_no`, `grade`, `time_of_cleaning`, `created_at`);

-- 6. Question and marking metadata.
ALTER TABLE `OBHS_questions`
  ADD KEY `idx_questions_station_type_id` (`station_id`, `type`, `id`),
  ADD KEY `idx_questions_user_id` (`user_id`, `id`);

ALTER TABLE `OBHS_marking`
  ADD KEY `idx_marking_station_value` (`station_id`, `value`),
  ADD KEY `idx_marking_user_value` (`user_id`, `value`);

-- 7. User login/admin dashboards.
ALTER TABLE `OBHS_users`
  ADD KEY `idx_users_username_type` (`username`, `type`),
  ADD KEY `idx_users_email_type` (`email`, `type`),
  ADD KEY `idx_users_station_type_status_end` (`station_id`, `type`, `status`, `end_date`),
  ADD KEY `idx_users_type_end_status` (`type`, `end_date`, `status`);

-- 8. Reports/sidebar.
ALTER TABLE `OBHS_reports`
  ADD KEY `idx_reports_user_status_id` (`user_id`, `status`, `id`),
  ADD KEY `idx_reports_station_type_status` (`station_id`, `type`, `status`);

-- 9. Employees.
ALTER TABLE `base_employees`
  ADD KEY `idx_employees_station_created` (`station_id`, `created_at`),
  ADD UNIQUE KEY `uq_employees_station_employee` (`station_id`, `employee_id`);

ALTER TABLE `base_employees_jodhpur`
  ADD KEY `idx_employees_jodhpur_station_created` (`station_id`, `created_at`),
  ADD KEY `idx_employees_jodhpur_station_employee` (`station_id`, `employee_id`);

-- 10. PDF attendance and advertisements.
ALTER TABLE `pdf_attendence`
  ADD KEY `idx_pdf_attendence_station_trains_date_id` (`station_id`, `train_up`, `train_down`, `from_date`, `id`);

ALTER TABLE `OBHS_Globaladvertisment`
  ADD KEY `idx_globaladvertisment_date` (`date`);



  --old
  --Not done For future  unique key issue
ALTER TABLE `OBHS_passenger`
  ADD UNIQUE KEY `uq_obhs_passenger_public_id` (`id`);

-- OBHS_passenger.
-- Done
CREATE INDEX `idx_passenger_station_train_grade_type_created`
  ON `OBHS_passenger` (`station_id`, `train_no`, `grade`, `coach_type`, `created`);

  --done
CREATE INDEX `idx_passenger_station_created_train`
  ON `OBHS_passenger` (`station_id`, `created`, `train_no`);

  --done
CREATE INDEX `idx_passenger_train_station_created`
  ON `OBHS_passenger` (`train_no`, `station_id`, `created`);

-- OBHS_Feedback .
-- NOt done for future unique key issue
CREATE INDEX `idx_feedback_passenger_feed_value`
  ON `OBHS_feedback` (`passenger_id`, `feed_param`, `value`);

 --Done
CREATE INDEX `idx_feedback_created_passenger`
  ON `OBHS_feedback` (`created`, `passenger_id`);

-- OBHS_Train 
--DONE
CREATE INDEX `idx_fb_target_station_train`
  ON `base_fb_target` (`station`, `train_no`);

--DONE
CREATE INDEX `idx_fb_target_station_created`
  ON `base_fb_target` (`station`, `created_at`);


-- OBhS_Attendance 
--DONE
CREATE INDEX `idx_attendance_station_train_grade_type_created`
  ON `base_attendance` (`station_id`, `train_no`, `grade`, `type_of_attendance`, `created_at`);
--done
CREATE INDEX `idx_attendance_employee_station_id`
  ON `base_attendance` (`employee_id`, `station_id`, `id`);

--done
CREATE INDEX `idx_attendance_station_created_train`
  ON `base_attendance` (`station_id`, `created_at`, `train_no`);

--Done
CREATE INDEX `idx_attendance_duplicate_day`
  ON `base_attendance` (`employee_id`, `station_id`, `type_of_attendance`, `train_no`, `grade`, `created_at`, `id`);



-- OBHS_Photo report 
--Done
CREATE INDEX `idx_photo_station_train_grade_coach_created`
  ON `base_photo_report` (`station_id`, `train_no`, `grade`, `coach_no`, `created_at`);

  
--done
CREATE INDEX `idx_photo_station_train_grade_clean_created`
  ON `base_photo_report` (`station_id`, `train_no`, `grade`, `time_of_cleaning`, `created_at`);



-- Question and marking 
--done
CREATE INDEX `idx_questions_station_type_id`
  ON `OBHS_questions` (`station_id`, `type`, `id`);

--done
CREATE INDEX `idx_questions_user_id`
  ON `OBHS_questions` (`user_id`, `id`);

--done
CREATE INDEX `idx_marking_station_value`
  ON `OBHS_marking` (`station_id`, `value`);

--done
CREATE INDEX `idx_marking_user_value`
  ON `OBHS_marking` (`user_id`, `value`);

-- User login.
--done
CREATE INDEX `idx_users_username_type`
  ON `OBHS_users` (`username`, `type`);

  --done
CREATE INDEX `idx_users_email_type`
  ON `OBHS_users` (`email`, `type`);


--done
CREATE INDEX `idx_users_station_type_status_end`
  ON `OBHS_users` (`station_id`, `type`, `status`, `end_date`);

CREATE INDEX `idx_users_type_end_status`
  ON `OBHS_users` (`type`, `end_date`, `status`);

--OBHS_Reports
--done
CREATE INDEX `idx_reports_user_status_id`
  ON `OBHS_reports` (`user_id`, `status`, `id`);

--done
CREATE INDEX `idx_reports_station_type_status`
  ON `OBHS_reports` (`station_id`, `type`, `status`);

-- obhs_Employee 
--done
CREATE INDEX `idx_employees_station_created`
  ON `base_employees` (`station_id`, `created_at`);

--NOT done for future unique key issue
CREATE UNIQUE INDEX `uq_employees_station_employee`
  ON `base_employees` (`station_id`, `employee_id`);

--done
CREATE INDEX `idx_employees_jodhpur_station_created`
  ON `base_employees_jodhpur` (`station_id`, `created_at`);

--done
CREATE INDEX `idx_employees_jodhpur_station_employee`
  ON `base_employees_jodhpur` (`station_id`, `employee_id`);

-- PDF attendance.
--done
CREATE INDEX `idx_pdf_attendence_station_trains_date_id`
  ON `pdf_attendence` (`station_id`, `train_up`, `train_down`, `from_date`, `id`);

-- Admin advertisement.
--done
CREATE INDEX `idx_globaladvertisment_date`
  ON `OBHS_Globaladvertisment` (`date`);


