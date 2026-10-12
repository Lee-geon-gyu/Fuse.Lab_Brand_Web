<?php
require __DIR__ . '/../app/bootstrap.php';
require_admin();

$portfolioOptions = portfolio_admin_list('');

view('admin/head', [
    'pageTitle' => '게시글 작성',
    'styles' => ['/css/admin-board.css', '/css/admin-post-edit.css', '/css/admin-layout.css'],
    'scripts' => ['/js/custom-select.js', '/js/admin-post-edit.js'],
    'bodyData' => [
        'board-url' => '/admin/board.php',
        'posts-api' => '/admin/posts-api.php',
        'csrf' => csrf_token(),
    ],
]);
view('admin/layout-start', ['active' => 'board']);
?>
    <main class="editor-shell">
      <form class="editor-card" id="editor-form" novalidate>
        <h1 id="editor-title">게시글 작성</h1>

        <div class="editor-row">
          <label class="editor-label" for="post-title">제목</label>
          <input class="editor-input" id="post-title" type="text" maxlength="120" placeholder="제목을 입력해 주세요" required />
        </div>

        <fieldset class="editor-row">
          <legend class="editor-label">노출 여부</legend>
          <div class="editor-choices">
            <label class="editor-choice"><input type="radio" name="visible" value="true" checked /> 노출</label>
            <label class="editor-choice"><input type="radio" name="visible" value="false" /> 숨김</label>
          </div>
        </fieldset>

        <div class="editor-row">
          <span class="editor-label" id="post-category-label">게시판</span>
          <div class="select-box" data-custom-select>
            <select id="post-category" aria-labelledby="post-category-label">
              <option value="notice">공지사항</option>
              <option value="event">이벤트</option>
              <option value="resources">자료실</option>
              <option value="portfolio">포트폴리오</option>
            </select>
            <img src="/aseets/icons/admin/board-category-arrow.svg" alt="" width="20" height="20" />
          </div>
        </div>

        <p class="editor-hint" data-board-only="portfolio" hidden>
          메인 포트폴리오 슬라이드로 노출됩니다. 제목은 큰 제목, 내용은 설명 문구(줄바꿈 유지)로 쓰이고 먼저 등록한 글이 앞에 나옵니다.
        </p>

        <div class="editor-row" data-board-only="portfolio" hidden>
          <label class="editor-label" for="post-eyebrow">상단 문구</label>
          <input class="editor-input" id="post-eyebrow" type="text" maxlength="60" placeholder="예: BRAND EXPERIENCE" />
        </div>

        <div class="editor-row" data-board-only="portfolio" hidden>
          <span class="editor-label" id="post-portfolio-label">연결 포트폴리오</span>
          <div class="editor-files">
            <div class="select-box" data-custom-select>
              <select id="post-portfolio" aria-labelledby="post-portfolio-label" aria-describedby="post-portfolio-hint">
                <option value="">연결 안 함</option>
<?php foreach ($portfolioOptions as $option): ?>
                <option value="<?= (int) $option['id'] ?>"><?= e($option['title']) ?><?= $option['is_visible'] ? '' : ' (숨김)' ?></option>
<?php endforeach; ?>
              </select>
              <img src="/aseets/icons/admin/board-category-arrow.svg" alt="" width="20" height="20" />
            </div>
            <span class="editor-hint" id="post-portfolio-hint">VIEW PROJECT 버튼이 이 포트폴리오 상세로 이동합니다. 연결 안 함이거나 대상이 숨김이면 다음 슬라이드로 넘어갑니다.</span>
          </div>
        </div>

        <div class="editor-row" data-board-only="portfolio" hidden>
          <span class="editor-label">배경 이미지</span>
          <div class="editor-files">
            <label class="file-button">
              <img src="/aseets/icons/admin/all-inbox.svg" alt="" width="24" height="24" />
              이미지 선택
              <input id="post-image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" />
            </label>
            <img class="image-preview" id="image-preview" alt="배경 이미지 미리보기" hidden />
            <span class="editor-hint">가로형 1920×1080 이상 권장, JPG·PNG·WEBP·GIF 10MB 이하</span>
          </div>
        </div>

        <div class="editor-row editor-row--stack">
          <label class="editor-label" for="post-content">내용</label>
          <textarea class="editor-input editor-textarea" id="post-content" placeholder="내용을 입력해 주세요" required></textarea>
        </div>

        <div class="editor-row">
          <span class="editor-label">파일첨부</span>
          <div class="editor-files">
            <label class="file-button">
              <img src="/aseets/icons/admin/all-inbox.svg" alt="" width="24" height="24" />
              파일 선택
              <input id="post-files" type="file" multiple />
            </label>
            <ul class="file-list" id="file-list"></ul>
          </div>
        </div>

        <div class="editor-actions">
          <button class="pill-button pill-button--danger" id="delete-post" type="button" hidden>삭제</button>
          <button class="pill-button pill-button--muted" id="cancel-post" type="button">취소</button>
          <button class="pill-button pill-button--muted" id="draft-post" type="button">임시저장</button>
          <button class="pill-button pill-button--primary" type="submit">저장</button>
        </div>
      </form>
    </main>

    <div class="toast" id="board-toast" role="status" aria-live="polite" hidden></div>
<?php
view('admin/layout-end');
view('admin/foot');
