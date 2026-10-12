-- 메인 포트폴리오 슬라이드 초기 데이터 (퍼블리싱 시안 4개). schema.sql 다음에 한 번만 실행.
-- 포트폴리오 글이 하나라도 있으면 아무것도 넣지 않는다.
INSERT INTO posts (board, title, eyebrow, content, image, admin_id)
SELECT seed.board, seed.title, seed.eyebrow, seed.content, seed.image, seed.admin_id
FROM (
  SELECT 1 AS sort, 'portfolio' AS board, 'THE SPACE KIDS' AS title, 'BRAND EXPERIENCE' AS eyebrow,
         '우주의 무한한 가능성을 탐험하고\n새로운 경험을 만드는 브랜드입니다.' AS content,
         '/aseets/images/section-portfolio/portfolio-1.png' AS image, (SELECT MIN(id) FROM admins) AS admin_id
  UNION ALL SELECT 2, 'portfolio', 'THE SPACE KIDS', 'BRAND EXPERIENCE',
         '우주의 무한한 가능성을 탐험하고\n새로운 경험을 만드는 브랜드입니다.',
         '/aseets/images/section-portfolio/portfolio-2.png', (SELECT MIN(id) FROM admins)
  UNION ALL SELECT 3, 'portfolio', 'THE SPACE KIDS', 'BRAND EXPERIENCE',
         '우주의 무한한 가능성을 탐험하고\n새로운 경험을 만드는 브랜드입니다.',
         '/aseets/images/section-portfolio/portfolio-3.png', (SELECT MIN(id) FROM admins)
  UNION ALL SELECT 4, 'portfolio', 'THE SPACE KIDS', 'BRAND EXPERIENCE',
         '우주의 무한한 가능성을 탐험하고\n새로운 경험을 만드는 브랜드입니다.',
         '/aseets/images/section-portfolio/portfolio-4.png', (SELECT MIN(id) FROM admins)
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM posts WHERE board = 'portfolio')
ORDER BY seed.sort;
