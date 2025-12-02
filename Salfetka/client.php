<?php
require __DIR__ . '/../backend/require_auth.php';
requireRole('user'); // впускаем только клиентов

$user = $_SESSION['user'];
?>

<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Strona klienta</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="page client-page">
  <header class="topbar">
    <nav><a class="link-btn" href="login.html">Wyłoguj się</a></nav>
  </header>

  <main class="client-panel">
    <aside class="filter-card">
      <h2>Filtruj</h2>
      <label for="filterId" class="visually-hidden">Numer przesyłki</label>
      <input id="filterId" class="input slim" type="text" placeholder="numer przesyłki">

      <label for="filterDate" class="visually-hidden">Data nadania</label>
      <input id="filterDate" class="input slim" type="date">

      <label for="filterStatus" class="visually-hidden">Status</label>
      <select id="filterStatus" class="input slim">
        <option value="">status</option>
        <option>Utworzona</option>
        <option>W drodze</option>
        <option>Dostarczona</option>
        <option>Wydana klientowi</option>
      </select>
    </aside>

    <section class="shipments-card">
      <h2>Lista przesyłek</h2>
      <div id="shipmentList" class="shipments-list"></div>
    </section>

    <div id="detailsOverlay" class="details-overlay hidden" aria-hidden="true">
      <div class="details-card" role="dialog" aria-modal="true" aria-labelledby="detailsHeading">
        <button id="closeDetails" class="close-btn" type="button" aria-label="Zamknij okno">&times;</button>
        <h3 id="detailsHeading">Informacje o przesyłce</h3>
        <p class="details-code" id="detailsCode">Wybierz przesyłkę</p>
        <p id="detailsStatus"></p>
        <p id="detailsDate"></p>
      </div>
    </div>
  </main>
</div>

<script>
  const shipments = [
    { id:'NPX66UW2GP', status:'Utworzona', date:'2025-10-24' },
    { id:'WX90PL0AA1', status:'W drodze', date:'2025-10-20' },
    { id:'JL55MK7CD2', status:'Dostarczona', date:'2025-09-18' },
    { id:'BT73RT1XZ3', status:'Wydana klientowi', date:'2025-08-04' },
    { id:'QP19ZD8LK4', status:'W drodze', date:'2025-10-18' }
  ];

  const listEl = document.getElementById('shipmentList');
  const detailsOverlay = document.getElementById('detailsOverlay');
  const closeDetailsBtn = document.getElementById('closeDetails');
  const detailCode = document.getElementById('detailsCode');
  const detailStatus = document.getElementById('detailsStatus');
  const detailDate = document.getElementById('detailsDate');

  const filterId = document.getElementById('filterId');
  const filterDate = document.getElementById('filterDate');
  const filterStatus = document.getElementById('filterStatus');

  const filters = {
    id: '',
    date: '',
    status: ''
  };

  function applyFilters(){
    const idValue = filters.id.toLowerCase();
    return shipments.filter(item => {
      const matchesId = !idValue || item.id.toLowerCase().includes(idValue);
      const matchesDate = !filters.date || item.date === filters.date;
      const matchesStatus = !filters.status || item.status === filters.status;
      return matchesId && matchesDate && matchesStatus;
    });
  }

  function renderList(){
    const filtered = applyFilters();
    listEl.innerHTML = '';
    if(!filtered.length){
      const empty = document.createElement('p');
      empty.className = 'empty';
      empty.textContent = 'Brak przesyłek.';
      listEl.appendChild(empty);
      return;
    }

    filtered.forEach(item => {
      const btn = document.createElement('button');
      btn.className = 'shipment-row';
      btn.type = 'button';
      btn.innerHTML = `
        <strong>${item.id}</strong>
        <span>${item.status}</span>
        <span>${item.date}</span>
      `;
      btn.addEventListener('click', () => showDetails(item));
      listEl.appendChild(btn);
    });
  }

  function showDetails(item){
    detailCode.textContent = item.id;
    detailStatus.textContent = 'Status: ' + item.status;
    detailDate.textContent = 'Data wysłania: ' + item.date.split('-').reverse().join('-');
    detailsOverlay.classList.remove('hidden');
    detailsOverlay.setAttribute('aria-hidden', 'false');
  }

  function hideDetails(){
    detailsOverlay.classList.add('hidden');
    detailsOverlay.setAttribute('aria-hidden', 'true');
  }

  closeDetailsBtn.addEventListener('click', hideDetails);
  detailsOverlay.addEventListener('click', (e) => {
    if(e.target === detailsOverlay){
      hideDetails();
    }
  });

  function updateFilter(key, value){
    filters[key] = value;
    renderList();
  }

  filterId.addEventListener('input', e => updateFilter('id', e.target.value));
  filterDate.addEventListener('input', e => updateFilter('date', e.target.value));
  filterStatus.addEventListener('change', e => updateFilter('status', e.target.value));

  renderList();
</script>
</body>
</html>

