<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_name = $_SESSION['user_name'] ?: $_SESSION['user_email'];
$is_admin = ($_SESSION['user_role'] === 'admin');

$target_user_id = $_SESSION['user_id'];
$viewing_other = false;
$target_name = "";

if ($is_admin && isset($_GET['user_id'])) {
    $target_user_id = $_GET['user_id'];
    $viewing_other = true;
    
    $stmt = $pdo->prepare("SELECT fullname, email FROM users WHERE id = ?");
    $stmt->execute([$target_user_id]);
    $t_user = $stmt->fetch();
    $target_name = ($t_user['fullname'] ?: $t_user['email']) ?? "Unknown User";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>English Self-Talk - Rin</title>
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #10b981;
            --accent: #f59e0b;
            --bg: #f8fafc;
            --text: #1e293b;
            --card-bg: #ffffff;
            --sidebar-w: 260px;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-w);
            background: white;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 20;
        }
        .sidebar.collapsed { width: 0; min-width: 0; border-right: none; }

        .sidebar-header {
            padding: 20px;
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--primary);
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-close-sidebar { background: none; border: none; color: #94a3b8; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .btn-close-sidebar:hover { color: var(--primary); }

        .archive-list { padding: 10px; }
        .archive-year { font-weight: bold; margin: 15px 10px 5px; color: #64748b; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; }
        .archive-month { padding: 8px 15px; cursor: pointer; border-radius: 8px; font-size: 0.9rem; transition: background 0.2s; display: flex; align-items: center; }
        .archive-month:hover { background: #f1f5f9; }
        .archive-month.active { background: #eff6ff; color: var(--primary); font-weight: 600; }
        .archive-item { padding: 5px 15px 5px 30px; font-size: 0.8rem; color: #475569; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .archive-item:hover { color: var(--primary); }

        /* Main Content Styles */
        .main-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            position: relative;
        }

        .header-section {
            padding: 20px 40px;
            background: rgba(248, 250, 252, 0.8);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 1px solid #e2e8f0;
        }

        .header-top { display: flex; justify-content: space-between; align-items: center; }
        .header-top h2 { margin: 0; font-size: 1.5rem; }

        .controls { display: flex; gap: 10px; align-items: center; margin-top: 15px; }
        select { padding: 5px 10px; border-radius: 6px; border: 1px solid #cbd5e1; outline: none; }

        .progress-container { flex: 1; background: #e2e8f0; border-radius: 10px; height: 8px; overflow: hidden; }
        #progress-bar { width: 0%; height: 100%; background: var(--secondary); transition: width 0.5s ease; }

        .content-body {
            max-width: 600px;
            margin: 20px auto;
            padding: 0 20px 100px;
            width: 100%;
        }

        .card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.05);
            border: 1px solid #f1f5f9;
            border-left: 5px solid #cbd5e1;
            transition: all 0.3s;
        }
        .card:hover { transform: translateY(-2px); }
        .card.completed { border-left-color: var(--secondary); }
        
        @keyframes flash {
            0% { background: #fffbeb; }
            100% { background: white; }
        }
        .highlight-flash { animation: flash 2s ease-out; }

        .card-header { font-weight: bold; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .card-content { font-size: 1.1rem; line-height: 1.6; min-height: 60px; }
        .hidden { display: none; }

        .btn-group { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 20px; }
        button { padding: 12px; border: none; border-radius: 10px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s; }
        .btn-toggle { background: #f1f5f9; color: #475569; }
        .btn-toggle.active { background: var(--primary); color: white; }
        .btn-mark { margin-top: 15px; width: 100%; background: #f8fafc; border: 1px dashed #cbd5e1; color: #64748b; }
        .btn-mark.completed { background: var(--secondary); color: white; border-style: solid; }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 20px 0;
            margin-top: 20px;
        }
        .page-btn { background: white; border: 1px solid #e2e8f0; padding: 8px 15px; color: var(--text); }
        .page-btn[disabled] { opacity: 0.5; cursor: not-allowed; }
        .page-btn.active { background: var(--primary); color: white; border-color: var(--primary); }

        /* Sidebar Toggle Mobile & Desktop */
        .menu-toggle { display: block; border: none; background: white; width: 40px; height: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); color: var(--primary); font-size: 1.2rem; cursor: pointer; transition: all 0.2s; margin-right: 15px; }
        .menu-toggle:hover { background: #f8fafc; transform: scale(1.05); }
        .sidebar.collapsed + .main-container .menu-toggle { display: block; }
        
        @media (max-width: 768px) {
            .sidebar { position: fixed; left: -100%; height: 100%; z-index: 20; background: white; }
            .sidebar.open { left: 0; box-shadow: 10px 0 30px rgba(0,0,0,0.1); }
            .menu-toggle { display: block; background: none; color: var(--primary); font-size: 1.5rem; }
        }
    </style>
</head>
<body>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <span>Arsip Self-Talk</span>
            <button class="btn-close-sidebar" onclick="toggleSidebar()" title="Tutup Sidebar">✕</button>
        </div>
        <div class="archive-list" id="archive-list">
            <!-- Loaded via JS -->
            <p style="padding: 20px; color:#64748b; font-size: 0.8rem;">Memuat arsip...</p>
        </div>
    </div>

    <div class="main-container">
        <div class="header-section">
            <div class="top-header" style="display: flex; align-items: center; gap: 20px;">
                <div style="display: flex; align-items: center;">
                    <button class="menu-toggle" onclick="toggleSidebar()" title="Buka Sidebar">☰</button>
                    <h1 style="margin: 0; font-size: 1.5rem; color: var(--primary); font-weight: 800; white-space: nowrap;">English Self-Talk</h1>
                </div>
                <div style="display: flex; align-items: center; gap: 20px; flex: 1; justify-content: flex-end;">
                    <div class="user-info">
                        <?php if ($viewing_other): ?>
                            <div style="background: #fef3c7; padding: 6px 12px; border-radius: 8px; border: 1px solid #fbbf24; font-size: 0.85rem; color: #92400e; font-weight: 600;">
                                Viewing Account: <strong><?= htmlspecialchars($target_name) ?></strong>
                            </div>
                        <?php endif; ?>

                        <?php if ($is_admin): ?>
                            <a href="admin_users.php" class="btn-admin">Managemen User</a>
                        <?php endif; ?>
                        <div class="btn-profile"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
                        <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
                        <a href="logout.php" class="btn-logout">Logout</a>
                    </div>
                    <div class="header-nav">
                        <a href="manage.php<?= $viewing_other ? "?user_id=$target_user_id" : "" ?>" class="btn-manage">+ Tambah Kalimat</a>
                    </div>
                </div>
            </div>
            
            <div class="controls">
                <span>Tampilkan:</span>
                <select id="limit-select" onchange="changeLimit(this.value)">
                    <option value="10">10 Baris</option>
                    <option value="50">50 Baris</option>
                    <option value="100">100 Baris</option>
                    <option value="150">150 Baris</option>
                    <option value="all">Semua</option>
                </select>
                <div class="progress-container">
                    <div id="progress-bar"></div>
                </div>
                <span class="stats" id="progress-text">0/0</span>
            </div>
        </div>

        <div class="content-body">
            <div id="app"></div>
            <div id="pagination" class="pagination"></div>
        </div>
    </div>

    <script>
        const userRole = <?= json_encode($_SESSION['user_role']) ?>;
        const targetUserId = <?= json_encode($target_user_id) ?>;
        let data = [];
        let archive = {};
        let currentPage = 1;
        let currentLimit = 10;
        let totalItems = 0;

        async function init() {
            await fetchArchive();
            await fetchData();
        }

        async function fetchData(page = 1) {
            currentPage = page;
            const ts = new Date().getTime();
            const url = `api.php?action=get_entries&user_id=${targetUserId}&page=${currentPage}&limit=${currentLimit}&t=${ts}`;
            
            try {
                const response = await fetch(url);
                const result = await response.json();
                data = result.data;
                totalItems = result.total;
                
                render();
                renderPagination();
                updateProgress();
            } catch (err) {
                console.error("Fetch error:", err);
            }
        }

        async function fetchArchive() {
            try {
                const response = await fetch(`api.php?action=get_archive&user_id=${targetUserId}`);
                archive = await response.json();
                renderArchive();
            } catch (err) {
                console.error("Archive error:", err);
            }
        }

        function renderArchive() {
            const list = document.getElementById('archive-list');
            let html = '';
            
            for (const year in archive) {
                html += `<div class="archive-year">${year}</div>`;
                for (const month in archive[year]) {
                    html += `
                        <div class="archive-month" onclick="toggleMonth(this)">
                            <span>${month}</span>
                            <span style="margin-left:auto; font-size:0.7rem; opacity:0.5;">▼</span>
                        </div>
                        <div class="month-items hidden">
                            ${archive[year][month].map(item => `
                                <div class="archive-item" onclick="scrollToCard(${item.id}, ${item.index})">${item.title}</div>
                            `).join('')}
                        </div>
                    `;
                }
            }
            list.innerHTML = html;
        }

        function toggleMonth(el) {
            const items = el.nextElementSibling;
            items.classList.toggle('hidden');
            el.classList.toggle('active');
        }

        async function scrollToCard(id, index) {
            let targetPage = 1;
            if (currentLimit !== 'all') {
                targetPage = Math.floor(index / currentLimit) + 1;
            }

            if (currentPage !== targetPage) {
                await fetchData(targetPage);
            }

            setTimeout(() => {
                const el = document.getElementById(`card-${id}`);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.style.borderLeftColor = 'var(--accent)';
                    el.classList.add('highlight-flash');
                    setTimeout(() => {
                        el.style.borderLeftColor = '';
                        el.classList.remove('highlight-flash');
                    }, 2000);
                    
                    if (window.innerWidth <= 768) {
                        toggleSidebar();
                    }
                }
            }, 100);
        }

        function renderPagination() {
            const container = document.getElementById('pagination');
            if (currentLimit === 'all') {
                container.innerHTML = '';
                return;
            }

            const totalPages = Math.ceil(totalItems / currentLimit);
            let html = `
                <button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">Prev</button>
            `;

            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += `<span>...</span>`;
                }
            }

            html += `
                <button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">Next</button>
            `;
            container.innerHTML = html;
        }

        function changePage(p) {
            fetchData(p);
            document.querySelector('.main-container').scrollTo({ top: 0, behavior: 'smooth' });
        }

        function changeLimit(l) {
            currentLimit = l;
            fetchData(1);
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('open'); // Mobile behavior
            }
        }

        function updateProgress() {
            const count = data.filter(item => item.is_completed == 1).length;
            const percent = totalItems > 0 ? (count / data.length) * 100 : 0; // Local page progress
            document.getElementById('progress-bar').style.width = percent + '%';
            document.getElementById('progress-text').innerText = `${count}/${data.length} (Halaman ini)`;
        }

        async function toggleComplete(id) {
            const item = data.find(i => i.id == id);
            const newStatus = item.is_completed == 1 ? 0 : 1;
            
            try {
                const response = await fetch('api.php?action=toggle_complete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, status: newStatus })
                });
                if ((await response.json()).success) {
                    item.is_completed = newStatus;
                    render();
                    updateProgress();
                }
            } catch (err) { console.error(err); }
        }

        function render() {
            const app = document.getElementById('app');
            if (data.length === 0) {
                app.innerHTML = '<p style="text-align:center; color:#64748b; padding: 40px;">Belum ada data di halaman ini.</p>';
                return;
            }
            
            app.innerHTML = data.map((item, index) => {
                const isDone = item.is_completed == 1;
                const displayNo = (currentLimit === 'all') ? index + 1 : ((currentPage - 1) * currentLimit) + index + 1;
                const vocabHtml = item.vocab_id ? `
                    <span style="margin-left: 10px; font-weight: normal; font-size: 0.85rem; color: #64748b; border-left: 1px solid #e2e8f0; padding-left: 10px;">
                        ${item.vocab_id} | ${item.vocab_en} <span style="font-style: italic; opacity:0.7;">(${item.vocab_pron})</span>
                    </span>
                ` : '';

                return `
                <div class="card ${isDone ? 'completed' : ''}" id="card-${item.id}">
                    <div class="card-header">
                        <div style="display: flex; align-items: center; width: 100%;">
                            <span style="color: var(--primary);">No. ${displayNo}</span>
                            ${vocabHtml}
                            ${userRole === 'admin' && item.creator_name ? `<span style="margin-left:auto; font-size: 0.7rem; color: #94a3b8; background: #f8fafc; border: 1px solid #e2e8f0; padding: 2px 8px; border-radius: 4px;">By: ${item.creator_name}</span>` : ''}
                        </div>
                    </div>
                    <div style="padding: 0 15px;">
                        ${isDone ? '<span style="color:var(--secondary); font-size: 0.8rem; display: block; margin-top: 5px;">✔ Lancar</span>' : ''}
                    </div>
                    
                    <div class="card-content">
                        <div id="text-id-${item.id}">${item.text_id}</div>
                        <div id="text-en-${item.id}" class="hidden"><strong>${item.text_en}</strong></div>
                        <div id="text-pron-${item.id}" class="hidden"><em>${item.pronunciation}</em></div>
                        <div id="text-break-${item.id}" class="hidden" style="font-size: 0.9rem; background:#f8fafc; padding:10px; border-radius:8px; border-left:3px solid var(--accent);">${item.breakdown}</div>
                    </div>

                    <div class="btn-group">
                        <button class="btn-toggle" onclick="showToggle(${item.id}, 'en')" id="btn-en-${item.id}">English</button>
                        <button class="btn-toggle" onclick="showToggle(${item.id}, 'pron')" id="btn-pron-${item.id}">Cara Baca</button>
                        <button class="btn-toggle" onclick="showToggle(${item.id}, 'break')" id="btn-break-${item.id}">Pecahan</button>
                    </div>

                    <button class="btn-mark ${isDone ? 'completed' : ''}" onclick="toggleComplete(${item.id})">
                        ${isDone ? 'Selesai ✓' : 'Tandai Sudah Lancar'}
                    </button>
                </div>
            `}).join('');
        }

        function showToggle(id, type) {
            const idEl = document.getElementById(`text-id-${id}`);
            const enEl = document.getElementById(`text-en-${id}`);
            const pronEl = document.getElementById(`text-pron-${id}`);
            const breakEl = document.getElementById(`text-break-${id}`);
            const btn = document.getElementById(`btn-${type}-${id}`);
            const isAct = btn.classList.contains('active');

            // Reset
            document.querySelectorAll(`#card-${id} .btn-toggle`).forEach(b => b.classList.remove('active'));
            [idEl, enEl, pronEl, breakEl].forEach(el => el.classList.add('hidden'));

            if (!isAct) {
                btn.classList.add('active');
                if (type === 'en') enEl.classList.remove('hidden');
                else if (type === 'pron') { pronEl.classList.remove('hidden'); idEl.classList.remove('hidden'); }
                else if (type === 'break') breakEl.classList.remove('hidden');
            } else {
                idEl.classList.remove('hidden');
            }
        }

        init();
    </script>
</body>
</html>