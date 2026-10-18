-- 포트폴리오 목록·상세 초기 데이터 (퍼블리싱 시안 6개). schema.sql 다음에 실행.
-- portfolios 에 한 건이라도 있으면 아무것도 넣지 않는다 (여러 번 실행해도 안전).
-- 쿠무만 퍼블리싱 상세 화면 내용이 있고, 나머지 5개의 분류·소개 문구는 임의로 넣은 값이다.
INSERT INTO portfolios (title, category, client, type, project_date, summary, site_url, thumbnail, hero_image, detail_image, sort_order)
SELECT seed.title, seed.category, seed.client, seed.type, seed.project_date, seed.summary, seed.site_url,
       seed.thumbnail, seed.hero_image, seed.detail_image, seed.sort_order
FROM (
  SELECT 1 AS sort_order, 'JW 중외제약' AS title, 'uiux' AS category, 'JW 중외제약' AS client, '' AS type, '' AS project_date,
         'JW 중외제약 웹사이트 프로젝트입니다.' AS summary, '' AS site_url,
         '/aseets/imgs/portfolio-sub/jw.png' AS thumbnail, NULL AS hero_image, NULL AS detail_image
  UNION ALL SELECT 2, 'CJ 올리브영', 'front', 'CJ 올리브영', '', '',
         'CJ 올리브영 웹사이트 프로젝트입니다.', '',
         '/aseets/imgs/portfolio-sub/cj.png', NULL, NULL
  UNION ALL SELECT 3, '쿠무', 'branding', 'Kumu', 'Branding, UI/UX Design, Responsive Web', '2025.10 ~ 2025.12',
         '쿠무는 사람과 반려견이 함께 식사와 디저트를 즐기며 특별한 시간을 만들어가는 반려동물 동반 카페·베이커리 브랜드입니다.\n분산된 메뉴와 이용 정보, 브랜드 콘텐츠를 하나의 웹사이트 안에서 연결하고, 계절에 따라 변화하는 메뉴와 클래스, 이벤트를 자연스럽게 탐색할 수 있도록 반응형 웹사이트를 설계했습니다.',
         'https://tnfh23-web.github.io/kumu/',
         '/aseets/imgs/portfolio-sub/kumu.png', '/aseets/imgs/portfolio-sub/back.png', '/aseets/imgs/portfolio-sub/kumu-pro.png'
  UNION ALL SELECT 4, '스타스케이프', 'mvp', '스타스케이프', '', '',
         '스타스케이프 서비스 MVP 프로젝트입니다.', '',
         '/aseets/imgs/portfolio-sub/star.png', NULL, NULL
  UNION ALL SELECT 5, '건일제약', 'detail', '건일제약', '', '',
         '건일제약 상세페이지 프로젝트입니다.', '',
         '/aseets/imgs/portfolio-sub/Geonil.png', NULL, NULL
  UNION ALL SELECT 6, '잇고', 'mvp', '잇고', '', '',
         '잇고 서비스 MVP 프로젝트입니다.', '',
         '/aseets/imgs/portfolio-sub/eatgo.png', NULL, NULL
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM portfolios)
ORDER BY seed.sort_order;
