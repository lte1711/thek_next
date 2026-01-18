<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';

$page_title = '회원보기 (바이너리 트리)';

admin_render_header($page_title);
?>

<div class="card" style="margin-bottom:12px;">
  <div class="card-header" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; justify-content:space-between;">
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <strong>회원보기</strong>
      <span class="badge">회사(GM) → Admin → Master → Agent → Investor …</span>
    </div>

    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
      <label style="display:flex; gap:6px; align-items:center;">
        <span style="font-size:12px; opacity:.8;">깊이</span>
        <select id="depthSel" class="input" style="height:32px;">
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5" selected>5</option>
          <option value="6">6</option>
        </select>
      </label>

      <input id="searchBox" class="input" style="height:32px; width:220px;" placeholder="이름/아이디 검색" />
      <button id="btnSearch" class="btn btn-primary" style="height:32px;">찾기</button>
      <button id="btnReset" class="btn" style="height:32px;">초기화</button>

      <label style="display:flex; gap:6px; align-items:center; margin-left:6px;">
        <input id="toggleRef" type="checkbox" checked />
        <span style="font-size:12px; opacity:.85;">추천인 점선</span>
      </label>
    </div>
  </div>

  <div style="padding:10px 14px; font-size:12px; color:#555;">
    • 트리는 <b>위→아래</b>로 내려가며, 각 노드의 하위는 <b>바이너리(좌/우)</b> 형태로 2명까지만 펼칩니다. (추가 하위는 <b>+더보기</b>로 묶음)<br>
    • <b>찾기</b>: 검색된 회원을 루트로 <b>하위 트리만</b> 표시 (동명이인/동일 조건이 여러 명이면 선택)<br>
    • 마우스 휠: 확대/축소, 드래그: 이동, 노드 클릭: 중앙정렬 + 우측 상세
  </div>
</div>

<div class="card">
  <div class="card-content" style="padding:0;">
    <div style="display:flex; width:100%; height:720px;">
      <div id="treeWrap" style="flex:1; position:relative; overflow:hidden; border-right:1px solid #e5e7eb;">
        <div id="loading" style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:14px; color:#666; background:rgba(255,255,255,.7); z-index:3;">
          데이터 불러오는 중...
        </div>
        <svg id="treeSvg" width="100%" height="100%"></svg>
      </div>

      <div id="infoPanel" style="width:320px; padding:14px; overflow:auto;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
          <strong>회원 상세</strong>
          <a id="panelLink" href="#" class="btn" style="height:28px; padding:0 10px; display:none;">회원관리</a>
        </div>
        <div id="panelEmpty" style="margin-top:10px; font-size:12px; color:#6b7280;">
          노드를 클릭하면 상세가 표시됩니다.
        </div>
        <div id="panelBody" style="display:none; margin-top:12px; font-size:13px;">
          <div style="margin-bottom:10px;">
            <div id="pName" style="font-weight:700; font-size:15px; color:#111827;"></div>
            <div id="pRole" style="margin-top:2px; font-size:12px; color:#6b7280;"></div>
          </div>

          <div style="display:grid; grid-template-columns:110px 1fr; row-gap:8px; column-gap:10px; align-items:center;">
            <div style="color:#6b7280; font-size:12px;">ID</div><div id="pId"></div>
            <div style="color:#6b7280; font-size:12px;">아이디</div><div id="pUser"></div>
            <div style="color:#6b7280; font-size:12px;">이메일</div><div id="pEmail"></div>
            <div style="color:#6b7280; font-size:12px;">전화</div><div id="pPhone"></div>
            <div style="color:#6b7280; font-size:12px;">상위(sponsor)</div><div id="pSponsor"></div>
            <div style="color:#6b7280; font-size:12px;">추천인(ref)</div><div id="pRef"></div>
            <div style="color:#6b7280; font-size:12px;">하위(표시)</div><div id="pDown"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/d3@7/dist/d3.min.js"></script>
