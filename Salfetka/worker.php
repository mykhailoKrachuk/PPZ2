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
    <a href="index.html" class="logo">
      <div class="logo-icon">📦</div>
      <span>Salfetka</span>
    </a>
    <nav class="topbar-nav">
      <a class="link-btn" href="index.html">Wyszukaj przesyłkę</a>
      <a class="link-btn" href="login.html">Wyłoguj się</a>
    </nav>
  </header>

  <main class="worker-panel">
    <section class="worker-search">
      <h1 class="section-title">Strona Pracownika</h1>
      <div class="form-group">
        <label for="searchNumber">Numer przesyłki</label>
        <input id="searchNumber" class="worker-input" type="text" placeholder="Wpisz numer">
      </div>

      <div class="form-group">
        <label for="searchFirst">Imię</label>
        <input id="searchFirst" class="worker-input" type="text" placeholder="Wpisz imię">
      </div>

      <div class="form-group">
        <label for="searchLast">Nazwisko</label>
        <input id="searchLast" class="worker-input" type="text" placeholder="Wpisz nazwisko">
      </div>

      <button id="createBtn" class="worker-btn full" type="button">Utwórz</button>
    </section>

    <section class="worker-results">
      <h2 class="section-title">Lista przesyłek</h2>
      <div class="worker-controls">
        <div class="form-group" style="margin-bottom: 0;">
          <label for="destinationSelect">Miasto docelowe</label>
          <select id="destinationSelect" class="worker-input select">
            <option value="">Wszystkie miasta</option>
            <option value="Katowice">Katowice</option>
            <option value="Poznań">Poznań</option>
            <option value="Gdańsk">Gdańsk</option>
            <option value="Wrocław">Wrocław</option>
            <option value="Warszawa">Warszawa</option>
          </select>
        </div>
        <button id="detailsBtn" class="worker-btn" type="button" style="align-self: flex-end;">Wyświetl</button>
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
      <div class="form-row">
        <div class="form-group">
          <label for="receiverName" class="required">Imię i nazwisko odbiorcy</label>
          <input id="receiverName" type="text" required>
        </div>

        <div class="form-group">
          <label for="receiverPhone" class="required">Numer Telefonu</label>
          <input id="receiverPhone" type="tel" required>
        </div>
      </div>

      <div class="form-group">
        <label for="receiverAddress" class="required">Adres Dostawy</label>
        <input id="receiverAddress" type="text" required>
      </div>

      <div class="form-group">
        <label for="packageNote">Opis</label>
        <textarea id="packageNote" rows="2"></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="packageSize">Wymiary (np. 30x20x15 cm)</label>
          <input id="packageSize" type="text" placeholder="30x20x15 cm">
        </div>

        <div class="form-group">
          <label for="packageWeight">Waga (kg)</label>
          <input id="packageWeight" type="number" step="0.01" min="0" placeholder="0.5">
        </div>
      </div>

      <div class="form-group">
        <label for="packagePrice">Cena (PLN)</label>
        <input id="packagePrice" type="number" step="0.01" min="0" placeholder="249.99">
      </div>

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
    <div id="codeInputContainer" class="form-group hidden">
      <label for="nadaniaCode">Kod 6-cyfrowy</label>
      <input id="nadaniaCode" type="text" placeholder="Wpisz 6-cyfrowy kod" class="input" maxlength="6" pattern="[0-9]{6}">
      <small style="color: #666; font-size: 12px; margin-top: 4px; display: block;">Wprowadź 6-cyfrowy kod, aby odblokować przycisk wydania</small>
    </div>
    <button id="issueBtn" class="worker-btn full" type="button" disabled>wydaj</button>
  </div>
</div>

<div id="successOverlay" class="modal-overlay hidden" aria-hidden="true">
  <div class="modal-card worker-modal" role="dialog" aria-modal="true">
    <button class="close-btn" type="button" data-close="successOverlay" aria-label="Zamknij okno">&times;</button>
    <div class="success-message">
      <div class="success-icon">✓</div>
      <h3>Przesyłka wydana</h3>
      <button class="worker-btn full" type="button" data-close="successOverlay">OK</button>
    </div>
  </div>
</div>

