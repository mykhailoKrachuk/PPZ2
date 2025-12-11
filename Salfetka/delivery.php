<?php
require __DIR__ . '/../backend/require_auth.php';
requireRole('deliver'); // только курьеры/доставщики

$user = $_SESSION['user'];
?>

<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pracownik działu dostaw</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="page delivery-page">
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

  <main class="delivery-panel">
    <section class="delivery-list-card">
      <h1 class="section-title">Pracownik działu dostaw</h1>
      <div id="deliveryList" class="delivery-list"></div>
    </section>

    <section class="delivery-actions">
      <button id="receiveBtn" class="delivery-action" type="button" disabled>Przyjęcie</button>
      <button id="shipBtn" class="delivery-action" type="button" disabled>Wysyłka</button>
    </section>
  </main>
</div>

<script>
  // Загрузка посылок z базы danych
  let deliveries = [];
  let selectedDelivery = null;

  const listContainer = document.getElementById('deliveryList');
  const receiveBtn = document.getElementById('receiveBtn');
  const shipBtn = document.getElementById('shipBtn');

  // Загружаем посылки при загрузке strony
  function loadDeliveries() {
    fetch('/backend/parcel_delivery_list.php')
      .then(response => {
        if (!response.ok) {
          throw new Error('Błąd ładowania przesyłek');
        }
        return response.json();
      })
      .then(data => {
        deliveries = data;
        renderDeliveries();
        // Сбрасываем выбор при обновлении списка
        selectedDelivery = null;
        receiveBtn.disabled = true;
        shipBtn.disabled = true;
        receiveBtn.textContent = 'Przyjęcie';
        shipBtn.textContent = 'Wysyłka';
      })
      .catch(error => {
        console.error('Błąd:', error);
        listContainer.innerHTML = `
          <div class="empty">
            <div class="empty-icon">⚠️</div>
            <div class="empty-text">Błąd ładowania przesyłek. Odśwież stronę.</div>
          </div>
        `;
      });
  }

  function renderDeliveries(){
    listContainer.innerHTML = '';
    if(!deliveries.length){
      const empty = document.createElement('div');
      empty.className = 'empty';
      empty.innerHTML = `
        <div class="empty-icon">📦</div>
        <div class="empty-text">Brak przesyłek do wyświetlenia</div>
      `;
      listContainer.appendChild(empty);
      // Деактивируем обе кнопки, если нет посылок
      receiveBtn.disabled = true;
      shipBtn.disabled = true;
      return;
    }
    
    deliveries.forEach(item => {
      const row = document.createElement('button');
      row.type = 'button';
      row.className = 'delivery-row';
      const statusLabel = item.type === 'created' ? 'Utworzona' : 'Wysłana';
      row.innerHTML = `
        <span>${item.id}</span>
        <span style="font-size: 12px; color: #666;">${statusLabel}</span>
      `;
      row.addEventListener('click', () => selectDelivery(item, row));
      listContainer.appendChild(row);
    });
  }

  function selectDelivery(delivery, rowEl){
    selectedDelivery = delivery;
    document.querySelectorAll('.delivery-row').forEach(btn => btn.classList.remove('active'));
    rowEl.classList.add('active');
    
    // Активируем соответствующую кнопку в зависимости от типа посылки
    if (delivery.type === 'created') {
      // Для created - активируем кнопку Wysyłka
      shipBtn.disabled = false;
      receiveBtn.disabled = true;
    } else if (delivery.type === 'sent') {
      // Для sent - активируем кнопку Przyjęcie
      receiveBtn.disabled = false;
      shipBtn.disabled = true;
    }
  }

  function handleSend(){
    if(!selectedDelivery || selectedDelivery.type !== 'created' || shipBtn.disabled){
      return;
    }
    
    shipBtn.disabled = true;
    shipBtn.textContent = 'Wysyłanie...';
    
    const formData = new FormData();
    formData.append('parcel_number', selectedDelivery.id);
    
    fetch('/backend/parcel_send.php', {
      method: 'POST',
      body: formData
    })
      .then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Błąd serwera');
          });
        }
        return response.json();
      })
      .then(data => {
        if (data.error) {
          console.error('Błąd:', data.error);
          shipBtn.disabled = false;
          shipBtn.textContent = 'Wysyłka';
          return;
        }
        
        // Успешно - обновляем список
        loadDeliveries();
        selectedDelivery = null;
        shipBtn.disabled = true;
        receiveBtn.disabled = true;
      })
      .catch(error => {
        console.error('Błąd:', error);
        shipBtn.disabled = false;
        shipBtn.textContent = 'Wysyłka';
      });
  }

  function handleReceive(){
    if(!selectedDelivery || selectedDelivery.type !== 'sent' || receiveBtn.disabled){
      return;
    }
    
    receiveBtn.disabled = true;
    receiveBtn.textContent = 'Przyjmowanie...';
    
    const formData = new FormData();
    formData.append('parcel_number', selectedDelivery.id);
    
    fetch('/backend/parcel_receive.php', {
      method: 'POST',
      body: formData
    })
      .then(response => {
        if (!response.ok) {
          return response.json().then(data => {
            throw new Error(data.error || 'Błąd serwera');
          });
        }
        return response.json();
      })
      .then(data => {
        if (data.error) {
          console.error('Błąd:', data.error);
          receiveBtn.disabled = false;
          receiveBtn.textContent = 'Przyjęcie';
          return;
        }
        
        // Успешно - обновляем список
        if (data.code) {
          console.log('Przesyłka otrzymana. Kod:', data.code);
          if (data.email_sent) {
            console.log('Kod został wysłany na email klienta.');
          }
        }
        loadDeliveries();
        selectedDelivery = null;
        shipBtn.disabled = true;
        receiveBtn.disabled = true;
      })
      .catch(error => {
        console.error('Błąd:', error);
        receiveBtn.disabled = false;
        receiveBtn.textContent = 'Przyjęcie';
      });
  }

  receiveBtn.addEventListener('click', handleReceive);
  shipBtn.addEventListener('click', handleSend);

  loadDeliveries();
</script>
</body>
</html>

