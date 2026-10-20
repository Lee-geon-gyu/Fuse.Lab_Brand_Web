# 포트폴리오 HTML 미리보기

2026-10-20 카페24에서 가져온 관리자 콘텐츠를 반영했습니다.

메인 `../index.html`의 포트폴리오 섹션도 같은 날 카페24의 4개 슬라이드(SWEATIE·Rawdoc·EAT-GO·AFTERIMAGE)와 이미지·문구·순서·상세 링크를 맞췄습니다. 메인 배경 밝기는 기존 스타일을 유지합니다.

- `portfolio-sub.html`: 포트폴리오 목록. 분류 탭과 더보기 버튼이 작동합니다.
- `portfolio-sweatie.html`, `portfolio-oy.html`, `portfolio-kumu.html`, `portfolio-afterimage.html`, `portfolio-rawdoc.html`, `portfolio-eatgo.html`, `portfolio-modoo.html`, `portfolio-reflow.html`, `portfolio-dive.html`, `portfolio-jw.html`: 프로젝트별 상세 페이지.
- `portfolio-sub-detail.html`: 기존 링크를 유지하기 위한 쿠무 상세 페이지.

`portfolio-sub.html`을 브라우저에서 열면 목록에서 각 상세 페이지로 이동할 수 있습니다. 로컬 서버에서는 `http://localhost:8088/sub-pages/portfolio-sub.html`로 확인합니다.

이미지는 `../aseets/imgs/portfolio-sub/`의 기존 원본을 사용합니다. 잇고 대표 이미지는 운영 업로드와 동일한 파일이 기존 원본에 없어 `eatgo-hero-uploaded.png`로 보관했습니다.

쿠무를 제외한 9개 프로젝트의 원본 로고는 `../aseets/imgs/portfolio-sub/logos/`에 저장하고, `../css/portfolio-brand-logo.css`로 대표 이미지 중앙에 표시합니다. 배경에는 검정 `rgba(0, 0, 0, 0.2)`을 덮고 로고는 그 위에 밝게 표시합니다. 배경과 로고는 별도 파일이므로 크기를 조정해도 배경 원본이 바뀌지 않습니다. 출처는 로고 폴더의 `README.md`에 정리했습니다.

이 HTML은 위 날짜의 콘텐츠를 담은 파일입니다. 이후 관리자에서 수정한 내용은 PHP 화면과 DB에 반영되며, 이 HTML에는 자동으로 반영되지 않습니다. 관리자 로그인·챗봇 서버 연결은 포함하지 않고, 챗봇은 기본 안내만 제공합니다.
