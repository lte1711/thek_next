<?php
$root_id = (int)$_SESSION['user_id'];
?>

<style>
.org-wrap { display:flex; gap:20px; }
.org-tree { width:45%; border:1px solid #ccc; padding:10px; border-radius:10px; }
.org-panel { width:55%; border:1px solid #ccc; padding:10px; border-radius:10px; }

.node-row{ display:flex; align-items:center; gap:8px; margin:6px 0; }
.children{ margin-left:20px; }

.btn-role{
  background:#3b6bbf; color:#fff; border:0; border-radius:10px;
  padding:8px 14px; font-weight:800; cursor:pointer;
  min-width:120px;
}
.btn-new{
  background:#ef7f2d; color:#fff; border:0; border-radius:10px;
  padding:8px 12px; font-weight:900; cursor:pointer; text-align:left;
}
.btn-ref{
  background:#a9c8e9; color:#000; border:0; border-radius:10px;
  padding:8px 12px; font-weight:900; cursor:pointer;
  line-height:1.1;
}
.small{ color:#666; font-size:12px; }
input, select { width:100%; height:40px; margin-top:8px; padding:0 10px; }
button[type="submit"]{ width:100%; height:42px; margin-top:10px; cursor:pointer; }
</style>

<div class="org-wrap">
  <div class="org-tree">
      <b><?= t('title.org_chart','Org Chart') ?></b>
      <div class="small">role 버튼: 펼침 / new: 바로 하위 생성 / investor: referrer new(추천인 생성)</div>
      <div id="tree"></div>
  </div>

  <div class="org-panel">
      <b id="formTitle"><?= t('title.create_account','Create Account') ?></b>

      <form id="createForm">
          <input type="hidden" name="parent_id" id="parent_id" value="">
          <input type="hidden" id="mode" value="child"> <!-- child | referral -->
          <input type="hidden" id="investor_id" value="">

          <div><?= t('label.selected','Selected') ?>: <span id="parent_label"><?= t('common.none','None') ?></span></div>

          <!-- role은 child 생성 시 자동 세팅(표시용) -->
          <select name="role" id="roleSel">
              <option value="">ROLE</option>
              <option value="master">MASTER</option>
              <option value="agent">AGENT</option>
              <option value="investor">INVESTOR</option>
          </select>

          <input type="text" name="username" placeholder="아이디" required>
          <input type="password" name="password" placeholder="비밀번호" required>
          <input type="text" name="name" placeholder="이름">
          <input type="text" name="phone" placeholder="전화번호">

          <button type="submit"><?= t('btn.create','Create') ?></button>
      </form>
  </div>
</div>

<script>
const api = 'org_chart_api.php';

// 디버그 모드
const DEBUG = true;

function log(...args) {
  if (DEBUG) console.log('[조직도]', ...args);
}

function escapeHtml(s) {
  return String(s ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function nextRole(role){
  const map = { admin:'master', master:'agent', agent:'investor' };
  return map[(role||'').toLowerCase()] || '';
}

function renderNodes(list, container){
  log('renderNodes 호출:', list.length, '개 노드');
  
  list.forEach(u => {
    const row = document.createElement('div');
    row.className = 'node-row';

    // role 버튼(펼침 토글 + 선택)
    const roleBtn = document.createElement('button');
    roleBtn.type = 'button';
    roleBtn.className = 'btn-role';
    const displayName = (u.name && u.name.trim()) ? u.name.trim() : (u.username || '');
    const roleText = (u.role || '').toLowerCase();
    const idText = u.username || '';
    const noText = u.id || '';

    roleBtn.innerHTML = `
    <div style="font-size:13px; font-weight:900; line-height:1.2;">
        ${escapeHtml(displayName)}
    </div>
    <div style="font-size:11px; font-weight:600; opacity:0.95; line-height:1.2; margin-top:4px;">
        ID: ${escapeHtml(idText)} &nbsp;|&nbsp; 등급: ${escapeHtml(roleText)} &nbsp;|&nbsp; No: ${escapeHtml(String(noText))}
    </div>
    `;

    roleBtn.onclick = async () => {
      log('노드 클릭:', u.username);
      
      // 선택 표시
      document.getElementById('parent_label').innerText = `${u.username} (${u.role})`;

      // 펼침 토글
      if (parseInt(u.has_children) !== 1) {
        log('자식 노드 없음');
        return;
      }

      const next = row.nextElementSibling;
      if (next && next.classList.contains('children')) {
        log('자식 노드 닫기');
        next.remove();
        return;
      }

      log('자식 노드 로드 중...');
      const childBox = document.createElement('div');
      childBox.className = 'children';
      row.after(childBox);

      try {
        const response = await fetch(api + '?action=get_children&parent_id=' + u.id);
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        const children = await response.json();
        log('자식 노드 로드 완료:', children.length, '개');
        
        if (children.error) {
          childBox.innerHTML = `<div style="color:#d00; padding:10px;">${escapeHtml(children.error)}</div>`;
          return;
        }
        
        renderNodes(children, childBox);
      } catch(error) {
        log('자식 노드 로드 실패:', error);
        childBox.innerHTML = `<div style="color:#d00; padding:10px;">로드 실패: ${escapeHtml(error.message)}</div>`;
      }
    };

    row.appendChild(roleBtn);

    const role = (u.role || '').toLowerCase();

    // new 버튼 규칙
    if (role === 'investor') {
      // 추천인 생성
      const refBtn = document.createElement('button');
      refBtn.type = 'button';
      refBtn.className = 'btn-ref';
      refBtn.innerHTML = 'referrer<br>new';

      refBtn.onclick = (e) => {
        e.stopPropagation();
        log('추천인 생성 모드:', u.username);

        document.getElementById('mode').value = 'referral';
        document.getElementById('investor_id').value = u.id;

        document.getElementById('formTitle').innerText = '추천인(Referrer) 생성';
        document.getElementById('parent_label').innerText = `${u.username} (investor 추천인)`;

        // roleSel은 investor로 고정(추천인은 investor로 생성)
        document.getElementById('roleSel').value = 'investor';
        document.getElementById('roleSel').disabled = true;
      };

      row.appendChild(refBtn);

    } else {
      const nr = nextRole(role);
      if (nr) {
        const newBtn = document.createElement('button');
        newBtn.type = 'button';
        newBtn.className = 'btn-new';
        newBtn.textContent = 'new';

        newBtn.onclick = (e) => {
          e.stopPropagation();
          log('직계 하위 생성 모드:', u.username, '→', nr);

          document.getElementById('mode').value = 'child';
          document.getElementById('investor_id').value = '';
          document.getElementById('roleSel').disabled = false;

          document.getElementById('parent_id').value = u.id;
          document.getElementById('parent_label').innerText = `${u.username} (${u.role}) → ${nr} 생성`;
          document.getElementById('formTitle').innerText = '직계 하위 생성';

          // role 자동 선택
          document.getElementById('roleSel').value = nr;
        };

        row.appendChild(newBtn);
      }
    }

    container.appendChild(row);
  });
}

async function loadRoot(){
  log('조직도 로드 시작...');
  const tree = document.getElementById('tree');
  tree.innerHTML = '<div style="padding:10px; color:#666;">📡 로딩 중...</div>';
  
  try {
    log('API 호출:', api + '?action=get_root');
    const response = await fetch(api + '?action=get_root');
    
    log('응답 상태:', response.status, response.statusText);
    
    if (!response.ok) {
      const errorText = await response.text();
      log('에러 응답:', errorText);
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const contentType = response.headers.get('content-type');
    log('Content-Type:', contentType);
    
    const roots = await response.json();
    log('루트 노드 수신:', roots);
    
    tree.innerHTML = '';
    
    if (roots.error) {
      tree.innerHTML = `<div style="padding:10px; color:#d00;">
        ❌ 에러: ${escapeHtml(roots.error)}
      </div>`;
      return;
    }
    
    if (!roots || !Array.isArray(roots) || roots.length === 0) {
      tree.innerHTML = `<div style="padding:10px; color:#999;">
        ℹ️ 등록된 Admin 계정이 없습니다.<br>
        Admin 계정을 먼저 생성해주세요.
        <br><br>
        <strong>현재 DB 상태:</strong><br>
        - Admin 계정: 0개
      </div>`;
      return;
    }
    
    log('렌더링 시작:', roots.length, '개 루트 노드');
    renderNodes(roots, tree);
    log('렌더링 완료!');
    
  } catch(error) {
    console.error('❌ 조직도 로드 실패:', error);
    tree.innerHTML = `<div style="padding:10px; color:#d00; border:1px solid #d00; border-radius:5px;">
      <strong>❌ 조직도 로드 실패</strong><br>
      <div style="margin-top:8px; font-size:13px;">
        에러: ${escapeHtml(error.message)}
      </div>
      <div style="margin-top:8px; font-size:12px; color:#666;">
        💡 해결 방법:<br>
        1. F12 → Console 탭 확인<br>
        2. Network 탭에서 org_chart_api.php 응답 확인<br>
        3. GM 계정으로 로그인했는지 확인
      </div>
    </div>`;
  }
}

// submit
document.getElementById('createForm').addEventListener('submit', async function(e){
  e.preventDefault();
  log('폼 제출 시작');
  
  const fd = new FormData(this);

  const mode = document.getElementById('mode').value;
  if (mode === 'referral') {
    fd.append('action', 'create_referral');
    fd.append('investor_id', document.getElementById('investor_id').value);
    log('추천인 생성 모드');
  } else {
    fd.append('action', 'create_child');
    if (!fd.get('parent_id')) {
      alert("<?= t('js.msg.e01d1a2e34', '부모를 선택하세요') ?>");
      return;
    }
    log('직계 하위 생성 모드');
  }

  try {
    log('API 호출 중...');
    const res = await fetch(api, { method:'POST', body: fd });
    const data = await res.json();
    log('응답 수신:', data);

    if (data.success) {
      alert("<?= t('js.msg.f3bc85d753', '생성 성공!') ?>");
      this.reset();
      document.getElementById('roleSel').disabled = false;
      document.getElementById('parent_label').innerText = '없음';
      document.getElementById('formTitle').innerText = '계정 생성';
      document.getElementById('mode').value = 'child';
      document.getElementById('investor_id').value = '';
      await loadRoot();
    } else {
      alert(data.error || data.message || '실패');
      log('생성 실패:', data);
    }
  } catch(error) {
    console.error('폼 제출 실패:', error);
    alert('에러 발생: ' + error.message);
  }
});

log('초기화 완료, 조직도 로드 시작...');
loadRoot();
</script>
