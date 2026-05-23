-- phpMyAdmin SQL Dump
-- TravHub v2 Migration : ALTER `travelers`
-- Adds AI-summary + structured-info columns for the document intelligence pipeline.
--
-- Database: `travhub_dev`
-- Engine: InnoDB   Charset: utf8mb4   Collation: utf8mb4_general_ci
-- Timezone (app): Asia/Dhaka
--
-- Safe to run once. Re-running will error on duplicate columns (expected).
-- All columns are NULLable so existing rows are unaffected.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- New columns for table `travelers`
--
ALTER TABLE `travelers`
  ADD COLUMN `summary` longtext DEFAULT NULL
    COMMENT 'AI-generated living profile narrative (re-merged each batch)'
    AFTER `travel_history`,

  ADD COLUMN `history_summary` longtext DEFAULT NULL
    COMMENT 'JSON array of previous summary snapshots-> [{"text":"...","date":"21-05-2026 10:30"}, ...]'
    AFTER `summary`,

  ADD COLUMN `summary_info` text DEFAULT NULL
    COMMENT 'JSON {"taken_token": int, "time": "2.3s"} for the last traveler-merge Gemini call'
    AFTER `history_summary`,

  ADD COLUMN `personal_info` longtext DEFAULT NULL
    COMMENT 'JSON-> DOB, gender, blood_group, religion, marital_status, etc.'
    AFTER `summary_info`,

  ADD COLUMN `family_info` longtext DEFAULT NULL
    COMMENT 'JSON-> father, mother, spouse, children details'
    AFTER `personal_info`,

  ADD COLUMN `employment_info` longtext DEFAULT NULL
    COMMENT 'JSON-> current employer, designation, salary, employment_type'
    AFTER `family_info`,

  ADD COLUMN `educational_info` longtext DEFAULT NULL
    COMMENT 'JSON-> degrees, institutions, passing_years'
    AFTER `employment_info`,

  ADD COLUMN `work_info` longtext DEFAULT NULL
    COMMENT 'JSON-> work history / experience records'
    AFTER `educational_info`,

  ADD COLUMN `others_info` longtext DEFAULT NULL
    COMMENT 'JSON-> miscellaneous extra fields'
    AFTER `work_info`;

COMMIT;