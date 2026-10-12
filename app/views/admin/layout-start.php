<?php
/**
 * 관리자 좌측 메뉴 + 본문 영역 시작. 본문 뒤에 admin/layout-end 로 닫는다.
 * @var string $active 'dashboard' | 'board' | 'chatbot'
 */
$menu = [
    'dashboard' => ['/admin/', '대시보드'],
    'board' => ['/admin/board.php', '게시판'],
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
          <a href="/" target="_blank" rel="noopener">사이트 보기</a>
          <a href="/admin/logout.php">로그아웃</a>
        </div>
      </aside>
      <div class="admin-content">
