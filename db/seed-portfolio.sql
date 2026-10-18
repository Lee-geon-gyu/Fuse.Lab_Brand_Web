-- 메인 포트폴리오 슬라이드 초기 데이터 (퍼블리싱 시안 2개). schema.sql → seed-portfolios.sql 다음에 한 번만 실행.
-- 포트폴리오 글이 하나라도 있으면 아무것도 넣지 않는다.
-- 각 슬라이드의 VIEW PROJECT 는 portfolios 순서상 1~2번째 항목에 연결 (portfolios 가 비어 있으면 연결 없음)
INSERT INTO posts (board, title, eyebrow, portfolio_id, content, image, admin_id)
SELECT seed.board, seed.title, seed.eyebrow,
       (SELECT f.id FROM portfolios f
         WHERE (SELECT COUNT(*) FROM portfolios g WHERE (g.sort_order, g.id) < (f.sort_order, f.id)) = seed.sort - 1) AS portfolio_id,
       seed.content, seed.image, seed.admin_id
FROM (
  SELECT 1 AS sort, 'portfolio' AS board, 'THE SPACE KIDS' AS title, 'BRAND EXPERIENCE' AS eyebrow,
         '우주의 무한한 가능성을 탐험하고\n새로운 경험을 만드는 브랜드입니다.' AS content,
         '/aseets/images/section-portfolio/portfolio-1.png' AS image, (SELECT MIN(id) FROM admins) AS admin_id
  UNION ALL SELECT 2, 'portfolio', 'THE SPACE KIDS', 'BRAND EXPERIENCE',
         '우주의 무한한 가능성을 탐험하고\n새로운 경험을 만드는 브랜드입니다.',
         '/aseets/images/section-portfolio/portfolio-2.png', (SELECT MIN(id) FROM admins)
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM posts WHERE board = 'portfolio')
ORDER BY seed.sort;
