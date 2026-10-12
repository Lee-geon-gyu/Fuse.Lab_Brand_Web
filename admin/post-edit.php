<?php
require __DIR__ . '/../app/bootstrap.php';

view('admin/head', [
    'pageTitle' => '게시글 작성',
    'styles' => ['/css/admin-board.css', '/css/admin-post-edit.css', '/css/admin-layout.css'],
    'scripts' => ['/js/custom-select.js', '/js/admin-post-edit.js'],
    'bodyData' => ['board-url' => '/admin/board.php'],
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
            </select>
            <img src="/aseets/icons/admin/board-category-arrow.svg" alt="" width="20" height="20" />
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
