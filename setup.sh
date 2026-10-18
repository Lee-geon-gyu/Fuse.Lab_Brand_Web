#!/usr/bin/env bash
# macOS / Linux 용 로컬 개발환경 세팅
set -e
cd "$(dirname "$0")"

echo "[1/6] Docker 확인"
command -v docker >/dev/null || { echo "Docker Desktop 이 설치되어 있지 않습니다. https://www.docker.com/products/docker-desktop/"; exit 1; }
docker info >/dev/null 2>&1 || { echo "Docker Desktop 을 실행한 뒤 다시 시도하세요."; exit 1; }

echo "[2/6] .env 준비"
if [ ! -f .env ]; then cp .env.example .env; echo ".env 생성"; else echo "기존 .env 사용"; fi
set -a; . ./.env; set +a

echo "[3/6] 컨테이너 빌드/기동"
docker compose up -d --build || { echo "기동 실패. 포트가 겹치면 .env 의 포트를 바꾸고 다시 실행하세요."; exit 1; }

echo "[4/6] DB 연결 대기"
for i in $(seq 1 31); do
  docker compose exec -T web php -r "require 'app/bootstrap.php'; db();" >/dev/null 2>&1 && break
  if [ "$i" -eq 31 ]; then echo "DB 연결 실패. docker compose logs db 를 확인하세요."; exit 1; fi
  sleep 2
done

echo "[5/6] db/schema.sql 적용"
docker compose exec -T db mariadb -ufuselab -pfuselab fuselab < db/schema.sql || { echo "db/schema.sql 적용 실패"; exit 1; }

# 공유 데이터는 빈 DB 에만 넣는다 (로컬에서 고친 데이터를 덮어쓰지 않음)
echo "[6/6] db/dump.sql 적용 (빈 DB 일 때만)"
rows=$(docker compose exec -T db mariadb -ufuselab -pfuselab -N -s fuselab -e "SELECT (SELECT COUNT(*) FROM posts) + (SELECT COUNT(*) FROM portfolios)") || { echo "기존 데이터 확인 실패"; exit 1; }
if [ "$rows" = "0" ]; then
  docker compose exec -T db mariadb -ufuselab -pfuselab fuselab < db/dump.sql || { echo "db/dump.sql 적용 실패"; exit 1; }
  echo "db/dump.sql 적용 완료"
else
  echo "건너뜀: DB 에 이미 데이터가 있음. 초기화하려면 docker compose down -v 후 setup 다시 실행"
fi

echo
echo "완료"
echo "  사이트      http://localhost:$WEB_PORT"
echo "  관리자      http://localhost:$WEB_PORT/admin/  (처음엔 승인 코드 ${ADMIN_SIGNUP_CODE:-fuselab-dev} 로 회원가입)"
echo "  phpMyAdmin  http://localhost:$PMA_PORT"
echo "  MariaDB     localhost:$DB_PORT_HOST  (fuselab / fuselab)"
echo "  종료: docker compose down"
