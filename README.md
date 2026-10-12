### Fuse.Lab_Brand_Web



## Hello we are fuse.labs member



## I am team leader "Lee"

---

## 개발 환경

- 운영: 카페24 10G 광아우토반 FullSSD+ (PHP 8.2 / MariaDB), 설치형 프레임워크 없이 순수 PHP
- 로컬: Docker (`php:8.2-apache` + `mariadb:10.6` + phpMyAdmin)

새 컴퓨터 세팅: [Docker Desktop](https://www.docker.com/products/docker-desktop/) 설치·실행 → 저장소 clone → 아래 실행

- Windows: `setup.bat` 더블클릭
- macOS / Linux: `./setup.sh`

Docker 확인 → `.env` 생성(없을 때만) → 컨테이너 빌드/기동 → DB 연결 확인 → `db/schema.sql` 적용 → `db/dump.sql` 적용(빈 DB 일 때만) → 접속 주소 출력. 다시 실행해도 안전.

| 서비스 | 주소 |
| --- | --- |
| 사이트 | http://localhost:8088 |
| 관리자 | http://localhost:8088/admin/ |
| phpMyAdmin | http://localhost:8089 |
| MariaDB (외부 클라이언트) | `localhost:3310` / `fuselab` / `fuselab` |

포트 충돌 시 `.env` 의 `WEB_PORT`, `PMA_PORT`, `DB_PORT_HOST` 값을 바꾸고 setup 을 다시 실행.

### 관리자 계정

기본 계정 `admin` / `qwer1234` 가 `db/schema.sql` 실행 시 들어간다 (이름 `관리자`, 이메일 `admin@fuselab.example`). 같은 ID 가 이미 있으면 비밀번호를 덮어쓰지 않는다.
공개 저장소에 비밀번호가 적혀 있으므로 운영에서는 배포 직후 비밀번호를 바꾼다 (로그인 화면 > 비밀번호 찾기, 또는 phpMyAdmin 에서 `password_hash` 교체).

추가 계정은 `/admin/signup.php` 에서 승인 코드로 가입한다 (ID·비밀번호·이름·이메일).

- 로컬 승인 코드: `fuselab-dev` (`.env` 의 `ADMIN_SIGNUP_CODE` 로 변경)
- 운영 승인 코드: `app/config.local.php` 의 `signup_code` (비우면 가입 불가)
- ID/비밀번호 찾기: 로그인 화면 다이얼로그 → 가입 이메일로 ID 안내 또는 비밀번호 재설정 링크(1시간 유효) 메일
  - 로컬은 메일을 실제로 보내지 않고 `app/storage/mail.log` 에 남긴다 (재설정 링크도 여기서 확인)
  - 이메일이 없는 계정(이메일 기능 이전 가입)은 phpMyAdmin 에서 `admins.email` 을 채워야 찾기 가능

### 로컬 공유 데이터 (`db/dump.sql`)

DB 데이터는 Docker 볼륨에 있어서 git 으로 공유되지 않는다. 그래서 콘텐츠 테이블(`portfolios`, `posts`, `post_files`, `chatbot_settings`) 데이터만 `db/dump.sql` 로 커밋한다.
계정(`admins`)·견적문의·비밀번호 재설정·방문 집계는 넣지 않는다 (공개 저장소). 계정은 `db/schema.sql` 의 기본 관리자를 쓴다.

- setup 은 `posts`·`portfolios` 가 둘 다 비어 있을 때만 `db/dump.sql` 을 넣는다. 로컬에서 고친 데이터는 덮어쓰지 않는다
- 덤프로 초기화: `docker compose down -v` (로컬 DB 삭제) → setup 다시 실행
- 덤프 갱신 (내 로컬 데이터를 공유할 때, cmd·PowerShell·bash 공통):

```
docker compose exec -T db mariadb-dump -ufuselab -pfuselab --no-tablespaces --no-create-info --insert-ignore --complete-insert --skip-extended-insert --skip-dump-date --skip-comments --skip-add-locks --skip-disable-keys --skip-triggers --default-character-set=utf8mb4 -r /tmp/dump.sql fuselab portfolios posts post_files chatbot_settings
docker compose cp db:/tmp/dump.sql db/dump.sql
```

컨테이너 안에서 파일로 쓰고 복사하는 이유: PowerShell 의 `>` 리다이렉트는 UTF-16 으로 저장해 한글이 깨진다.
업로드한 이미지(`uploads/posts/`)는 git 제외라서 덤프에 업로드 이미지 경로가 들어가면 다른 PC 에서는 깨진다. 공유할 이미지는 `aseets/` 경로를 쓴다.

덤프 없이 시안 데이터만 넣을 때는 아래를 한 번씩 실행 (데이터가 이미 있으면 아무것도 넣지 않음. 카페24 는 phpMyAdmin 에서 같은 파일 실행)

```
docker compose exec -T db mariadb -ufuselab -pfuselab fuselab < db/seed-portfolios.sql
docker compose exec -T db mariadb -ufuselab -pfuselab fuselab < db/seed-portfolio.sql
```

## 구조

- 저장소 루트 = 웹 루트 (카페24 `www/` 에 그대로 업로드)
- `*.html`, `sub-pages/` — 퍼블리싱 원본 (유지)
- `index.php`, `portfolio*.php`, `admin/` — 퍼블리싱 화면을 공통 레이아웃으로 조합한 PHP 페이지 (관리자: http://localhost:8088/admin/)
- `app/` — PHP 공통 코드 (bootstrap, 설정, DB, `views/` 레이아웃). `.htaccess` 로 웹 접근 차단
- `uploads/posts/` — 게시판 첨부·포트폴리오·챗봇 아이콘 이미지 (git 제외, `uploads/.htaccess` 로 스크립트 실행 차단)
- `app/storage/` — 로컬 메일 로그 (git 제외, 웹 접근 차단)
- PHP 파일 목록: [page-map.md](page-map.md) / 화면 설계: [docs/design.md](docs/design.md)

## 카페24 배포

1. `app/config.local.example.php` → `app/config.local.php` 로 복사 후 카페24 DB 정보, `signup_code`, `mail_from`(보내는 사람), `site_url`(사이트 주소, 재설정 메일 링크용) 입력
2. 카페24 DB 관리(phpMyAdmin) > SQL 에서 `db/schema.sql` 실행. 이미 이전 버전 스키마를 적용한 DB 에 다시 실행해도 안전함 (`admins.email`, `posts.portfolio_id` 는 `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` 로 추가됨)
3. 기본 계정 `admin` / `qwer1234` 로 `/admin/login.php` 로그인 후 비밀번호 변경 (이메일을 실제 주소로 바꿔야 비밀번호 찾기 메일을 받을 수 있음)
4. 시안 데이터가 필요하면 `db/seed-portfolios.sql`(포트폴리오 목록 6개) → `db/seed-portfolio.sql`(메인 슬라이드 4개, VIEW PROJECT 를 목록 1~4번째에 연결) 순서로 한 번씩 실행
5. `uploads/.htaccess` 를 함께 업로드. `uploads/posts/` 는 첫 업로드 때 자동 생성되며, 실패하면 FTP 로 만들고 쓰기 권한(755/707) 부여
6. 배포 후 확인: 견적문의 제출, ID/비밀번호 찾기 메일 수신(카페24 `mail()` 발송 여부 미확인), 이미지 업로드
7. `docker/`, `docker-compose.yml`, `*.md` 는 업로드하지 않음 (업로드돼도 `.htaccess` 로 차단)

## 관리자 기능

| 메뉴 | 기능 |
| --- | --- |
| 대시보드 | 월별 방문자(주차별 막대)·챗봇 사용률(방문 세션 대비 챗봇을 연 세션), 관리자 계정 현황, 최근 견적문의·게시글 |
| 견적문의 | 메인 폼으로 들어온 문의 목록·검색·상세, 상태(접수 완료/검토 중/답변 대기/처리 완료/반려)·메모 저장, 삭제 |
| 게시판 | 공지사항·이벤트·자료실·포트폴리오(메인 슬라이드) 글 작성/수정/노출/삭제, 첨부 |
| 포트폴리오 | `/portfolio.php` 목록·상세에 나오는 프로젝트 등록/수정, 분류·순서·노출 관리 |
| 챗봇 설정 | 사용자 사이트 우하단 챗봇의 이름·안내문구·아이콘·색상·말투·환영 메시지. 챗봇 응답은 키워드 규칙 기반 (외부 AI 미사용) |

방문자 수는 사용자 페이지(메인·포트폴리오 목록·상세)를 연 세션을 하루 1회씩 센다.

### 메인 포트폴리오 관리

관리자 > 게시판에서 게시판을 `포트폴리오` 로 골라 글을 쓰면 메인 포트폴리오 슬라이드가 된다.

- 노출된 글을 먼저 쓴 순서대로 보여주고, 글 수만큼 슬라이드가 늘어남 (번호 `01 / 05` 자동)
- 제목 = 큰 제목, 상단 문구 = 제목 위 작은 글자, 내용 = 설명(줄바꿈 유지), 배경 이미지 필수 (JPG·PNG·WEBP·GIF 10MB 이하)
- 숨김 처리하면 메인에서 빠짐
- 관리자로 로그인한 채 메인을 열면 슬라이드마다 `이 슬라이드 수정` 버튼과 화면 오른쪽 아래 `포트폴리오 슬라이드 추가` 메뉴가 보임. 저장하면 메인으로 돌아옴
