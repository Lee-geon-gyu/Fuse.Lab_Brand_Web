/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

INSERT IGNORE INTO `portfolios` (`id`, `title`, `category`, `client`, `type`, `project_date`, `summary`, `site_url`, `thumbnail`, `hero_image`, `detail_image`, `sort_order`, `is_visible`, `created_at`, `updated_at`) VALUES (1,'JW 중외제약','uiux','JW 중외제약','','','JW 중외제약 웹사이트 프로젝트입니다.','','/aseets/imgs/portfolio-sub/jw.png',NULL,NULL,1,1,'2026-10-12 22:14:08','2026-10-12 22:23:51');
INSERT IGNORE INTO `portfolios` (`id`, `title`, `category`, `client`, `type`, `project_date`, `summary`, `site_url`, `thumbnail`, `hero_image`, `detail_image`, `sort_order`, `is_visible`, `created_at`, `updated_at`) VALUES (2,'CJ 올리브영','front','CJ 올리브영','','','CJ 올리브영 웹사이트 프로젝트입니다.','','/aseets/imgs/portfolio-sub/cj.png',NULL,NULL,2,1,'2026-10-12 22:14:08','2026-10-12 22:49:48');
INSERT IGNORE INTO `portfolios` (`id`, `title`, `category`, `client`, `type`, `project_date`, `summary`, `site_url`, `thumbnail`, `hero_image`, `detail_image`, `sort_order`, `is_visible`, `created_at`, `updated_at`) VALUES (3,'쿠무','branding','Kumu','Branding, UI/UX Design, Responsive Web','2025.10 ~ 2025.12','쿠무는 사람과 반려견이 함께 식사와 디저트를 즐기며 특별한 시간을 만들어가는 반려동물 동반 카페·베이커리 브랜드입니다.\n분산된 메뉴와 이용 정보, 브랜드 콘텐츠를 하나의 웹사이트 안에서 연결하고, 계절에 따라 변화하는 메뉴와 클래스, 이벤트를 자연스럽게 탐색할 수 있도록 반응형 웹사이트를 설계했습니다.','https://tnfh23-web.github.io/kumu/','/aseets/imgs/portfolio-sub/kumu.png','/aseets/imgs/portfolio-sub/back.png','/aseets/imgs/portfolio-sub/kumu-pro.png',3,1,'2026-10-12 22:14:08','2026-10-12 22:14:08');
INSERT IGNORE INTO `portfolios` (`id`, `title`, `category`, `client`, `type`, `project_date`, `summary`, `site_url`, `thumbnail`, `hero_image`, `detail_image`, `sort_order`, `is_visible`, `created_at`, `updated_at`) VALUES (4,'스타스케이프','mvp','스타스케이프','','','스타스케이프 서비스 MVP 프로젝트입니다.','','/aseets/imgs/portfolio-sub/star.png',NULL,NULL,4,1,'2026-10-12 22:14:08','2026-10-12 22:14:08');
INSERT IGNORE INTO `portfolios` (`id`, `title`, `category`, `client`, `type`, `project_date`, `summary`, `site_url`, `thumbnail`, `hero_image`, `detail_image`, `sort_order`, `is_visible`, `created_at`, `updated_at`) VALUES (5,'건일제약','detail','건일제약','','','건일제약 상세페이지 프로젝트입니다.','','/aseets/imgs/portfolio-sub/Geonil.png',NULL,NULL,5,1,'2026-10-12 22:14:08','2026-10-12 22:14:08');
INSERT IGNORE INTO `portfolios` (`id`, `title`, `category`, `client`, `type`, `project_date`, `summary`, `site_url`, `thumbnail`, `hero_image`, `detail_image`, `sort_order`, `is_visible`, `created_at`, `updated_at`) VALUES (6,'잇고','mvp','잇고','','','잇고 서비스 MVP 프로젝트입니다.','','/aseets/imgs/portfolio-sub/eatgo.png',NULL,NULL,6,1,'2026-10-12 22:14:08','2026-10-12 22:17:53');

INSERT IGNORE INTO `posts` (`id`, `board`, `title`, `eyebrow`, `portfolio_id`, `content`, `image`, `is_visible`, `admin_id`, `views`, `created_at`, `updated_at`) VALUES (1,'portfolio','THE SPACE KIDS','BRAND EXPERIENCE',1,'우주의 무한한 가능성을 탐험하고\r\n새로운 경험을 만드는 브랜드입니다.','/aseets/images/section-portfolio/portfolio-1.png',1,1,0,'2026-10-12 21:49:46','2026-10-12 22:49:36');
INSERT IGNORE INTO `posts` (`id`, `board`, `title`, `eyebrow`, `portfolio_id`, `content`, `image`, `is_visible`, `admin_id`, `views`, `created_at`, `updated_at`) VALUES (2,'portfolio','THE SPACE KIDS','BRAND EXPERIENCE',2,'우주의 무한한 가능성을 탐험하고\n새로운 경험을 만드는 브랜드입니다.','/aseets/images/section-portfolio/portfolio-2.png',1,1,0,'2026-10-12 21:49:46','2026-10-12 22:48:32');
INSERT IGNORE INTO `posts` (`id`, `board`, `title`, `eyebrow`, `portfolio_id`, `content`, `image`, `is_visible`, `admin_id`, `views`, `created_at`, `updated_at`) VALUES (3,'portfolio','THE SPACE KIDS','BRAND EXPERIENCE',3,'우주의 무한한 가능성을 탐험하고\n새로운 경험을 만드는 브랜드입니다.','/aseets/images/section-portfolio/portfolio-3.png',1,1,0,'2026-10-12 21:49:46','2026-10-12 22:48:32');
INSERT IGNORE INTO `posts` (`id`, `board`, `title`, `eyebrow`, `portfolio_id`, `content`, `image`, `is_visible`, `admin_id`, `views`, `created_at`, `updated_at`) VALUES (4,'portfolio','안녕하세요?','BRAND EXPERIENCE',4,'우주의 무한한 가능성을 탐험하고\r\n새로운 경험을 만드는 브랜드입니다.','/aseets/images/section-portfolio/portfolio-4.png',1,1,0,'2026-10-12 21:49:46','2026-10-12 23:05:11');


/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

