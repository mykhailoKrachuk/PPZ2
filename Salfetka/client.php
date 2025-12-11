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
    <a href="index.html" class="logo">
      <div class="logo-icon">📦</div>
      <span>Salfetka</span>
    </a>
    <nav class="topbar-nav">
      <a class="link-btn" href="index.html">Wyszukaj przesyłkę</a>
      <a class="link-btn" href="login.html">Wyłoguj się</a>
    </nav>
  </header>

  <main class="client-panel">
    <aside class="filter-card">
      <h2 class="section-title">Filtruj</h2>
      <div class="form-group">
        <label for="filterId">Numer przesyłki</label>
        <input id="filterId" class="input slim" type="text" placeholder="Wpisz numer przesyłki">
      </div>

      <div class="form-group">
        <label for="filterDate">Data nadania</label>
        <input id="filterDate" class="input slim" type="date">
      </div>

      <div class="form-group">
        <label for="filterStatus">Status</label>
        <select id="filterStatus" class="input slim">
          <option value="">Wszystkie statusy</option>
          <option>Utworzona</option>
          <option>Wysłana</option>
          <option>Otrzymana</option>
          <option>Wydana</option>
        </select>
      </div>
    </aside>

    <section class="shipments-card">
      <h2 class="section-title">Lista przesyłek</h2>
      <div class="tabs">
        <button class="tab-btn active" data-tab="active" id="tabActive">Aktywne</button>
        <button class="tab-btn" data-tab="archive" id="tabArchive">Archiwum</button>
      </div>
      <div id="shipmentList" class="shipments-list"></div>
      <div id="archiveList" class="shipments-list hidden"></div>
    </section>

    <div id="detailsOverlay" class="details-overlay hidden" aria-hidden="true">
      <div class="details-card" role="dialog" aria-modal="true" aria-labelledby="detailsHeading">
        <button id="closeDetails" class="close-btn" type="button" aria-label="Zamknij okno">&times;</button>
        <h3 id="detailsHeading">Informacje o przesyłce</h3>
        <p class="details-code" id="detailsCode">Wybierz przesyłkę</p>
        <p id="detailsStatus"></p>
        <p id="detailsDate"></p>
        <div id="detailsRoute" class="route-timeline"></div>
      </div>
    </div>

    <div id="infoOverlay" class="details-overlay hidden" aria-hidden="true">
      <div class="details-card info-card" role="dialog" aria-modal="true" aria-labelledby="infoHeading">
        <button id="closeInfo" class="close-btn" type="button" aria-label="Zamknij okno">&times;</button>
        <h3 id="infoHeading">Szczegóły przesyłki</h3>
        <div id="infoContent" class="info-content"></div>
      </div>
    </div>
  </main>
</div>

