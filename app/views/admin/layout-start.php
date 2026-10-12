<?php
/**
 * 관리자 좌측 메뉴 + 본문 영역 시작. 본문 뒤에 admin/layout-end 로 닫는다.
 * 페이지에서 require_admin() 을 먼저 호출한 상태를 전제로 한다.
 * @var string $active 'dashboard' | 'inquiries' | 'board' | 'portfolios' | 'chatbot'
 */
$admin = current_admin();
$menu = [
    'dashboard' => ['/admin/', '대시보드'],
    'inquiries' => ['/admin/inquiries.php', '견적문의'],
    'board' => ['/admin/board.php', '게시판'],
    'portfolios' => ['/admin/portfolios.php', '포트폴리오'],
    'chatbot' => ['/admin/chatbot.php', '챗봇 설정'],
];
?>
    <div class="admin-layout">
      <aside class="admin-nav">
        <a class="admin-nav__logo" href="/admin/" aria-label="관리자 홈">
          <img src="/aseets/img/logo-dark.svg" width="118" height="24" alt="fuse.lab" />
        </a>
        <nav aria-label="관리자 메뉴">
          <ul class="admin-nav__list">
<?php foreach ($menu as $key => [$href, $label]): ?>
            <li><a href="<?= $href ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>><?= $label ?></a></li>
<?php endforeach; ?>
          </ul>
        </nav>
        <div class="admin-nav__bottom">
          <span class="admin-nav__user"><?= e($admin['name']) ?> 님</span>
          <a href="/" target="_blank" rel="noopener">사이트 보기</a>
          <form method="post" action="/admin/logout.php">
            <?= csrf_field() ?>
            <button type="submit">로그아웃</button>
          </form>
        </div>
      </aside>
      <div class="admin-content">
