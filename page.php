<?php
/**
 * 관리자가 만든 서브페이지 (/page/{slug} → .htaccess 가 page.php?slug= 로 연결).
 * 본문 HTML·CSS·JS 는 관리자 > 서브페이지 관리에서 작성 (CSS 는 site/head, JS 는 site/footer 가 붙인다).
 * 숨김 페이지는 로그인한 관리자에게만 보인다 (미리보기).
 */
require __DIR__ . '/app/bootstrap.php';

$page = subpage_find_slug((string) ($_GET['slug'] ?? ''));
if ($page === null || (!$page['is_visible'] && current_admin() === null)) {
    http_response_code(404);
    view('site/head', ['pageTitle' => '페이지를 찾을 수 없습니다', 'styles' => ['/css/custom-page.css']]);
    view('site/header', ['isHome' => false, 'headerTheme' => 'dark']);
?>
    <main class="custom-page custom-page--empty">
      <h1>페이지를 찾을 수 없습니다</h1>
      <p>주소가 잘못되었거나 비공개된 페이지입니다.</p>
      <a href="/">홈으로</a>
    </main>
<?php
    view('site/footer', ['isHome' => false]);
    exit;
}

track_visit();
subpage_current($page);

view('site/head', ['pageTitle' => $page['title'], 'styles' => ['/css/custom-page.css']]);
view('site/header', ['isHome' => false, 'headerTheme' => 'dark']);
?>
    <main class="custom-page">
<?php if (!$page['is_visible']): ?>
      <p class="custom-page__preview">숨김 상태 페이지입니다. 관리자에게만 보입니다.</p>
<?php endif; ?>
<?= $page['html'] ?>
    </main>
<?php view('site/footer', ['isHome' => false]); ?>
