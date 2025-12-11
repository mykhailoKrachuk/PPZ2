# Salfetka - System Zarządzania Przesyłkami

## Funkcjonalności

### Klient
- **Wyszukiwanie przesyłek** -wyszukaniу przesyłki po numerze śledzenia na stronie głównej
- **Panel klienta** - przeglądanie wszystkich swoich przesyłek z filtrowaniem i sortowaniem
- **Szczegóły przesyłki** - pełne informacje o przesyłce, historia statusów w formie osi czasu
- **Rejestracja i logowanie** - system autoryzacji z hashowaniem haseł (bcrypt)

### Pracownik
- **Tworzenie przesyłek** - tworzenie nowych przesyłek z wszystkimi danymi
- **Automatyczne przypisywanie** - system automatycznie przypisuje przesyłkę do użytkownika na podstawie numeru telefonu
- **Generowanie numeru przesyłki** - automatyczne generowanie unikalnego 15-znakowego numeru 
- **Filtrowanie i wyszukiwanie** - szybkie znajdowanie przesyłek po różnych kryteriach
- **Wydawanie przesyłek** - wydawanie przesyłek klientom z weryfikacją 6-cyfrowego kodu

### Dział Dostaw
- **Wysyłka przesyłek** - oznaczanie przesyłek jako wysłane
- **Przyjmowanie przesyłek** - rejestracja otrzymanych przesyłek z automatycznym generowaniem kodu odbioru
- **Wysyłka email z kodem** - automatyczna wysyłka 6-cyfrowego kodu odbioru na email klienta
- **Lista przesyłek** - przeglądanie przesyłek do obsługi

## Technologie

### Backend
- **PHP 8+** - język programowania
- **PostgreSQL** - baza danych (hostowana na AWS RDS)
- **PDO** - warstwa dostępu do danych z obsługą UTF-8
- **Sesje PHP** - zarządzanie autoryzacją
- **SMTP** - wysyłka email z promokodami

### Frontend
- **HTML/CSS** - struktura i style
- **JavaScript** - logika aplikacji, komunikacja z API
- **Responsive Design** - adaptacyjny interfejs dla wszystkich urządzeń
- **Modern UI** - nowoczesny interfejs z gradientami, animacjami i modalnymi oknami

## Struktura Projektu

```
PPZ2/
├── backend/                      # Backend API
│   ├── config.php                # Konfiguracja bazy danych i SMTP
│   ├── require_auth.php          # Middleware autoryzacji
│   ├── login.php                 # Logowanie użytkowników
│   ├── register.php              # Rejestracja nowych użytkowników
│   ├── logout.php                # Wylogowanie
│   ├── parcel_search.php         # Wyszukiwanie przesyłki po numerze 
│   ├── parcel_list.php           # Lista przesyłek zalogowanego użytkownika
│   ├── parcel_create.php         # Tworzenie nowej przesyłki 
│   ├── parcel_send.php           # Oznaczanie przesyłki jako wysłana 
│   ├── parcel_receive.php        # Przyjmowanie przesyłki i generowanie kodu 
│   ├── parcel_issue.php          # Wydawanie przesyłki klientowi 
│   ├── parcel_worker_list.php    # Lista przesyłek dla pracownika
│   ├── parcel_delivery_list.php  # Lista przesyłek dla działu dostaw
│   └── migrate_and_seed.php      # Migracja i seed bazy danych
│
└── Salfetka/                     # Frontend aplikacji
    ├── index.html                # Strona główna - wyszukiwanie 
    ├── login.html                # Strona logowania
    ├── register.html             # Strona rejestracji
    ├── client.php                # Panel klienta
    ├── worker.php                # Panel pracownika
    ├── delivery.php              # Panel kuriera/działu dostaw
    ├── styles.css                # Style CSS
    └── package.json              # Zależności Node.js
```

## Baza Danych

### Tabele

