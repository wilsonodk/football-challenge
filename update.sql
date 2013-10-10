-- Changes for v2.2.0

-- Add a new field to users
ALTER TABLE `football_challenge_users` ADD COLUMN `reminder` tinyint(3) unsigned NOT NULL DEFAULT 1;
