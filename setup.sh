#!/usr/bin/env bash
# macOS / Linux 용 로컬 개발환경 세팅
set -e
cd "$(dirname "$0")"

echo "[1/4] Docker 확인"
command -v docker >/dev/null || { echo "Docker Desktop 이 설치되어 있지 않습니다. https://www.docker.com/products/docker-desktop/"; exit 1; }
docker info >/dev/null 2>&1 || { echo "Docker Desktop 을 실행한 뒤 다시 시도하세요."; exit 1; }

echo "[2/4] .env 준비"
if [ ! -f .env ]; then cp .env.example .env; echo ".env 생성"; else echo "기존 .env 사용"; fi
set -a; . ./.env; set +a

echo "[3/4] 컨테이너 빌드/기동"
docker compose up -d --build || { echo "기동 실패. 포트가 겹치면 .env 의 포트를 바꾸고 다시 실행하세요."; exit 1; }

echo "[4/4] DB 연결 대기"
for i in $(seq 1 30); do
  if docker compose exec -T web php -r "require 'app/bootstrap.php'; db();" >/dev/null 2>&1; then
    echo
    echo "완료"
    echo "  사이트      http://localhost:$WEB_PORT"
    echo "  phpMyAdmin  http://localhost:$PMA_PORT"
    echo "  MariaDB     localhost:$DB_PORT_HOST  (fuselab / fuselab)"
    echo "  종료: docker compose down"
    exit 0
  fi
  sleep 2
done
echo "DB 연결 실패. docker compose logs db 를 확인하세요."
exit 1