#### `users`
- `id` - identyfikator użytkownika (SERIAL PRIMARY KEY)
- `username` - nazwa użytkownika (unikalna)
- `password` - hash hasła (bcrypt)
- `role` - rola użytkownika (`user`, `worker`, `deliver`)
- `mail` - adres email
- `phone_number` - numer telefonu
- `name`, `surname` - imię i nazwisko
- `address`, `country`, `city`, `postal_code` - dane adresowe

#### `parcel`
- `id` - identyfikator przesyłki (SERIAL PRIMARY KEY)
- `parcel_number` - numer śledzenia 
- `user_id` - ID użytkownika
- `phone` - numer telefonu odbiorcy
- `sender_name` - imię i nazwisko nadawcy/odbiorcy
- `sender_address` - adres dostawy
- `description` - opis zawartości
- `status` - status przesyłki (`created`, `sent`, `received`, `delivered`, `canceled`)
- `size` - wymiary przesyłki
- `weight` - waga 
- `price` - cena 
- `code` - 6-cyfrowy kod odbioru (generowany przy otrzymaniu)
- `created_at` - data utworzenia (TIMESTAMP)
- `sent_at` - data wysłania (TIMESTAMP, NULL jeśli nie wysłana)
- `received_at` - data otrzymania (TIMESTAMP, NULL jeśli nie otrzymana)
- `issued_at` - data wydania klientowi (TIMESTAMP, NULL jeśli nie wydana)

## System Autoryzacji

Aplikacja wykorzystuje system ról z kontrolą dostępu:

- **user** - klient
  - Może przeglądać swoje przesyłki
  - Może wyszukiwać przesyłki po numerze 
- **worker** - pracownik
  - Może tworzyć nowe przesyłki
  - Może przeglądać przesyłki gotowe do wydania 
  - Może wydawać przesyłki klientom
  
- **deliver** - kurier/dział dostaw
  - Może przeglądać przesyłki do obsługi
  - Może oznaczać przesyłki jako wysłane
  - Może przyjmować przesyłki i generować kody odbioru

Hasła są hashowane przy użyciu `password_hash()` z algorytmem bcrypt.

## Instalacja i Uruchomienie

### Wymagania
- PHP 8.0 lub nowszy
- PostgreSQL 12+ (lub dostęp do AWS RDS)
- Serwer web (Apache/Nginx) z obsługą PHP
- Opcjonalnie: Node.js 16+ i npm (dla frontendu, jeśli używany Webpack)


##  API Endpoints

### Publiczne
- `GET /backend/parcel_search.php?number={numer}` - wyszukiwanie przesyłki po numerze śledzenia
  - Zwraca: szczegóły przesyłki, historię statusów, dane nadawcy

### Wymagające autoryzacji

#### Autoryzacja
- `POST /backend/login.php` - logowanie użytkownika
- `POST /backend/register.php` - rejestracja nowego użytkownika
- `POST /backend/logout.php` - wylogowanie

#### Klient
- `GET /backend/parcel_list.php` - lista wszystkich przesyłek zalogowanego użytkownika
  - Zwraca: przesyłki z historią statusów, filtrowane po user_id

#### Pracownik
- `GET /backend/parcel_worker_list.php` - lista przesyłek gotowych do wydania
  - Zwraca: przesyłki z `received_at IS NOT NULL` i `issued_at IS NULL`
  
- `POST /backend/parcel_create.php` - tworzenie nowej przesyłki
  - Parametry: `receiver_name`, `receiver_address`, `receiver_phone`, `description`, `size`, `weight`, `price`
  - Automatycznie: generuje numer przesyłki, przypisuje do użytkownika po numerze telefonu
  
- `POST /backend/parcel_issue.php` - wydawanie przesyłki klientowi
  - Parametry: `parcel_number`, `code` (6-cyfrowy kod)
  - Aktualizuje: `issued_at = NOW()`, `status = 'delivered'`

#### Dział Dostaw 
- `GET /backend/parcel_delivery_list.php` - lista przesyłek do obsługi
  - Zwraca: przesyłki z `created_at IS NOT NULL` i `sent_at IS NULL` 
  - oraz przesyłki z `sent_at IS NOT NULL` i `received_at IS NULL` 
