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

Docker 확인 → `.env` 생성(없을 때만) → 컨테이너 빌드/기동 → DB 연결 확인 → 접속 주소 출력. 다시 실행해도 안전.

| 서비스 | 주소 |
| --- | --- |
| 사이트 | http://localhost:8088 |
| phpMyAdmin | http://localhost:8089 |
| MariaDB (외부 클라이언트) | `localhost:3310` / `fuselab` / `fuselab` |

포트 충돌 시 `.env` 의 `WEB_PORT`, `PMA_PORT`, `DB_PORT_HOST` 값을 바꾸고 setup 을 다시 실행.

## 구조

- 저장소 루트 = 웹 루트 (카페24 `www/` 에 그대로 업로드)
- `*.html`, `sub-pages/` — 퍼블리싱 원본 (유지)
- `index.php`, `portfolio*.php`, `admin/` — 퍼블리싱 화면을 공통 레이아웃으로 조합한 PHP 페이지 (관리자: http://localhost:8088/admin/)
- `app/` — PHP 공통 코드 (bootstrap, 설정, DB, `views/` 레이아웃). `.htaccess` 로 웹 접근 차단
- PHP 파일 목록: [page-map.md](page-map.md) / 화면 설계: [docs/design.md](docs/design.md)

## 카페24 배포

1. `app/config.local.example.php` → `app/config.local.php` 로 복사 후 카페24 DB 정보 입력
2. `docker/`, `docker-compose.yml`, `*.md` 는 업로드하지 않음 (업로드돼도 `.htaccess` 로 차단)
