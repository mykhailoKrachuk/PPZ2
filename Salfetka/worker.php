<?php
require __DIR__ . '/../backend/require_auth.php';
requireRole('worker'); // только сотрудники

$user = $_SESSION['user'];
?>

<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Strona Pracownika</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="page worker-page">
  <header class="topbar">
    <nav><a class="link-btn" href="login.html">Wyłoguj się</a></nav>
  </header>

  <main class="worker-panel">
    <section class="worker-search">
      <h1>Strona Pracownika</h1>
      <label for="searchNumber" class="visually-hidden">Numer przesyłki</label>
      <input id="searchNumber" class="worker-input" type="text" placeholder="wpisz numer">

      <label for="searchFirst" class="visually-hidden">Imię</label>
      <input id="searchFirst" class="worker-input" type="text" placeholder="Imię">

      <label for="searchLast" class="visually-hidden">Nazwisko</label>
      <input id="searchLast" class="worker-input" type="text" placeholder="Nazwisko">

      <button id="createBtn" class="worker-btn full" type="button">Utwórz</button>
    </section>

    <section class="worker-results">
      <div class="worker-controls">
        <label for="destinationSelect">Do:</label>
        <select id="destinationSelect" class="worker-input select">
          <option value="">Wszystkie miasta</option>
          <option value="Katowice">Katowice</option>
          <option value="Poznań">Poznań</option>
          <option value="Gdańsk">Gdańsk</option>
          <option value="Wrocław">Wrocław</option>
          <option value="Warszawa">Warszawa</option>
        </select>
        <button id="detailsBtn" class="worker-btn" type="button">Wyświetl</button>
      </div>

      <div id="workerList" class="worker-list"></div>
    </section>
  </main>
</div>

<div id="createOverlay" class="modal-overlay hidden" aria-hidden="true">
  <div class="modal-card worker-modal" role="dialog" aria-modal="true" aria-labelledby="createHeading">
    <button class="close-btn" type="button" data-close="createOverlay" aria-label="Zamknij okno">&times;</button>
    <h2 id="createHeading">Tworzenie Przesyłki</h2>
    <form id="createForm" class="modal-form">
      <label for="receiverName">Imię i nazwisko odbiorcy</label>
      <input id="receiverName" type="text" required>

      <label for="receiverAddress">Adres Dostawy</label>
      <input id="receiverAddress" type="text" required>

      <label for="receiverPhone">Numer Telefonu</label>
      <input id="receiverPhone" type="tel" required>

      <label for="packageNote">Opis</label>
      <textarea id="packageNote" rows="2"></textarea>

      <button class="worker-btn full" type="submit">Utwórz</button>
    </form>
  </div>
</div>

<div id="issueOverlay" class="modal-overlay hidden" aria-hidden="true">
  <div class="modal-card worker-modal" role="dialog" aria-modal="true" aria-labelledby="issueHeading">
    <button class="close-btn" type="button" data-close="issueOverlay" aria-label="Zamknij okno">&times;</button>
    <h2 id="issueHeading">Informacje o przesyłce</h2>
    <p id="issueCode" class="details-code">Wybierz przesyłkę</p>
    <p id="issueStatus"></p>
    <p id="issueDate"></p>
    <button id="issueBtn" class="worker-btn full" type="button">wydaj</button>
  </div>
</div>

