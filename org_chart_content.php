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
      <b>조직도</b>
      <div class="small">role 버튼: 펼침 / new: 바로 하위 생성 / investor: referrer new(추천인 생성)</div>
      <div id="tree"></div>
  </div>

  <div class="org-panel">
      <b id="formTitle">계정 생성</b>

      <form id="createForm">
          <input type="hidden" name="parent_id" id="parent_id" value="">
          <input type="hidden" id="mode" value="child"> <!-- child | referral -->
          <input type="hidden" id="investor_id" value="">

          <div>선택: <span id="parent_label">없음</span></div>

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

          <button type="submit">생성</button>
      </form>
  </div>
</div>

<script>
const api = 'org_chart_api.php';
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
      // 선택 표시
      document.getElementById('parent_label').innerText = `${u.username} (${u.role})`;

      // 펼침 토글
      if (parseInt(u.has_children) !== 1) return;

      const next = row.nextElementSibling;
      if (next && next.classList.contains('children')) {
        next.remove();
        return;
      }

      const childBox = document.createElement('div');
      childBox.className = 'children';
      row.after(childBox);

      const children = await fetch(api + '?action=get_children&parent_id=' + u.id).then(r => r.json());
      renderNodes(children, childBox);
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
  const tree = document.getElementById('tree');
  tree.innerHTML = '';
  const roots = await fetch(api + '?action=get_root').then(r => r.json());
  renderNodes(roots, tree);
}

// submit
document.getElementById('createForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const fd = new FormData(this);

  const mode = document.getElementById('mode').value;
  if (mode === 'referral') {
    fd.append('action', 'create_referral');
    fd.append('investor_id', document.getElementById('investor_id').value);
  } else {
    fd.append('action', 'create_child');
    // parent_id는 new 버튼으로 선택된 값 사용
    // role은 서버에서 next role로 강제하지만, UI 표시용으로도 들어감
    if (!fd.get('parent_id')) {
      alert('상위 노드에서 new 버튼으로 생성 대상을 선택하세요.');
      return;
    }
  }

  const res = await fetch(api, { method:'POST', body: fd });
  const data = await res.json();

  if (data.success) {
    alert('생성 완료');
    this.reset();
    document.getElementById('roleSel').disabled = false;
    document.getElementById('parent_label').innerText = '없음';
    document.getElementById('formTitle').innerText = '계정 생성';
    document.getElementById('mode').value = 'child';
    document.getElementById('investor_id').value = '';
    await loadRoot();
  } else {
    alert(data.error || data.message || '실패');
  }
});

loadRoot();
</script>
