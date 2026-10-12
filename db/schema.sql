-- fuse.lab DB 스키마. 여러 번 실행해도 안전하게 작성한다 (CREATE TABLE IF NOT EXISTS).
-- 로컬: setup 실행 시 자동 적용 / 카페24: DB 관리(phpMyAdmin) > SQL 에서 실행

CREATE TABLE IF NOT EXISTS admins (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  login_id      VARCHAR(50)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  name          VARCHAR(50)  NOT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admins_login_id (login_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 계정 찾기용 이메일 (기존 DB 에도 적용되도록 ALTER ... IF NOT EXISTS, MariaDB 10.0.2+)
ALTER TABLE admins ADD COLUMN IF NOT EXISTS email VARCHAR(255) NULL AFTER name;

-- 기본 관리자 계정 admin / qwer1234 (bcrypt 해시). 같은 login_id 가 있으면 건드리지 않는다 (비밀번호 덮어쓰지 않음)
INSERT IGNORE INTO admins (login_id, password_hash, name, email)
VALUES ('admin', '$2y$10$KWitJ4BCPDWFEnlF8VyZZOuNOFaBopERwq7ATHCTGU8k5PhQZbULe', '관리자', 'admin@fuselab.example');

-- 비밀번호 재설정 링크. token_hash = SHA-256(메일로 보낸 토큰)
CREATE TABLE IF NOT EXISTS password_resets (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id   INT UNSIGNED NOT NULL,
  token_hash CHAR(64)     NOT NULL,
  expires_at DATETIME     NOT NULL,
  used_at    DATETIME     NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_password_resets_token (token_hash),
  CONSTRAINT fk_password_resets_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 게시판 글. board: notice(공지사항) / event(이벤트) / resources(자료실) / portfolio(메인 포트폴리오 슬라이드)
-- portfolio 글은 eyebrow(제목 위 작은 문구)·image(배경 이미지 경로)·portfolio_id(VIEW PROJECT 로 열 포트폴리오 상세)를 함께 쓴다
CREATE TABLE IF NOT EXISTS posts (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  board      VARCHAR(20)  NOT NULL,
  title      VARCHAR(120) NOT NULL,
  eyebrow    VARCHAR(60)  NOT NULL DEFAULT '',
  portfolio_id INT UNSIGNED NULL,
  content    TEXT         NOT NULL,
  image      VARCHAR(255) NULL,
  is_visible TINYINT(1)   NOT NULL DEFAULT 1,
  admin_id   INT UNSIGNED NULL,
  views      INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_posts_board (board, is_visible, id),
  CONSTRAINT fk_posts_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 슬라이드 → 포트폴리오 상세 연결 (기존 DB 에도 적용). 대상이 삭제·숨김이면 화면에서 연결 없음으로 처리
ALTER TABLE posts ADD COLUMN IF NOT EXISTS portfolio_id INT UNSIGNED NULL AFTER eyebrow;

CREATE TABLE IF NOT EXISTS post_files (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id       INT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name   VARCHAR(100) NOT NULL,
  size          INT UNSIGNED NOT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_post_files_post (post_id),
  CONSTRAINT fk_post_files_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 메인 견적문의. status: received(접수 완료) / reviewing(검토 중) / waiting(답변 대기) / done(처리 완료) / rejected(반려)
CREATE TABLE IF NOT EXISTS inquiries (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name              VARCHAR(50)  NOT NULL,
  email             VARCHAR(255) NOT NULL,
  phone             VARCHAR(30)  NOT NULL,
  budget            VARCHAR(100) NOT NULL DEFAULT '',
  schedule          VARCHAR(100) NOT NULL DEFAULT '',
  company           VARCHAR(100) NOT NULL DEFAULT '',
  details           TEXT         NOT NULL,
  status            VARCHAR(20)  NOT NULL DEFAULT 'received',
  admin_memo        TEXT         NULL,
  privacy_agreed_at DATETIME     NOT NULL,
  ip                VARCHAR(45)  NOT NULL DEFAULT '',
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_inquiries_created (created_at),
  KEY idx_inquiries_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 포트폴리오 목록/상세 페이지 (메인 슬라이드는 posts.board = 'portfolio' 로 별도 관리)
-- category: mvp / branding / uiux / front / detail
CREATE TABLE IF NOT EXISTS portfolios (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title        VARCHAR(120) NOT NULL,
  category     VARCHAR(20)  NOT NULL,
  client       VARCHAR(100) NOT NULL DEFAULT '',
  type         VARCHAR(200) NOT NULL DEFAULT '',
  project_date VARCHAR(50)  NOT NULL DEFAULT '',
  summary      TEXT         NOT NULL,
  site_url     VARCHAR(255) NOT NULL DEFAULT '',
  thumbnail    VARCHAR(255) NOT NULL,
  hero_image   VARCHAR(255) NULL,
  detail_image VARCHAR(255) NULL,
  sort_order   INT          NOT NULL DEFAULT 0,
  is_visible   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_portfolios_list (is_visible, category, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 챗봇 설정 1건 (id = 1 고정). 행이 없으면 기본값 사용
CREATE TABLE IF NOT EXISTS chatbot_settings (
  id              TINYINT UNSIGNED NOT NULL,
  ai_name         VARCHAR(50)  NOT NULL,
  guide_text      VARCHAR(200) NOT NULL DEFAULT '',
  icon            VARCHAR(255) NULL,
  theme_color     CHAR(7)      NOT NULL DEFAULT '#6a00ff',
  tone            VARCHAR(20)  NOT NULL DEFAULT 'friendly',
  welcome_message TEXT         NOT NULL,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 일별 집계. visitors = 그날 사용자 페이지를 연 세션 수, chatbot_users = 그날 챗봇을 연 세션 수
CREATE TABLE IF NOT EXISTS visit_stats (
  visit_date    DATE         NOT NULL,
  visitors      INT UNSIGNED NOT NULL DEFAULT 0,
  chatbot_users INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (visit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
