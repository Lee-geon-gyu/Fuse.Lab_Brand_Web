# 포트폴리오 원본 로고

2026-10-20 각 프로젝트 사이트에서 가져온 로고입니다. 쿠무는 대표 배경 안에 로고가 포함돼 있어 추가하지 않았습니다.

| 프로젝트 | 파일 | 원본 |
| --- | --- | --- |
| SWEATIE | `sweatie.svg` | [사이트의 SVG 로고](https://r00neyj.github.io/Sweatie_Brand_Web/image/top-section/header/logo.svg) |
| CJ 올리브영 | `oy.png` | [사이트의 흰색 로고](https://lee-geon-gyu.github.io/OY_Redesign/images/header/OliveYoung-logo-white.png) |
| AFTERIMAGE | `afterimage.svg` | [사이트의 SVG 로고](https://tnfh23-web.github.io/afterimage/logo.svg) |
| Rawdoc | `rawdoc.png`, `rawdoc-icon.svg` | [소개 페이지](https://rawdoc.app/welcome), [아이콘](https://rawdoc.app/icons/icon.svg) |
| EAT-GO | `eatgo.png` | [사이트의 영문 로고](https://r00neyj.github.io/Eatgo_Process/images/sections/eatgo-logo-en.png) |
| MODOO | `modoo.png` | [사이트의 로고](https://tnfh23-web.github.io/react-modoo/assets/logo-DBIdfBxB.png) |
| REFLOW | `reflow.png` | [사이트](https://reflow.tnfh23.workers.dev/) |
| DIVE | `dive.png` | [사이트의 흰색 로고](https://lee-geon-gyu.github.io/DIVE_Web_React_Ts/assets/dive-logo-white-B7CKsulb.png) |
| JW중외제약 | `jw-white-hd.png`(현재 사용, 767×221), `jw-white.png`, `jw.png` | [고해상도 흰색 로고](https://lee-geon-gyu.github.io/JW_Redesign/Image_file/main-images/sec-2/sec-2-logo.png), [기존 흰색 로고](https://lee-geon-gyu.github.io/JW_Redesign/Image_file/header-imgs/header-logo.png), [컬러 로고](https://lee-geon-gyu.github.io/JW_Redesign/Image_file/header-imgs/header-logo-change.png) |

Rawdoc과 REFLOW는 별도 워드마크 이미지 대신 HTML 글자와 심벌을 사용하는 사이트입니다. 원본의 글자·서체·간격·심벌 구성을 브라우저에서 크게 렌더링해 투명 PNG로 저장했습니다. 다른 파일은 사이트의 원본 파일입니다.

상세 화면에서는 배경 이미지와 별도로 로고를 겹쳐 표시합니다. 쿠무를 제외한 대표 배경에는 검정 `rgba(0, 0, 0, 0.2)`을 덮습니다. 로고와 아래 화살표는 그 위에 표시하며, Rawdoc·REFLOW·EAT-GO는 CSS로 밝은 로고 색을 적용합니다. 로고 원본 파일의 색은 보존합니다.

PHP 연결은 `app/portfolios.php`의 `portfolio_brand_logo()`, 크기와 색상은 `css/portfolio-brand-logo.css`에서 조정합니다.