- `POST /backend/parcel_send.php` - oznaczanie przesyłki jako wysłana
  - Parametry: `parcel_number`
  - Aktualizuje: `sent_at = NOW()`, `status = 'sent'`
  
- `POST /backend/parcel_receive.php` - przyjmowanie przesyłki
  - Parametry: `parcel_number`
  - Generuje: 6-cyfrowy kod odbioru
  - Wysyła: email z kodem na adres klienta (jeśli SMTP skonfigurowane)
  - Aktualizuje: `received_at = NOW()`, `status = 'received'`, `code = {generated_code}`

## Workflow Przesyłek

Przesyłki przechodzą przez następujący cykl życia:

1. **Utworzona** (`created`)
   - Pracownik tworzy przesyłkę w systemie
   - Status: `created`
   - `created_at` = NOW()
   - `sent_at` = NULL, `received_at` = NULL, `issued_at` = NULL

2. **Wysłana** (`sent`)
   - Dział dostaw oznacza przesyłkę jako wysłaną
   - Status: `sent`
   - `sent_at` = NOW()

3. **Otrzymana** (`received`)
   - Dział dostaw przyjmuje przesyłkę w punkcie odbioru
   - Status: `received`
   - `received_at` = NOW()
   - Generowany 6-cyfrowy kod odbioru
   - Email z kodem wysyłany na adres klienta

4. **Wydana** (`delivered`)
   - Pracownik wydaje przesyłkę klientowi po weryfikacji kodu
   - Status: `delivered`
   - `issued_at` = NOW()

## System Email

Aplikacja automatycznie wysyła email z 6-cyfrowym kodem odbioru, gdy przesyłka zostaje otrzymana w punkcie odbioru.

### Konfiguracja Email

Email jest wysyłany tylko jeśli:
- `SMTP_ENABLED = true` w `config.php`
- Przesyłka ma przypisanego użytkownika (`user_id IS NOT NULL`)
- Użytkownik ma adres email w bazie danych

### Format Email

Email zawiera:
- Przywitanie z imieniem klienta
- Numer przesyłki
- 6-cyfrowy kod odbioru (wyróżniony wizualnie)
- Informację o ważności kodu
- Profesjonalny HTML template z brandingiem Salfetka

## Interfejs Użytkownika

Aplikacja oferuje nowoczesny, responsywny interfejs z:

- **Intuicyjną nawigacją** - spójny header na wszystkich stronach
- **Kolorowymi znacznikami statusów** - wizualne rozróżnienie statusów przesyłek
- **Filtrowaniem i sortowaniem** - szybkie znajdowanie przesyłek
- **Szczegółowymi widokami** - pełne informacje o przesyłce w modalnych oknach
- **Historią zmian statusów** - oś czasu pokazująca wszystkie etapy przesyłki
- **Responsywnym designem** - optymalizacja dla urządzeń mobilnych
- **Animacjami i przejściami** - płynne interakcje użytkownika

## Bezpieczeństwo

- Hasła hashowane przy użyciu bcrypt
- Autoryzacja oparta na sesjach PHP
- Kontrola dostępu oparta na rolach
- Walidacja danych wejściowych
- Prepared statements (PDO) zapobiegające SQL injection
- Kodowanie UTF-8 dla wszystkich danych

## Uwagi Techniczne

- Wszystkie dane są pobierane z bazy danych PostgreSQL
- Numer przesyłki jest generowany automatycznie (15 znaków: A-Z, 0-9)
- Kod odbioru jest generowany losowo (6 cyfr)
- Email jest wysyłany asynchronicznie (nie blokuje operacji)
- Baza danych używa kodowania UTF-8 dla obsługi polskich znaków

## Autorzy

Projekt został stworzony przez zespół deweloperski w ramach przedmiotu PPZ2.
SCRUM - Mychailo Kravchuk
Backend - Danylo Rudenko
Frontend - Maksym Melnyk
Frontend/Backend - Oleksii Melnyk
---

**Wersja:** 1.0  
**Ostatnia aktualizacja:** Grudzień 2025
