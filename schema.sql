-- ==========================================================
--  مخطط قاعدة بيانات نظام المهام (MariaDB / MySQL)
--  آمن للتشغيل المتكرر (IF NOT EXISTS)
-- ==========================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(120) NOT NULL,
  username      VARCHAR(60)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          VARCHAR(20)  NOT NULL DEFAULT 'head',   -- manager | head
  department    VARCHAR(120) DEFAULT NULL,
  active        TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tasks (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title        VARCHAR(200) NOT NULL,
  details      TEXT         NOT NULL,
  department   VARCHAR(120) DEFAULT NULL,
  priority     VARCHAR(20)  NOT NULL DEFAULT 'متوسطة',
  status       VARCHAR(20)  NOT NULL DEFAULT 'جديدة',
  due_date     DATE         DEFAULT NULL,
  manager_note VARCHAR(600) DEFAULT NULL,
  created_by   INT UNSIGNED DEFAULT NULL,
  assigned_to  INT UNSIGNED DEFAULT NULL,
  reply        TEXT         DEFAULT NULL,
  replied_by   VARCHAR(120) DEFAULT NULL,
  replied_at   DATETIME     DEFAULT NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_status (status),
  KEY idx_assigned (assigned_to),
  KEY idx_created (created_at),
  CONSTRAINT fk_task_creator  FOREIGN KEY (created_by)  REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_task_assignee FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS task_comments (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  task_id    INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED DEFAULT NULL,
  author     VARCHAR(120) NOT NULL,
  role       VARCHAR(20)  NOT NULL,
  body       TEXT         NOT NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_task (task_id),
  CONSTRAINT fk_comment_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