<script>
(() => {
  const svg = d3.select("#treeSvg");
  const wrap = document.getElementById("treeWrap");
  const loading = document.getElementById("loading");

  const depthSel = document.getElementById("depthSel");
  const searchBox = document.getElementById("searchBox");
  const btnSearch = document.getElementById("btnSearch");
  const btnReset = document.getElementById("btnReset");
  const toggleRef = document.getElementById("toggleRef");

  // 우측 패널
  const panelLink = document.getElementById("panelLink");
  const panelEmpty = document.getElementById("panelEmpty");
  const panelBody = document.getElementById("panelBody");
  const pName = document.getElementById("pName");
  const pRole = document.getElementById("pRole");
  const pId = document.getElementById("pId");
  const pUser = document.getElementById("pUser");
  const pEmail = document.getElementById("pEmail");
  const pPhone = document.getElementById("pPhone");
  const pSponsor = document.getElementById("pSponsor");
  const pRef = document.getElementById("pRef");
  const pDown = document.getElementById("pDown");

  let rootData = null;
  let currentTransform = d3.zoomIdentity;
  let selectedNodeId = null;
  let showReferrer = (localStorage.getItem('tree_show_referrer') !== '0');
  if (toggleRef) toggleRef.checked = showReferrer;

  const g = svg.append("g");

  const zoom = d3.zoom()
    .scaleExtent([0.2, 2.0])
    .on("zoom", (event) => {
      currentTransform = event.transform;
      g.attr("transform", currentTransform);
    });

  svg.call(zoom);

  function roleColor(role) {
    // 색 지정은 최소화. 대비 위주.
    switch ((role||"").toLowerCase()) {
      case "company": return "#111827";
      case "admin": return "#1f2937";
      case "master": return "#374151";
      case "agent": return "#4b5563";
      case "investor": return "#6b7280";
      case "more": return "#0f766e";
      default: return "#334155";
    }
  }

  function fetchData() {
    loading.style.display = "flex";
    loading.textContent = "데이터 불러오는 중...";
    const depth = depthSel.value || "5";
    return fetch(`member_tree_data.php?depth=${encodeURIComponent(depth)}`, { cache: "no-store" })
      .then(r => r.json())
      .then(j => {
        rootData = j;
        selectedNodeId = null;
        setPanelEmpty();
        render();
      })
      .catch(err => {
        console.error(err);
        loading.textContent = "데이터 로딩 실패 (콘솔 확인)";
      })
      .finally(() => {
        setTimeout(()=> loading.style.display = "none", 150);
      });
  }

  async function fetchSubtree(query) {
    const q = (query || '').trim();
    if (!q) return;
    loading.style.display = 'flex';
    loading.textContent = '하위 트리 불러오는 중...';
    const depth = depthSel.value || '5';
    try {
      const url = `member_tree_data.php?action=subtree&depth=${encodeURIComponent(depth)}&q=${encodeURIComponent(q)}`;
      const j = await fetch(url, { cache: 'no-store' }).then(r => r.json());

      // 동명이인/복수 매칭이면 선택 UI 띄움
      if (j && j.status === 'multiple' && Array.isArray(j.matches)) {
        openPicker(j.q || q, j.matches);
        return;
      }

      if (!j || !j.found || !j.root) {
        alert('검색 결과가 없습니다.');
        return;
      }

      rootData = j.root;
      selectedNodeId = null;
      setPanelEmpty(`하위 트리 보기: ${j.root.name || ''}`);
      render();
    } catch (e) {
      console.error(e);
      alert('하위 트리 로딩 실패 (콘솔 확인)');
    } finally {
      setTimeout(()=> loading.style.display = 'none', 150);
    }
  }

  // --- 검색 결과 선택 모달 ---
  function openPicker(keyword, matches) {
    closePicker();

    const overlay = document.createElement('div');
    overlay.id = 'memberPickOverlay';
    overlay.style.cssText = 'position:fixed; inset:0; background:rgba(0,0,0,.35); display:flex; align-items:center; justify-content:center; z-index:9999;';
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) closePicker();
    });

    const box = document.createElement('div');
    box.style.cssText = 'width:min(680px, calc(100vw - 24px)); max-height:min(560px, calc(100vh - 24px)); background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,.25); display:flex; flex-direction:column;';

    const head = document.createElement('div');
    head.style.cssText = 'padding:14px 16px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; gap:10px;';
    head.innerHTML = `<div><div style="font-weight:800; color:#111827;">동일 검색 결과가 여러 명입니다</div><div style="margin-top:2px; font-size:12px; color:#6b7280;">"${escapeHtml(keyword)}" 에 해당하는 회원을 선택하세요.</div></div>`;
    const closeBtn = document.createElement('button');
    closeBtn.className = 'btn';
    closeBtn.style.cssText = 'height:30px; padding:0 10px;';
    closeBtn.textContent = '닫기';
    closeBtn.addEventListener('click', closePicker);
    head.appendChild(closeBtn);

    const body = document.createElement('div');
    body.style.cssText = 'padding:10px 16px; overflow:auto;';

    const table = document.createElement('table');
    table.style.cssText = 'width:100%; border-collapse:collapse; font-size:13px;';
    table.innerHTML = `
      <thead>
        <tr>
          <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280;">ID</th>
          <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280;">이름</th>
          <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280;">username</th>
          <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280;">role</th>
          <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280;">email</th>
          <th style="text-align:right; padding:8px; border-bottom:1px solid #e5e7eb;"></th>
        </tr>
      </thead>
      <tbody></tbody>
    `;
    const tbody = table.querySelector('tbody');
    for (const m of matches) {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td style="padding:10px 8px; border-bottom:1px solid #f1f5f9;">${escapeHtml(String(m.id ?? ''))}</td>
        <td style="padding:10px 8px; border-bottom:1px solid #f1f5f9; font-weight:700; color:#111827;">${escapeHtml(String(m.name ?? ''))}</td>
        <td style="padding:10px 8px; border-bottom:1px solid #f1f5f9;">${escapeHtml(String(m.username ?? ''))}</td>
        <td style="padding:10px 8px; border-bottom:1px solid #f1f5f9;">${escapeHtml(String(m.role ?? ''))}</td>
        <td style="padding:10px 8px; border-bottom:1px solid #f1f5f9; color:#475569;">${escapeHtml(String(m.email ?? ''))}</td>
        <td style="padding:10px 8px; border-bottom:1px solid #f1f5f9; text-align:right;">
          <button class="btn btn-primary" style="height:30px; padding:0 10px;" data-id="${escapeHtml(String(m.id ?? ''))}">선택</button>
        </td>
      `;
      tr.querySelector('button')?.addEventListener('click', async () => {
        closePicker();
        await fetchSubtree(String(m.id));
      });
      tbody.appendChild(tr);
    }

    body.appendChild(table);

    const foot = document.createElement('div');
    foot.style.cssText = 'padding:12px 16px; border-top:1px solid #e5e7eb; font-size:12px; color:#6b7280;';
    foot.textContent = `총 ${matches.length}명 검색됨 · 선택하면 해당 회원을 기준으로 하위 트리만 표시됩니다.`;

    box.appendChild(head);
    box.appendChild(body);
    box.appendChild(foot);
    overlay.appendChild(box);
    document.body.appendChild(overlay);
  }

  function closePicker() {
    const el = document.getElementById('memberPickOverlay');
    if (el && el.parentNode) el.parentNode.removeChild(el);
  }

  function escapeHtml(s) {
    return String(s)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  
  function formatRel(id, name) {
    if (id === null || typeof id === 'undefined' || String(id).trim() === '') return '';
    const n = (name || '').trim();
    return n ? `${id} (${n})` : String(id);
  }

function setPanelEmpty(msg) {
    panelBody.style.display = 'none';
    panelEmpty.style.display = 'block';
    panelEmpty.textContent = msg || '노드를 클릭하면 상세가 표시됩니다.';
    panelLink.style.display = 'none';
  }

  function setPanel(d) {
    if (!d || !d.data) {
      setPanelEmpty();
      return;
    }
    const role = (d.data.role || '').toLowerCase();
    if (role === 'group' || role === 'more') {
      setPanelEmpty();
      return;
    }

    const m = d.data.meta || {};
    panelEmpty.style.display = 'none';
    panelBody.style.display = 'block';

    pName.textContent = d.data.name || '';
    pRole.textContent = `role: ${d.data.role || ''}`;
    pId.textContent = (typeof d.data.id !== 'undefined') ? String(d.data.id) : '';
    pUser.textContent = m.username || '';
    pEmail.textContent = m.email || '';
    pPhone.textContent = m.phone || '';
    pSponsor.textContent = formatRel(m.sponsor_id, m.sponsor_name);
    pRef.textContent = formatRel(m.referrer_id, m.referrer_name);

    // 하위 수(현재 화면 기준)
    const desc = d.descendants ? d.descendants() : [];
    const realDesc = desc.filter(x => {
      const r = (x.data && x.data.role) ? String(x.data.role).toLowerCase() : '';
      return r !== 'group' && r !== 'more';
    });
    const totalDown = Math.max(0, realDesc.length - 1);
    const directKids = (d.children || []).filter(x => {
      const r = (x.data && x.data.role) ? String(x.data.role).toLowerCase() : '';
      return r !== 'group';
    }).length;
    pDown.textContent = `${directKids} (전체 ${totalDown})`;

    const link = m.link || (typeof d.data.id !== 'undefined' ? `users.php?id=${encodeURIComponent(d.data.id)}` : '#');
    panelLink.href = link;
    panelLink.style.display = link && link !== '#' ? 'inline-flex' : 'none';
  }

  // rootData 트리에서 특정 id 노드를 찾아 반환
  function findNode(obj, predicate) {
    if (!obj) return null;
    if (predicate(obj)) return obj;
    const kids = obj.children || [];
    for (const k of kids) {
      const f = findNode(k, predicate);
      if (f) return f;
    }
    return null;
  }

  // 배열(유저 노드들)을 균형 이진 트리 형태로 묶어주는 헬퍼
  // - 화면에서 'group' 노드는 거의 보이지 않게 렌더링
  function buildBinary(items, prefix) {
    const n = items.length;
    if (n <= 0) return null;
    if (n === 1) return items[0];
    if (n === 2) {
      return {
        id: `group_${prefix}_${items[0].id}_${items[1].id}`,
        name: '',
        role: 'group',
        children: [items[0], items[1]]
      };
    }
    const mid = Math.floor(n/2);
    const left = buildBinary(items.slice(0, mid), prefix + 'L');
    const right = buildBinary(items.slice(mid), prefix + 'R');
    return {
      id: `group_${prefix}_${n}_${Date.now()}`,
      name: '',
      role: 'group',
      children: [left, right].filter(Boolean)
    };
  }

  async function loadMore(moreNodeData) {
    const meta = moreNodeData && moreNodeData.meta ? moreNodeData.meta : {};
    const parentId = meta.parent_id;
    if (!parentId) return;

    // 클릭한 +더보기 노드를 rootData에서 찾아서 그 자리에서 확장
    const target = findNode(rootData, (n) => n.id === moreNodeData.id);
    if (!target) return;

    const offset = Number(meta.offset || 2);
    const remaining = Number(meta.remaining || 0);
    const limit = Math.min(10, Math.max(2, remaining));

    loading.style.display = 'flex';
    loading.textContent = '하위 회원 불러오는 중...';
    try {
      const url = `member_tree_data.php?action=children&parent_id=${encodeURIComponent(parentId)}&offset=${encodeURIComponent(offset)}&limit=${encodeURIComponent(limit)}`;
      const j = await fetch(url, { cache: 'no-store' }).then(r => r.json());
      const items = (j.items || []).map(x => ({...x, children: x.children || []}));
      const total = Number(j.total || 0);

      // 지금까지 보여준(기존 2명 + 이전에 더보기로 확장된 수) 계산
      const shownSoFar = offset + items.length;
      const leftRemain = Math.max(0, total - shownSoFar);

      // 더보기 노드 자체를 '컨테이너'로 사용: (1)이번에 로드한 유저들(이진 묶음) (2)남은 더보기 노드
      const subtree = buildBinary(items, `p${parentId}_o${offset}`);
      const children = [];
      if (subtree) children.push(subtree);
      if (leftRemain > 0) {
        children.push({
          id: `more_${parentId}_${shownSoFar}`,
          name: `+${leftRemain}명 더보기`,
          role: 'more',
          meta: {
            link: `users.php?sponsor_id=${parentId}`,
            parent_id: parentId,
            offset: shownSoFar,
            remaining: leftRemain
          },
          children: []
        });
      }

      // target 업데이트
      target.name = leftRemain > 0 ? `+${leftRemain}명 더보기` : '더보기';
      target.role = 'more';
      target.meta = {
        ...(meta||{}),
        parent_id: parentId,
        offset: shownSoFar,
        remaining: leftRemain,
        link: `users.php?sponsor_id=${parentId}`
      };
      target.children = children;

      render();
    } catch (e) {
      console.error(e);
      alert('하위 로딩 실패 (콘솔 확인)');
    } finally {
      setTimeout(()=> loading.style.display = 'none', 150);
    }
  }

  function render() {
    if (!rootData) return;

    const w = wrap.clientWidth;
    const h = wrap.clientHeight;

    svg.attr("viewBox", [0, 0, w, h]);

    g.selectAll("*").remove();

    const root = d3.hierarchy(rootData);
    // Top-down (위→아래) 트리
    // d.x: 가로 위치, d.y: 세로(depth)
    const tree = d3.tree()
      .nodeSize([170, 70]); // [x, y]

    tree(root);

    // 가운데 정렬을 위해 x 범위 계산
    let x0 = Infinity, x1 = -Infinity;
    root.each(d => { if (d.x < x0) x0 = d.x; if (d.x > x1) x1 = d.x; });

    const centerX = (x0 + x1) / 2;
    const startY = 60;

    // 기본 transform 초기화(루트를 화면 상단 중앙)
    const initial = d3.zoomIdentity.translate(w/2, startY).scale(0.85).translate(-centerX, 0);
    svg.transition().duration(350).call(zoom.transform, initial);

    // 기본 스폰서(트리) 링크
    g.append("g")
      .attr("fill", "none")
      .attr("stroke", "#cbd5e1")
      .attr("stroke-width", 2)
      .selectAll("path")
      .data(root.links())
      .join("path")
      .attr("d", d3.linkVertical()
        .x(d => d.x)
        .y(d => d.y)
      );

    // 추천인(referrer) 점선 링크 (현재 화면에 존재하는 노드끼리만)
    if (showReferrer) {
      const pos = new Map();
      root.each(d => {
        const key = d.data && typeof d.data.id !== 'undefined' ? String(d.data.id) : '';
        if (key) pos.set(key, {x:d.x, y:d.y});
      });
      const refLinks = [];
      root.each(d => {
        const m = d.data && d.data.meta ? d.data.meta : null;
        const rid = m && m.referrer_id ? String(m.referrer_id) : '';
        const sid = d.data && typeof d.data.id !== 'undefined' ? String(d.data.id) : '';
        if (!rid || !sid) return;
        if (!pos.has(rid) || !pos.has(sid)) return;
        if (rid === sid) return;
        refLinks.push({ from: sid, to: rid });
      });

      g.append('g')
        .attr('fill','none')
        .attr('stroke','#94a3b8')
        .attr('stroke-width',1.6)
        .attr('stroke-dasharray','5,4')
        .selectAll('path')
        .data(refLinks)
        .join('path')
        .attr('d', (l)=>{
          const a = pos.get(l.from);
          const b = pos.get(l.to);
          if (!a || !b) return '';
          const my = (a.y + b.y)/2 - 40;
          return `M${a.x},${a.y} C${a.x},${my} ${b.x},${my} ${b.x},${b.y}`;
        });
    }

    // 노드 그룹
    const node = g.append("g")
      .selectAll("g")
      .data(root.descendants())
      .join("g")
      .attr("transform", d => `translate(${d.x},${d.y})`)
      .style("cursor", "pointer")
      .on("click", async (_, d) => {
        const role = (d.data && d.data.role) ? String(d.data.role).toLowerCase() : '';
        if (role === 'more') {
          await loadMore(d.data);
          return;
        }
        selectedNodeId = (typeof d.data.id !== 'undefined') ? String(d.data.id) : null;
        setPanel(d);
        centerOn(d);
        applySelectionStyles();
      });

    node.append("circle")
      .attr("r", d => {
        const r = (d.data && d.data.role) ? String(d.data.role).toLowerCase() : '';
        if (r === 'group') return 5;
        return 18;
      })
      .attr("fill", d => {
        const r = (d.data && d.data.role) ? String(d.data.role).toLowerCase() : '';
        if (r === 'group') return '#cbd5e1';
        return roleColor(d.data.role);
      })
      .attr("stroke", d => {
        const r = (d.data && d.data.role) ? String(d.data.role).toLowerCase() : '';
        const id = (typeof d.data.id !== 'undefined') ? String(d.data.id) : '';
        if (selectedNodeId && id === selectedNodeId) return '#f59e0b'; // amber
        return r === 'group' ? '#e2e8f0' : '#e5e7eb';
      })
      .attr("stroke-width", d => {
        const r = (d.data && d.data.role) ? String(d.data.role).toLowerCase() : '';
        const id = (typeof d.data.id !== 'undefined') ? String(d.data.id) : '';
        if (selectedNodeId && id === selectedNodeId) return 4;
        return r === 'group' ? 1 : 2;
      });

    node.append("text")
      .attr("dy", 34)
      .attr("text-anchor", "middle")
      .attr("font-size", 12)
      .attr("fill", "#111827")
      .text(d => {
        const role = (d.data && d.data.role) ? String(d.data.role).toLowerCase() : '';
        if (role === 'group') return '';
        const nm = d.data.name || "";
        const r0 = d.data.role ? `(${d.data.role})` : "";
        return `${nm} ${r0}`;
      });

    // hover tooltip (간단)
    node.append("title").text(d => {
      const m = d.data.meta || {};
      const parts = [];
      if (d.data.role) parts.push(`role: ${d.data.role}`);
      if (typeof d.data.id !== "undefined") parts.push(`id: ${d.data.id}`);
      if (m.username) parts.push(`username: ${m.username}`);
      if (m.email) parts.push(`email: ${m.email}`);
      if (m.referrer_id) parts.push(`referrer_id: ${m.referrer_id}`);
      if (m.link) parts.push(`link: ${m.link}`);
      return parts.join("\\n");
    });

    // 최초 렌더 후 선택 강조 반영
    applySelectionStyles();
  }

  function applySelectionStyles() {
    g.selectAll("circle")
      .attr("stroke", d => {
        // d is hierarchy node
        const r = (d.data && d.data.role) ? String(d.data.role).toLowerCase() : '';
        const id = (typeof d.data.id !== 'undefined') ? String(d.data.id) : '';
        if (selectedNodeId && id === selectedNodeId) return '#f59e0b';
        return r === 'group' ? '#e2e8f0' : '#e5e7eb';
      })
      .attr("stroke-width", d => {
        const r = (d.data && d.data.role) ? String(d.data.role).toLowerCase() : '';
        const id = (typeof d.data.id !== 'undefined') ? String(d.data.id) : '';
        if (selectedNodeId && id === selectedNodeId) return 4;
        return r === 'group' ? 1 : 2;
      });
  }

  function centerOn(d) {
    const w = wrap.clientWidth;
    const h = wrap.clientHeight;
    const scale = currentTransform.k;

    // top-down: x=가로, y=세로
    const tx = (w/2) - (d.x * scale);
    const ty = (h/2) - (d.y * scale);

    const t = d3.zoomIdentity.translate(tx, ty).scale(scale);
    svg.transition().duration(450).call(zoom.transform, t);
  }

  function findAndCenter(query) {
    query = (query || "").trim().toLowerCase();
    if (!query) return;

    // walk hierarchy and find first matching
    const root = d3.hierarchy(rootData);
    let found = null;
    root.each(d => {
      if (found) return;
      const id = (""+d.data.id).toLowerCase();
      const nm = (d.data.name||"").toLowerCase();
      if (id === query || nm.includes(query)) found = d;
    });

    if (!found) {
      alert("검색 결과가 없습니다.");
      return;
    }
    // re-render build again to get positions
    const w = wrap.clientWidth;
    const h = wrap.clientHeight;
    const tree = d3.tree().nodeSize([170,70]);
    tree(root);

    // current zoom scale 유지
    const scale = currentTransform.k || 0.8;
    const tx2 = (w/2) - (found.x * scale);
    const ty2 = (h/2) - (found.y * scale);
    const t = d3.zoomIdentity.translate(tx2, ty2).scale(scale);
    svg.transition().duration(450).call(zoom.transform, t);
  }

  depthSel.addEventListener("change", () => fetchData());
  btnSearch.addEventListener("click", () => fetchSubtree(searchBox.value));
  searchBox.addEventListener("keydown", (e) => {
    if (e.key !== "Enter") return;
    fetchSubtree(searchBox.value);
  });
  btnReset.addEventListener("click", () => {
    searchBox.value = "";
    fetchData();
  });
  if (toggleRef) {
    toggleRef.addEventListener('change', () => {
      showReferrer = !!toggleRef.checked;
      localStorage.setItem('tree_show_referrer', showReferrer ? '1' : '0');
      render();
    });
  }

  fetchData();
})();
</script>

<?php admin_render_footer(); ?>