<script>
  // Загрузка посылок из базы данных
  let shipments = [];
  
  // Загружаем посылки при загрузке страницы
  fetch('/backend/parcel_list.php')
    .then(response => {
      if (!response.ok) {
        throw new Error('Błąd ładowania przesyłek');
      }
      return response.json();
    })
    .then(data => {
      shipments = data;
      renderList(false); // Начальная загрузка активных посылок
    })
    .catch(error => {
      console.error('Błąd:', error);
      const listEl = document.getElementById('shipmentList');
      listEl.innerHTML = `
        <div class="empty">
          <div class="empty-icon">⚠️</div>
          <div class="empty-text">Błąd ładowania przesyłek. Odśwież stronę.</div>
        </div>
      `;
    });
  
  // Старый hardcoded массив (удалён, теперь загружается из API)
  /*
  const shipments = [
    { 
      id:'NPX66UW2GP', 
      status:'Utworzona', 
      date:'2025-10-24',
      created_at: '2025-10-22 10:12',
      sent_at: null,
      received_at: null,
      issued_at: null,
      phone: '+48 500 123 456',
      parcel: 'Paczka standardowa',
      sender: 'Sklep Online Sp. z o.o.',
      sender_name: 'Jan Kowalski',
      sender_address: 'ul. Przykładowa 15, 00-001 Warszawa',
      description: 'Elektronika - słuchawki bezprzewodowe',
      size: '30x20x15 cm',
      weight: '0.5 kg',
      price: '249.99 PLN',
      route: [
        { status: 'Utworzona', label: 'Utworzona', date: '22-10-2025 10:12', location: 'Nadanie paczki' }
      ]
    },
    { 
      id:'WX90PL0AA1', 
      status:'Wysłana', 
      date:'2025-10-20',
      created_at: '2025-10-18 09:05',
      sent_at: '2025-10-19 14:30',
      received_at: null,
      issued_at: null,
      phone: '+48 501 234 567',
      parcel: 'Paczka ekspresowa',
      sender: 'Moda Fashion S.A.',
      sender_name: 'Anna Nowak',
      sender_address: 'ul. Modowa 42, 30-001 Kraków',
      description: 'Odzież - kurtka zimowa',
      size: '40x30x10 cm',
      weight: '1.2 kg',
      price: '399.00 PLN',
      route: [
        { status: 'Utworzona', label: 'Utworzona', date: '18-10-2025 09:05', location: 'Rejestracja przesyłki' },
        { status: 'Wysłana', label: 'Wysłana z magazynu', date: '19-10-2025 14:30', location: 'Magazyn centralny' }
      ]
    },
    { 
      id:'JL55MK7CD2', 
      status:'Otrzymana', 
      date:'2025-09-18',
      created_at: '2025-09-15 11:20',
      sent_at: '2025-09-16 08:10',
      received_at: '2025-09-18 17:30',
      issued_at: null,
      phone: '+48 502 345 678',
      parcel: 'Paczka standardowa',
      sender: 'Księgarnia Online',
      sender_name: 'Piotr Wiśniewski',
      sender_address: 'ul. Książkowa 7, 60-001 Poznań',
      description: 'Książki - zestaw 3 pozycji',
      size: '25x18x12 cm',
      weight: '0.8 kg',
      price: '89.99 PLN',
      route: [
        { status: 'Utworzona', label: 'Utworzona', date: '15-09-2025 11:20', location: 'Utworzenie etykiety' },
        { status: 'Wysłana', label: 'Wysłana', date: '16-09-2025 08:10', location: 'Centrum sortowania' },
        { status: 'Otrzymana', label: 'Otrzymana w paczkomacie', date: '18-09-2025 17:30', location: 'Paczkamat JL-02' }
      ]
    },
    { 
      id:'BT73RT1XZ3', 
      status:'Wydana', 
      date:'2025-08-04',
      created_at: '2025-08-01 10:00',
      sent_at: '2025-08-02 13:40',
      received_at: '2025-08-03 09:15',
      issued_at: '2025-08-04 18:02',
      phone: '+48 503 456 789',
      parcel: 'Paczka priorytetowa',
      sender: 'TechStore Sp. z o.o.',
      sender_name: 'Marek Zieliński',
      sender_address: 'ul. Techniczna 99, 00-100 Warszawa',
      description: 'Smartfon - model premium',
      size: '20x15x5 cm',
      weight: '0.3 kg',
      price: '3299.00 PLN',
      route: [
        { status: 'Utworzona', label: 'Utworzona', date: '01-08-2025 10:00', location: 'Sklep internetowy' },
        { status: 'Wysłana', label: 'Wysłana', date: '02-08-2025 13:40', location: 'Kurier w trasie' },
        { status: 'Otrzymana', label: 'Otrzymana w punkcie', date: '03-08-2025 09:15', location: 'Punkt odbioru BT-17' },
        { status: 'Wydana', label: 'Wydana klientowi', date: '04-08-2025 18:02', location: 'Punkt odbioru BT-17' }
      ]
    },
    { 
      id:'QP19ZD8LK4', 
      status:'Wysłana', 
      date:'2025-10-18',
      created_at: '2025-10-16 12:48',
      sent_at: '2025-10-18 08:20',
      received_at: null,
      issued_at: null,
      phone: '+48 504 567 890',
      parcel: 'Paczka standardowa',
      sender: 'Dom i Ogród',
      sender_name: 'Katarzyna Lewandowska',
      sender_address: 'ul. Ogrodowa 23, 90-001 Łódź',
      description: 'Narzędzia ogrodowe - zestaw',
      size: '50x30x20 cm',
      weight: '2.5 kg',
      price: '179.99 PLN',
      route: [
        { status: 'Utworzona', label: 'Utworzona', date: '16-10-2025 12:48', location: 'Nadanie' },
        { status: 'Wysłana', label: 'Wysłana', date: '18-10-2025 08:20', location: 'Trasa do paczkomatu' }
      ]
    }
  ];
  */

  const listEl = document.getElementById('shipmentList');
  const archiveListEl = document.getElementById('archiveList');
  const tabActive = document.getElementById('tabActive');
  const tabArchive = document.getElementById('tabArchive');
  const detailsOverlay = document.getElementById('detailsOverlay');
  const closeDetailsBtn = document.getElementById('closeDetails');
  const detailCode = document.getElementById('detailsCode');
  const detailStatus = document.getElementById('detailsStatus');
  const detailDate = document.getElementById('detailsDate');
  const detailRoute = document.getElementById('detailsRoute');
  
  const infoOverlay = document.getElementById('infoOverlay');
  const closeInfoBtn = document.getElementById('closeInfo');
  const infoContent = document.getElementById('infoContent');

  const filterId = document.getElementById('filterId');
  const filterDate = document.getElementById('filterDate');
  const filterStatus = document.getElementById('filterStatus');

  const filters = {
    id: '',
    date: '',
    status: ''
  };

  function applyFilters(isArchive = false){
    const idValue = filters.id.toLowerCase();
    return shipments.filter(item => {
      const isArchived = item.status === 'Wydana';
      if(isArchive !== isArchived) return false;
      
      const matchesId = !idValue || item.id.toLowerCase().includes(idValue);
      const matchesDate = !filters.date || item.date === filters.date;
      const matchesStatus = !filters.status || item.status === filters.status;
      return matchesId && matchesDate && matchesStatus;
    });
  }

  function getStatusBadgeClass(status) {
    const statusMap = {
      'Utworzona': 'utworzona',
      'Wysłana': 'w-drodze',
      'Otrzymana': 'dostarczona',
      'Wydana': 'wydana-klientowi'
    };
    return statusMap[status] || 'utworzona';
  }

  function getStatusLabel(status) {
    const statusMap = {
      'Utworzona': 'Utworzona',
      'Wysłana': 'Wysłana',
      'Otrzymana': 'Otrzymana',
      'Wydana': 'Wydana'
    };
    return statusMap[status] || status;
  }

  function renderList(isArchive = false){
    const targetList = isArchive ? archiveListEl : listEl;
    const filtered = applyFilters(isArchive);
    // Сортировка по дате в обратном порядке (более новые выше)
    const sorted = [...filtered].sort((a, b) => {
      const dateA = new Date(a.date);
      const dateB = new Date(b.date);
      return dateB - dateA; // Обратный порядок
    });
    
    targetList.innerHTML = '';
    if(!sorted.length){
      const empty = document.createElement('div');
      empty.className = 'empty';
      empty.innerHTML = `
        <div class="empty-icon">📦</div>
        <div class="empty-text">${isArchive ? 'Brak przesyłek w archiwum' : 'Brak przesyłek do wyświetlenia'}</div>
      `;
      targetList.appendChild(empty);
      return;
    }

    sorted.forEach(item => {
      const row = document.createElement('div');
      row.className = 'shipment-row-wrapper';
      row.innerHTML = `
        <button class="shipment-row" type="button">
          <strong>${item.id}</strong>
          <span class="status-badge ${getStatusBadgeClass(item.status)}">${getStatusLabel(item.status)}</span>
          <span>${item.date.split('-').reverse().join('-')}</span>
        </button>
        <button class="info-btn" type="button" aria-label="Szczegóły">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
        </button>
      `;
      const rowBtn = row.querySelector('.shipment-row');
      const infoBtn = row.querySelector('.info-btn');
      rowBtn.addEventListener('click', () => showDetails(item));
      infoBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        showInfo(item);
      });
      targetList.appendChild(row);
    });
  }

  function switchTab(tabName){
    const isArchive = tabName === 'archive';
    
    // Обновление активной вкладки
    tabActive.classList.toggle('active', !isArchive);
    tabArchive.classList.toggle('active', isArchive);
    
    // Показать/скрыть списки
    listEl.classList.toggle('hidden', isArchive);
    archiveListEl.classList.toggle('hidden', !isArchive);
    
    // Перерисовать список
    renderList(isArchive);
  }

  tabActive.addEventListener('click', () => switchTab('active'));
  tabArchive.addEventListener('click', () => switchTab('archive'));

  function renderRoute(item){
    if(!item.route || !item.route.length){
      detailRoute.innerHTML = '';
      return;
    }

    // Обратный порядок - более новые статусы выше
    const reversedRoute = [...item.route].reverse();

    const stepsHtml = reversedRoute.map((step, index) => {
      // Первый элемент (после reverse) - это последний статус, он текущий
      const isCurrent = index === 0;
      const badgeClass = getStatusBadgeClass(step.status);
      return `
        <div class="route-step ${isCurrent ? 'route-step--current' : ''}">
          <div class="route-step-marker">
            <span class="route-step-dot"></span>
            ${index < reversedRoute.length - 1 ? '<span class="route-step-line"></span>' : ''}
          </div>
          <div class="route-step-content">
            <div class="route-step-title">${step.label}</div>
            <div class="route-step-meta">${step.date}</div>
            <div class="route-step-meta">${step.location}</div>
            <div class="route-step-status">
              <span class="status-badge ${badgeClass}">${getStatusLabel(step.status)}</span>
            </div>
          </div>
        </div>
      `;
    }).join('');

    detailRoute.innerHTML = `
      <h4 class="route-title">Trasa przesyłki</h4>
      ${stepsHtml}
    `;
  }

  function showDetails(item){
    detailCode.textContent = item.id;
    detailStatus.innerHTML = 'Status: <span class="status-badge ' + getStatusBadgeClass(item.status) + '">' + getStatusLabel(item.status) + '</span>';
    detailDate.textContent = 'Data wysłania: ' + item.date.split('-').reverse().join('-');
    renderRoute(item);
    detailsOverlay.classList.remove('hidden');
    detailsOverlay.setAttribute('aria-hidden', 'false');
  }

  function hideDetails(){
    detailsOverlay.classList.add('hidden');
    detailsOverlay.setAttribute('aria-hidden', 'true');
  }

  function showInfo(item){
    const formatDate = (dateStr) => {
      if(!dateStr) return '-';
      // Формат: YYYY-MM-DD HH:mm -> DD-MM-YYYY HH:mm
      const date = new Date(dateStr);
      if (isNaN(date.getTime())) return '-';
      const day = String(date.getDate()).padStart(2, '0');
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const year = date.getFullYear();
      const hours = String(date.getHours()).padStart(2, '0');
      const minutes = String(date.getMinutes()).padStart(2, '0');
      return `${day}-${month}-${year} ${hours}:${minutes}`;
    };

    infoContent.innerHTML = `
      <div class="info-section">
        <h4>Dane podstawowe</h4>
        <div class="info-row">
          <span class="info-label">Numer przesyłki:</span>
          <span class="info-value">${item.id}</span>
        </div>
      </div>

      <div class="info-section">
        <h4>Odbiorca</h4>
        <div class="info-row">
          <span class="info-label">Imię i nazwisko:</span>
          <span class="info-value">${item.sender_name || '-'}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Adres:</span>
          <span class="info-value">${item.sender_address || '-'}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Telefon:</span>
          <span class="info-value">${item.phone || '-'}</span>
        </div>
      </div>

      <div class="info-section">
        <h4>Szczegóły przesyłki</h4>
        <div class="info-row">
          <span class="info-label">Opis:</span>
          <span class="info-value">${item.description || '-'}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Wymiary:</span>
          <span class="info-value">${item.size || '-'}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Waga:</span>
          <span class="info-value">${item.weight || '-'}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Cena:</span>
          <span class="info-value">${item.price || '-'}</span>
        </div>
      </div>

      <div class="info-section">
        <h4>Statusy czasowe</h4>
        <div class="info-row">
          <span class="info-label">Utworzona:</span>
          <span class="info-value">${formatDate(item.created_at)}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Wysłana:</span>
          <span class="info-value">${formatDate(item.sent_at)}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Otrzymana:</span>
          <span class="info-value">${formatDate(item.received_at)}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Wydana:</span>
          <span class="info-value">${formatDate(item.issued_at)}</span>
        </div>
      </div>
    `;
    infoOverlay.classList.remove('hidden');
    infoOverlay.setAttribute('aria-hidden', 'false');
  }

  function hideInfo(){
    infoOverlay.classList.add('hidden');
    infoOverlay.setAttribute('aria-hidden', 'true');
  }

  closeDetailsBtn.addEventListener('click', hideDetails);
  detailsOverlay.addEventListener('click', (e) => {
    if(e.target === detailsOverlay){
      hideDetails();
    }
  });

  closeInfoBtn.addEventListener('click', hideInfo);
  infoOverlay.addEventListener('click', (e) => {
    if(e.target === infoOverlay){
      hideInfo();
    }
  });

  function updateFilter(key, value){
    filters[key] = value;
    const isArchive = tabArchive.classList.contains('active');
    renderList(isArchive);
  }

  filterId.addEventListener('input', e => updateFilter('id', e.target.value));
  filterDate.addEventListener('input', e => updateFilter('date', e.target.value));
  filterStatus.addEventListener('change', e => updateFilter('status', e.target.value));

  // renderList(false) вызывается после загрузки данных из API
</script>
</body>
</html>