<script>
  // Загрузка посылок z базы данных
  let shipments = [];
  
  // Загружаем посылки при загрузке strony
  function loadShipments() {
    fetch('/backend/parcel_worker_list.php')
      .then(response => {
        if (!response.ok) {
          throw new Error('Błąd ładowania przesyłek');
        }
        return response.json();
      })
      .then(data => {
        shipments = data;
        renderList();
        updateDestinationSelect();
      })
      .catch(error => {
        console.error('Błąd:', error);
        const listEl = document.getElementById('workerList');
        listEl.innerHTML = `
          <div class="empty">
            <div class="empty-icon">⚠️</div>
            <div class="empty-text">Błąd ładowania przesyłek. Odśwież stronę.</div>
          </div>
        `;
      });
  }
  
  loadShipments();

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
  const codeInputContainer = document.getElementById('codeInputContainer');
  const nadaniaCodeInput = document.getElementById('nadaniaCode');
  const successOverlay = document.getElementById('successOverlay');

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

  function updateDestinationSelect() {
    const destinations = [...new Set(shipments.map(s => s.destination))].sort();
    destinationSelect.innerHTML = '<option value="">Wszystkie miasta</option>';
    destinations.forEach(dest => {
      const option = document.createElement('option');
      option.value = dest;
      option.textContent = dest;
      destinationSelect.appendChild(option);
    });
  }

  function renderList(){
    listEl.innerHTML = '';
    const filtered = shipments.filter(matchesFilters);
    if(!filtered.length){
      const empty = document.createElement('div');
      empty.className = 'empty';
      empty.innerHTML = `
        <div class="empty-icon">📦</div>
        <div class="empty-text">Brak przesyłek do wyświetlenia</div>
      `;
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
      const errorOverlay = document.createElement('div');
      errorOverlay.className = 'modal-overlay';
      errorOverlay.innerHTML = `
        <div class="modal-card worker-modal">
          <button class="close-btn" type="button" onclick="this.closest('.modal-overlay').remove()">&times;</button>
          <div class="success-message">
            <div class="success-icon" style="color: #ef4444;">!</div>
            <h3>Błąd</h3>
            <p>Wybierz przesyłkę z listy.</p>
            <button class="worker-btn full" type="button" onclick="this.closest('.modal-overlay').remove()">OK</button>
          </div>
        </div>
      `;
      document.body.appendChild(errorOverlay);
      return;
    }
    const shipment = shipments.find(item => item.id === selectedId);
    if (!shipment) return;
    
    function getStatusBadgeClass(status) {
      const statusMap = {
        'Utworzona': 'utworzona',
        'W drodze': 'w-drodze',
        'Dostarczona': 'dostarczona',
        'Wydana klientowi': 'wydana-klientowi',
        'Przyjęta': 'przyjeta',
        'Otrzymana': 'dostarczona',
        'Magazyn': 'magazyn',
        'Gotowa do wysyłki': 'gotowa-do-wysylki',
        'Wysłana': 'wyslana'
      };
      return statusMap[status] || 'utworzona';
    }
    
    issueCode.textContent = shipment.id;
    issueStatus.innerHTML = 'Status: <span class="status-badge ' + getStatusBadgeClass(shipment.status) + '">' + shipment.status + '</span>';
    issueDate.textContent = 'Data otrzymania: ' + shipment.date.split('-').reverse().join('-');
    
    // Показываем поле для кода и блокируем кнопку
    codeInputContainer.classList.remove('hidden');
    nadaniaCodeInput.value = '';
    nadaniaCodeInput.classList.remove('error');
    issueBtn.disabled = true;
    
    // Валидация кода при вводе
    nadaniaCodeInput.oninput = function() {
      const code = this.value.trim();
      if (code.length === 6 && /^\d{6}$/.test(code)) {
        this.classList.remove('error');
        issueBtn.disabled = false;
      } else {
        this.classList.add('error');
        issueBtn.disabled = true;
      }
    };
    
    openOverlay(issueOverlay);
  });

  issueBtn.addEventListener('click', () => {
    if(!selectedId || issueBtn.disabled){ return; }
    
    const shipment = shipments.find(item => item.id === selectedId);
    if (!shipment) return;
    
    const code = nadaniaCodeInput.value.trim();
    if (!code || code.length !== 6 || !/^\d{6}$/.test(code)) {
      nadaniaCodeInput.classList.add('error');
      nadaniaCodeInput.focus();
      return;
    }
    
    // Отправляем запрос на сервер
    issueBtn.disabled = true;
    issueBtn.textContent = 'Wydawanie...';
    
    const formData = new FormData();
    formData.append('parcel_number', shipment.id);
    formData.append('code', code);
    
    fetch('/backend/parcel_issue.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          alert('Błąd: ' + data.error);
          issueBtn.disabled = false;
          issueBtn.textContent = 'wydaj';
          return;
        }
        
        // Успешно - закрываем окно и показываем сообщение
        closeOverlay(issueOverlay);
        openOverlay(successOverlay);
        
        // Обновляем список посылок
        loadShipments();
        selectedId = '';
      })
      .catch(error => {
        console.error('Błąd:', error);
        alert('Błąd połączenia z serwerem');
        issueBtn.disabled = false;
        issueBtn.textContent = 'wydaj';
      });
  });

  createForm.addEventListener('submit', e => {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('receiver_name', document.getElementById('receiverName').value.trim());
    formData.append('receiver_address', document.getElementById('receiverAddress').value.trim());
    formData.append('receiver_phone', document.getElementById('receiverPhone').value.trim());
    formData.append('description', document.getElementById('packageNote').value.trim());
    formData.append('size', document.getElementById('packageSize').value.trim());
    formData.append('weight', document.getElementById('packageWeight').value.trim());
    formData.append('price', document.getElementById('packagePrice').value.trim());
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Tworzenie...';
    
    fetch('/backend/parcel_create.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          alert('Błąd: ' + data.error);
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
          return;
        }
        
        // Успешно - закрываем форму и обновляем список
        e.target.reset();
        closeOverlay(createOverlay);
        
        // Показываем сообщение об успехе
        const successMsg = document.createElement('div');
        successMsg.className = 'modal-overlay';
        successMsg.innerHTML = `
          <div class="modal-card worker-modal">
            <button class="close-btn" type="button" onclick="this.closest('.modal-overlay').remove()">&times;</button>
            <div class="success-message">
              <div class="success-icon">✓</div>
              <h3>Przesyłka utworzona</h3>
              <p>Numer przesyłki: <strong>${data.parcel_number}</strong></p>
              <button class="worker-btn full" type="button" onclick="this.closest('.modal-overlay').remove()">OK</button>
            </div>
          </div>
        `;
        document.body.appendChild(successMsg);
        
        // Обновляем список посылок
        loadShipments();
      })
      .catch(error => {
        console.error('Błąd:', error);
        alert('Błąd połączenia z serwerem');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      });
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
      if(target){ 
        closeOverlay(target);
        // Обновляем список после закрытия окна успеха
        if(target.id === 'successOverlay'){
          loadShipments();
          selectedId = '';
        }
      }
    });
  });

  // renderList() вызывается после загрузки данных из API в функции loadShipments()
</script>
</body>
</html>

