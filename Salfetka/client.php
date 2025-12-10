<?php
require __DIR__ . '/../backend/require_auth.php';
requireRole('user'); // только клиенты
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Moje przesyłki</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Макет: слева фильтр, справа список */
        .layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 24px;
            max-width: 1100px;
            margin: 40px auto;
            align-items: start;
        }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,.08); padding: 20px; }
        .card h2 { font-size: 18px; margin: 0 0 16px; }
        .filter .input, .filter select { width: 100%; margin-bottom: 12px; }
        .tabs { display: flex; gap: 8px; margin-bottom: 12px; }
        .tab { padding: 8px 12px; border-radius: 999px; background: #eef3ff; cursor: pointer; user-select: none; }
        .tab.active { background: linear-gradient(90deg,#6aa5ff,#7ab6ff); color:#fff; }
        .list { display: flex; flex-direction: column; gap: 10px; }
        .item { display: grid; grid-template-columns: 1fr auto auto; gap: 12px; align-items: center;
            padding: 12px 14px; border-radius: 12px; background: #f8fafc; border: 1px solid #eaf0ff; cursor: pointer; }
        .pill { padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .pill.gray { background:#eef2f6; color:#556; }
        .pill.blue { background:#e9f1ff; color:#3173ff; }
        .pill.green{ background:#eaf8f0; color:#1c8c4a; }
        .pill.purple{ background:#efeaff; color:#6c4af2; }
        .muted { color:#778; font-size: 12px; }
        .actions { display:flex; gap:8px; }
        .btn-outline { padding:6px 10px; border-radius:8px; border:1px solid #cee2ff; background:#fff; cursor:pointer; }
        /* модалка */
        dialog { width: 760px; border: none; border-radius: 16px; box-shadow: 0 24px 64px rgba(0,0,0,.25); }
        dialog::backdrop { background: rgba(0,0,0,.35); }
        .modal-head { display:flex; justify-content: space-between; align-items:center; margin-bottom: 10px; }
        .close-x { cursor:pointer; font-size:22px; line-height:1; border:none; background:transparent; }
    </style>
</head>
<body>
<div class="page">
    <header class="topbar right">
        <nav>
            <a class="link-btn" href="index.html">Wyszukaj przesyłkę</a>
            <a class="link-btn" href="/backend/logout.php">Wyloguj się</a>
        </nav>
    </header>

    <main class="layout">
        <!-- Фильтр -->
        <section class="card filter">
            <h2>Filtruj</h2>
            <input id="q" class="input" type="text" placeholder="Numer przesyłki">
            <input id="date" class="input" type="date">
            <select id="status" class="input">
                <option value="">Wszystkie statusy</option>
                <option value="created">UTWORZONA</option>
                <option value="sent">WYSŁANA</option>
                <option value="in_transit">W DRODZE</option>
                <option value="received">OTRZYMANA W PUNKCIE</option>
            </select>
            <button id="apply" class="primary" type="button">Szukaj</button>
            <button id="reset" class="btn-outline" type="button">Reset</button>
        </section>

        <!-- Список -->
        <section class="card">
            <h2>Lista przesyłek</h2>
            <div class="tabs">
                <div class="tab active" data-tab="active">Aktywne</div>
                <div class="tab" data-tab="archive">Archiwum</div>
            </div>
            <div id="list" class="list"></div>
            <p id="empty" class="muted" style="display:none">Brak wyników.</p>
        </section>
    </main>
</div>

<!-- Модалка с деталями -->
<dialog id="dlg">
    <div class="modal-head">
        <h3>Szczegóły przesyłki</h3>
        <button class="close-x" onclick="dlg.close()">×</button>
    </div>
    <div id="details" class="muted">Ładowanie…</div>
</dialog>

<script>
    const listEl   = document.getElementById('list');
    const emptyEl  = document.getElementById('empty');
    const tabs     = document.querySelectorAll('.tab');
    const dlg      = document.getElementById('dlg');
    const details  = document.getElementById('details');

    let currentTab = 'active';

    function fmtStatus(st){
        switch(st){
            case 'created':    return ['UTWORZONA','pill gray'];
            case 'sent':       return ['WYSŁANA','pill blue'];
            case 'in_transit': return ['W DRODZE','pill purple'];
            case 'received':   return ['OTRZYMANA','pill green'];
            case 'delivered':  return ['DOSTARCZONA','pill green'];
            case 'issued':     return ['WYDANA','pill green'];
            case 'canceled':   return ['ANULOWANA','pill gray'];
            default:           return [st,'pill gray'];
        }
    }

    function render(list){
        listEl.innerHTML = '';
        if (!list.length){
            emptyEl.style.display = '';
            return;
        }
        emptyEl.style.display = 'none';

        list.forEach(row => {
            const [label, cls] = fmtStatus(row.status);
            const item = document.createElement('div');
            item.className = 'item';
            item.innerHTML = `
      <div>
        <div style="font-weight:600">${row.parcel_number}</div>
        <div class="muted">tel. ${row.phone ?? ''}</div>
      </div>
      <div><span class="${cls}">${label}</span></div>
      <div class="muted">${row.created_at?.replace('T',' ') ?? ''}</div>
    `;
            item.addEventListener('click', () => openDetails(row.parcel_number));
            listEl.appendChild(item);
        });
    }

    async function load(){
        const q = document.getElementById('q').value.trim();
        const date = document.getElementById('date').value;
        const status = document.getElementById('status').value;
        const url = new URL('/backend/client_list.php', location.origin);
        if (q) url.searchParams.set('q', q);
        if (date) url.searchParams.set('date', date);
        if (status) url.searchParams.set('status', status);

        const res = await fetch(url, {credentials:'same-origin'});
        const data = await res.json();
        const list = currentTab === 'active' ? (data.active || []) : (data.archive || []);
        render(list);
    }

    async function openDetails(number){
        details.textContent = 'Ładowanie…';
        dlg.showModal();
        try{
            const url = new URL('/backend/parcel_show.php', location.origin);
            url.searchParams.set('number', number);
            const res = await fetch(url, {credentials:'same-origin'});
            const d = await res.json();
            if (d.error) throw new Error(d.error);

            details.innerHTML = `
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div><b>Numer:</b> ${d.parcel_number}</div>
        <div><b>Telefon:</b> ${d.phone ?? ''}</div>
        <div><b>Nadawca:</b> ${d.sender_name ?? ''}</div>
        <div><b>Adres:</b> ${d.sender_address ?? ''}</div>
        <div style="grid-column:1/-1"><b>Opis:</b> ${d.description ?? ''}</div>
        <div><b>Waga:</b> ${d.weight ?? ''}</div>
        <div><b>Wymiary:</b> ${d.size ?? ''}</div>
        <div><b>Cena:</b> ${d.price ?? ''}</div>
        <hr style="grid-column:1/-1;border:none;border-top:1px solid #eee">
        <div><b>Utworzona:</b> ${d.created_at ?? '-'}</div>
        <div><b>Wysłana:</b> ${d.sent_at ?? '-'}</div>
        <div><b>Otrzymana w punkcie:</b> ${d.received_at ?? '-'}</div>
        <div><b>Wydana/Dostarczona:</b> ${d.issued_at ?? '-'}</div>
      </div>
    `;
        }catch(e){
            details.textContent = 'Błąd ładowania szczegółów.';
        }
    }

    document.getElementById('apply').addEventListener('click', load);
    document.getElementById('reset').addEventListener('click', () => {
        document.getElementById('q').value = '';
        document.getElementById('date').value = '';
        document.getElementById('status').value = '';
        load();
    });
    tabs.forEach(t => t.addEventListener('click', () => {
        tabs.forEach(x => x.classList.remove('active'));
        t.classList.add('active');
        currentTab = t.dataset.tab;
        load();
    }));

    // первый рендер
    load();
</script>
</body>
</html>
