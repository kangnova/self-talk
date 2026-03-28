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
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header-section {
            width: 100%;
            max-width: 500px;
            text-align: center;
            position: sticky;
            top: 0;
            background: var(--bg);
            padding: 10px 0;
            z-index: 10;
        }

        h2 { color: var(--primary); margin: 0; }
        
        /* Progress Tracker Styles */
        .progress-container {
            width: 100%;
            background: #e2e8f0;
            border-radius: 10px;
            height: 12px;
            margin: 15px 0;
            overflow: hidden;
        }

        #progress-bar {
            width: 0%;
            height: 100%;
            background: var(--secondary);
            transition: width 0.5s ease;
        }

        .stats { font-size: 0.85rem; font-weight: bold; color: var(--secondary); }

        .container { width: 100%; max-width: 500px; padding-bottom: 50px; }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            border-left: 5px solid #cbd5e1;
            transition: border-color 0.3s;
        }

        .card.completed { border-left-color: var(--secondary); }

        .card-header {
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }

        .content-area {
            min-height: 70px;
            margin: 15px 0;
            font-size: 1.05rem;
            line-height: 1.6;
        }

        .btn-group {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
            margin-bottom: 10px;
        }

        button {
            padding: 10px 5px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
        }

        button.active { background: var(--primary); color: white; }

        .btn-complete {
            width: 100%;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            color: #64748b;
            margin-top: 10px;
        }

        .btn-complete.active {
            background: var(--secondary);
            color: white;
            border-style: solid;
        }

        .hidden { display: none; }
        
        .explanation {
            font-size: 0.85rem;
            color: #475569;
            background: #f1f5f9;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
            border-left: 3px solid var(--accent);
        }

        .word-breakdown strong { color: var(--primary); }
    </style>
</head>
<body>

    <div class="header-section">
        <h2>English Self-Talk</h2>
        <div style="margin-top: 5px;">
            <a href="manage.php" style="font-size: 0.75rem; color: var(--primary); text-decoration: none; font-weight: bold; border: 1px solid var(--primary); padding: 2px 8px; border-radius: 20px;">Manage Data</a>
        </div>
        <div class="stats" style="margin-top: 10px;">Progress: <span id="progress-text">0/10</span></div>
        <div class="progress-container">
            <div id="progress-bar"></div>
        </div>
    </div>

    <div class="container">
        <div id="app"></div>
    </div>

    <script>
        let data = [];
        let vocabs = [];
        let completedItems = [];

        async function init() {
            try {
                const ts = new Date().getTime();
                // Fetch sentences
                const resEntries = await fetch(`api.php?action=get_entries&t=${ts}`);
                data = await resEntries.json();
                
                // Fetch vocabs
                const resVocabs = await fetch(`api.php?action=get_vocabs&t=${ts}`);
                vocabs = await resVocabs.json();

                render();
                updateProgress();
            } catch (err) {
                console.error("Failed to fetch data:", err);
            }
        }



        function updateProgress() {
            const total = data.length;
            const count = data.filter(item => item.is_completed == 1).length;
            const percent = total > 0 ? (count / total) * 100 : 0;
            
            document.getElementById('progress-bar').style.width = percent + '%';
            document.getElementById('progress-text').innerText = `${count}/${total}`;
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
                const result = await response.json();
                if (result.success) {
                    item.is_completed = newStatus;
                    render();
                    updateProgress();
                }
            } catch (err) {
                console.error("Failed to update status:", err);
            }
        }

        function render() {
            const app = document.getElementById('app');
            if (data.length === 0) {
                app.innerHTML = '<p style="text-align:center; color:#64748b;">Belum ada data. Silakan tambah melalui menu Manage.</p>';
                return;
            }
            app.innerHTML = data.map((item, index) => {
                const isDone = item.is_completed == 1;
                const no = index + 1;
                const vocabHtml = item.vocab_id ? `
                    <span style="margin-left: 10px; font-weight: normal; font-size: 0.85rem; color: #475569; border-left: 1px solid #cbd5e1; padding-left: 10px;">
                        ${item.vocab_id} | ${item.vocab_en} <span style="color: var(--secondary); font-style: italic;">(${item.vocab_pron})</span>
                    </span>
                ` : '';

                return `
                <div class="card ${isDone ? 'completed' : ''}" id="card-${item.id}">
                    <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center;">
                            <strong>No. ${no}</strong>
                            ${vocabHtml}
                        </div>
                        ${isDone ? '<span style="color:var(--secondary); font-size: 0.8rem;">✔ Lancar</span>' : ''}
                    </div>
                    
                    <div class="content-area">
                        <div id="text-id-${item.id}">${item.text_id}</div>
                        <div id="text-en-${item.id}" class="hidden"><strong>${item.text_en}</strong></div>
                        <div id="text-pron-${item.id}" class="hidden"><em>${item.pronunciation}</em></div>
                        <div id="text-break-${item.id}" class="hidden explanation word-breakdown">${item.breakdown}</div>
                    </div>

                    <div class="btn-group">
                        <button onclick="showContent(${item.id}, 'en')" id="btn-en-${item.id}">English</button>
                        <button onclick="showContent(${item.id}, 'pron')" id="btn-pron-${item.id}">Cara Baca</button>
                        <button onclick="showContent(${item.id}, 'break')" id="btn-break-${item.id}">Pecahan</button>
                    </div>

                    <button class="btn-complete ${isDone ? 'active' : ''}" onclick="toggleComplete(${item.id})">
                        ${isDone ? 'Selesai (Klik untuk Batal)' : 'Tandai Sudah Lancar'}
                    </button>
                </div>
            `}).join('');
        }

        function showContent(id, type) {
            const idEl = document.getElementById(`text-id-${id}`);
            const enEl = document.getElementById(`text-en-${id}`);
            const pronEl = document.getElementById(`text-pron-${id}`);
            const breakEl = document.getElementById(`text-break-${id}`);
            const allBtns = document.querySelectorAll(`#card-${id} .btn-group button`);
            const targetBtn = document.getElementById(`btn-${type}-${id}`);

            if (!targetBtn.classList.contains('active')) {
                allBtns.forEach(b => b.classList.remove('active'));
                targetBtn.classList.add('active');
                [idEl, enEl, pronEl, breakEl].forEach(el => el.classList.add('hidden'));

                if (type === 'en') { enEl.classList.remove('hidden'); } 
                else if (type === 'pron') { pronEl.classList.remove('hidden'); idEl.classList.remove('hidden'); } 
                else if (type === 'break') { breakEl.classList.remove('hidden'); }
            } else {
                targetBtn.classList.remove('active');
                idEl.classList.remove('hidden');
                [enEl, pronEl, breakEl].forEach(el => el.classList.add('hidden'));
            }
        }

        init();
    </script>
</body>
</html>