<script>
  const shipments = [
    { id:'MFINBDJK87', destination:'Katowice', status:'Utworzona', date:'2025-10-24', receiverFirst:'Anna', receiverLast:'Nowak', address:'ul. Leśna 10, Katowice', phone:'+48 500 200 111', note:'Pilne' },
    { id:'AMSDNA1192', destination:'Poznań', status:'W drodze', date:'2025-10-20', receiverFirst:'Marek', receiverLast:'Kowalski', address:'ul. Wiślana 3, Poznań', phone:'+48 501 111 222', note:'' },
    { id:'HDJALAMA78', destination:'Gdańsk', status:'Przyjęta', date:'2025-10-23', receiverFirst:'Julia', receiverLast:'Mazur', address:'ul. Morska 7, Gdańsk', phone:'+48 502 333 444', note:'Delikatna zawartość' },
    { id:'HSKNBDJK87', destination:'Wrocław', status:'Magazyn', date:'2025-10-19', receiverFirst:'Kamil', receiverLast:'Polański', address:'ul. Lipowa 17, Wrocław', phone:'+48 503 555 666', note:'' },
    { id:'HKBYHSK227', destination:'Warszawa', status:'Gotowa do wysyłki', date:'2025-10-21', receiverFirst:'Olga', receiverLast:'Zielińska', address:'ul. Długa 4, Warszawa', phone:'+48 504 777 888', note:'Odbiór osobisty' }
  ];

  const filters = { number:'', first:'', last:'', destination:'' };
  let selectedId = '';

  const listEl = document.getElementById('workerList');
  const destinationSelect = document.getElementById('destinationSelect');
  const detailsBtn = document.getElementById('detailsBtn');
  const createBtn = document.getElementById('createBtn');
  const createOverlay = document.getElementById('createOverlay');
  const issueOverlay = document.getElementById('issueOverlay');
  const createForm = document.getElementById('createForm');
  const issueBtn = document.getElementById('issueBtn');
  const issueCode = document.getElementById('issueCode');
  const issueStatus = document.getElementById('issueStatus');
  const issueDate = document.getElementById('issueDate');

  document.getElementById('searchNumber').addEventListener('input', e => { filters.number = e.target.value.trim().toLowerCase(); renderList(); });
  document.getElementById('searchFirst').addEventListener('input', e => { filters.first = e.target.value.trim().toLowerCase(); renderList(); });
  document.getElementById('searchLast').addEventListener('input', e => { filters.last = e.target.value.trim().toLowerCase(); renderList(); });
  destinationSelect.addEventListener('change', e => { filters.destination = e.target.value; renderList(); });

  function matchesFilters(item){
    const numberOk = !filters.number || item.id.toLowerCase().includes(filters.number);
    const firstOk = !filters.first || item.receiverFirst.toLowerCase().includes(filters.first);
    const lastOk = !filters.last || item.receiverLast.toLowerCase().includes(filters.last);
    const destOk = !filters.destination || item.destination === filters.destination;
    return numberOk && firstOk && lastOk && destOk;
  }

  function renderList(){
    listEl.innerHTML = '';
    const filtered = shipments.filter(matchesFilters);
    if(!filtered.length){
      const empty = document.createElement('p');
      empty.className = 'empty';
      empty.textContent = 'Brak wyników.';
      listEl.appendChild(empty);
      selectedId = '';
      return;
    }
    filtered.forEach(item => {
      const btn = document.createElement('button');
      btn.className = 'worker-row';
      btn.type = 'button';
      btn.dataset.id = item.id;
      btn.innerHTML = `<span>${item.id}</span><strong>${item.destination.toLowerCase()}</strong>`;
      btn.addEventListener('click', () => selectShipment(item.id, btn));
      if(item.id === selectedId){
        btn.classList.add('active');
      }
      listEl.appendChild(btn);
    });
  }

  function selectShipment(id, button){
    selectedId = id;
    document.querySelectorAll('.worker-row').forEach(row => row.classList.remove('active'));
    button.classList.add('active');
  }

  function openOverlay(element){
    element.classList.remove('hidden');
    element.setAttribute('aria-hidden', 'false');
  }

  function closeOverlay(element){
    element.classList.add('hidden');
    element.setAttribute('aria-hidden', 'true');
  }

  createBtn.addEventListener('click', () => openOverlay(createOverlay));
  detailsBtn.addEventListener('click', () => {
    if(!selectedId){
      alert('Wybierz przesyłkę z listy.');
      return;
    }
    const shipment = shipments.find(item => item.id === selectedId);
    issueCode.textContent = shipment.id;
    issueStatus.textContent = 'Status: ' + shipment.status;
    issueDate.textContent = 'Data wysłania: ' + shipment.date.split('-').reverse().join('-');
    openOverlay(issueOverlay);
  });

  issueBtn.addEventListener('click', () => {
    if(!selectedId){ return; }
    alert('Przesyłka wydana: ' + selectedId);
    closeOverlay(issueOverlay);
  });

  createForm.addEventListener('submit', e => {
    e.preventDefault();
    const newShipment = {
      id: 'NP' + Math.random().toString(36).slice(2, 8).toUpperCase(),
      destination: filters.destination || 'Katowice',
      status: 'Utworzona',
      date: new Date().toISOString().split('T')[0],
      receiverFirst: document.getElementById('receiverName').value.split(' ')[0] || 'Nowy',
      receiverLast: document.getElementById('receiverName').value.split(' ')[1] || 'Klient',
      address: document.getElementById('receiverAddress').value,
      phone: document.getElementById('receiverPhone').value,
      note: document.getElementById('packageNote').value || ''
    };
    shipments.unshift(newShipment);
    e.target.reset();
    closeOverlay(createOverlay);
    renderList();
  });

  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if(e.target === overlay){
        closeOverlay(overlay);
      }
    });
  });
  document.querySelectorAll('[data-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.close);
      if(target){ closeOverlay(target); }
    });
  });

  renderList();
</script>
</body>
</html>

