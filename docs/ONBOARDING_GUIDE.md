# 🚀 Rental System - Przewodnik Onboardingowy

> **Kompletny przewodnik dla nowych programistów rozpoczynających pracę z systemem zarządzania wynajmem pojazdów**

---

## 📋 Spis treści

1. [Przegląd systemu](#-przegląd-systemu)
2. [Technologie i architektura](#-technologie-i-architektura)
3. [Struktura projektu](#-struktura-projektu)
4. [Konfiguracja środowiska](#-konfiguracja-środowiska)
5. [Kluczowe komponenty](#-kluczowe-komponenty)
6. [Przepływy danych](#-przepływy-danych)
7. [Standardy kodowania](#-standardy-kodowania)
8. [Częste problemy i rozwiązania](#-częste-problemy-i-rozwiązania)
9. [Testowanie i debugowanie](#-testowanie-i-debugowanie)
10. [Zasoby i dalsze kroki](#-zasoby-i-dalsze-kroki)

---

## 🎯 Przegląd systemu

### **Czym jest Rental System?**
Kompleksowy system zarządzania wynajmem pojazdów obejmujący:

- **Frontend klienta**: Wyszukiwanie, rezerwacja pojazdów
- **Panel administracyjny**: Zarządzanie flotą, rezerwacjami, raportami
- **Fleet Management**: Lokalizacje, opłaty, kaucje
- **System użytkowników**: Role (admin, staff, client)
- **Wielojęzyczność**: Polski/Angielski
- **Responsywny design**: Bootstrap 5

### **Główne funkcjonalności**
- ✅ Zarządzanie modelami pojazdów i egzemplarzami
- ✅ System rezerwacji z kalendarzem
- ✅ Fleet Management (lokalizacje, opłaty)
- ✅ System kaucji (fixed/percentage)
- ✅ Dodatki do wynajmu
- ✅ Promocje i rabaty
- ✅ Raportowanie i analityka
- ✅ Wielojęzyczne szablony email
- ✅ Słowniki i konfiguracja

---

## 🔧 Technologie i architektura

### **Stack technologiczny**
```
Frontend:  HTML5, CSS3, JavaScript ES6+, Bootstrap 5, FontAwesome
Backend:   PHP 8.0+, MySQL 8.0+
Styling:   SCSS → CSS (kompilacja npm)
Tools:     npm, Sass, Git
Server:    Apache/Nginx, XAMPP (dev)
```

### **Wzorce architektoniczne**
- **MVC**: Separacja logiki, widoków i danych
- **Include-based**: Modularna struktura plików
- **Class-based**: OOP dla logiki biznesowej
- **Database-first**: Schema-driven development
- **Component-based**: Reużywalne komponenty UI

### **Bezpieczeństwo**
- Prepared statements (PDO)
- XSS prevention (htmlspecialchars)
- Role-based access control
- Session management
- CSRF protection (w kluczowych miejscach)

---

## 📁 Struktura projektu

```
rental/
├── 📁 api/                     # API endpoints
├── 📁 assets/                  # Zasoby statyczne
│   ├── css/                   # Skompilowane CSS
│   ├── scss/                  # Źródłowe pliki SCSS
│   ├── js/                    # JavaScript
│   └── img/                   # Obrazy
├── 📁 auth/                    # Autoryzacja i autentykacja
├── 📁 classes/                 # Klasy PHP (logika biznesowa)
│   ├── FleetManager.php       # Zarządzanie flotą
│   ├── DepositManager.php     # System kaucji
│   └── LocationFeeManager.php # Opłaty lokalizacyjne
├── 📁 components/              # Komponenty UI
├── 📁 config/                  # Konfiguracja
├── 📁 database/                # Skrypty SQL
├── 📁 docs/                    # Dokumentacja
├── 📁 includes/                # Wspólne pliki PHP
│   ├── config.php             # Konfiguracja DB
│   ├── db.php                 # Połączenie z bazą
│   ├── i18n.php               # Międzynarodowość
│   └── lang/                  # Tłumaczenia
├── 📁 pages/                   # Strony aplikacji
│   ├── staff/                 # Panel administratora
│   └── *.php                  # Strony klienta
├── 📁 partials/                # Części wspólne (header, footer)
├── 📁 scripts/                 # Narzędzia deweloperskie
│   ├── tests/                 # Testy funkcjonalności
│   ├── checks/                # Sprawdzenia systemu
│   └── migrations/            # Migracje bazy
└── 📁 uploads/                 # Przesłane pliki
```

---

## ⚙️ Konfiguracja środowiska

### **1. Wymagania systemowe**
```bash
# Minimalne wymagania
PHP 8.0+
MySQL 8.0+
Apache/Nginx
Node.js 16+ (dla kompilacji SCSS)

# XAMPP (zalecane dla developmentu)
PHP 8.0+, MySQL, Apache w jednym pakiecie
```

### **2. Konfiguracja bazy danych**
```php
// includes/config.php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'rental');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/rental'); // Dostosuj do środowiska
```

### **3. Instalacja dependencies**
```bash
# W katalogu projektu
npm install

# Kompilacja SCSS (development)
npm run watch

# Kompilacja SCSS (production)
npm run build
```

### **4. Setup bazy danych**
```sql
-- Utwórz bazę danych
CREATE DATABASE rental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Wykonaj migracje (po kolei):
SOURCE database/database_fleet_basic.sql;
SOURCE database/database_email_settings.sql;
SOURCE database/database_addons.sql;
-- itp...
```

### **5. Pierwszy login**
```php
// Domyślne konto administratora
Email: admin@test.com
Hasło: admin123

// Lub stwórz nowe przez:
scripts/seeders/add_test_data.php
```

---

## 🧩 Kluczowe komponenty

### **1. System autoryzacji**
```php
// auth/auth.php - sprawdzanie uprawnień
require_once dirname(__DIR__) . '/auth/auth.php';
require_staff(); // Tylko staff/admin
require_admin(); // Tylko admin
```

### **2. Połączenie z bazą danych**
```php
// includes/db.php
$db = db(); // Zwraca PDO instance
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
```

### **3. Międzynarodowość**
```php
// includes/i18n.php
i18n::init();
echo __('welcome_message', 'frontend', 'Witaj!');
// Pliki: includes/lang/pl/frontend.php, includes/lang/en/frontend.php
```

### **4. Klasy biznesowe**
```php
// classes/FleetManager.php
$fleet = new FleetManager();
$locations = $fleet->getLocations();
$fee = $fleet->calculateLocationFee($from, $to);

// classes/DepositManager.php  
$deposit = new DepositManager();
$amount = $deposit->calculateDeposit($vehicle_id, $rental_days);

// classes/LocationFeeManager.php
$fees = new LocationFeeManager();
$cost = $fees->getFee($pickup_id, $return_id);
```

### **5. Routing i strony**
```php
// index.php - główny router
$page = $_GET['page'] ?? 'home';
switch ($page) {
    case 'dashboard-staff':
        require 'pages/dashboard-staff.php';
        break;
    case 'checkout':
        require 'pages/checkout.php';
        break;
    // ...
}
```

---

## 🔄 Przepływy danych

### **1. Rezerwacja pojazdu (klient)**
```
1. Wyszukiwanie → pages/search-results.php
2. Szczegóły → pages/product-details.php  
3. Rezerwacja → pages/reserve.php
4. Checkout → pages/checkout.php
5. Potwierdzenie → pages/checkout-confirm.php
6. Email + redirect → sukces
```

### **2. Zarządzanie flotą (admin)**
```
1. Dashboard → pages/dashboard-staff.php
2. Zakładka Pojazdy → pages/staff/section-vehicles.php
3. Dodaj/Edytuj → pages/vehicle-form.php
4. Zapisz → pages/vehicle-save.php
5. Przeglądaj egzemplarze → pages/vehicles-manage.php
```

### **3. Konfiguracja systemu**
```
1. Dashboard → Ustawienia
2. Sekcje: pages/staff/section-settings.php
3. Email templates → pages/staff/settings/email-templates.php
4. SMTP config → pages/staff/settings/email-smtp.php
5. Płatności → pages/staff/settings/payments-*.php
```

### **4. Baza danych - relacje**
```sql
products (modele) ← 1:N → vehicles (egzemplarze)
vehicles ← 1:N → reservations
locations ← 1:N → vehicles (current_location_id)
locations ← N:M → location_fees (opłaty między lokalizacjami)
users ← 1:N → reservations
dict_terms → products (car_class, car_type, fuel_type)
```

---

## 📝 Standardy kodowania

### **1. Konwencje nazewnictwa**
```php
// Pliki
kebab-case: product-form.php, vehicle-save.php
camelCase: FleetManager.php (klasy)

// Funkcje i zmienne
snake_case: $user_id, get_vehicle_list()
camelCase: calculateDeposit() (metody klas)

// Tabele i kolumny
snake_case: vehicle_location_history, created_at
```

### **2. Struktura plików PHP**
```php
<?php
// 1. Require dependencies
require_once dirname(__DIR__) . '/auth/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';

// 2. Authorization check
require_staff();

// 3. Variables and logic
$db = db();
$vehicles = $db->query("SELECT * FROM vehicles")->fetchAll();

// 4. HTML structure
?>
<!DOCTYPE html>
<html>
<head>
    <title>Panel</title>
</head>
<body>
    <!-- Content -->
</body>
</html>
```

### **3. Bezpieczeństwo - Best practices**
```php
// ✅ DOBRZE - Prepared statements
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);

// ✅ DOBRZE - Escape output
echo htmlspecialchars($user_name);

// ✅ DOBRZE - Walidacja input
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid ID');
}

// ❌ ŹLE - Direct query
$result = $db->query("SELECT * FROM users WHERE id = $user_id");

// ❌ ŹLE - No escaping
echo $_POST['name'];
```

### **4. Styling - SCSS struktura**
```scss
// assets/scss/main.scss
@import 'variables';     // Kolory, fonty
@import 'layout';        // Layout strony
@import 'components';    // Komponenty UI
@import 'pages/dashboard-staff'; // Style specyficzne dla stron
```

### **5. JavaScript standardy**
```javascript
// ✅ Modern ES6+
const vehicles = await fetch('/api/vehicles').then(r => r.json());

// ✅ Event listeners
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', handleDelete);
});

// ✅ Error handling
try {
    await saveVehicle(data);
} catch (error) {
    console.error('Save failed:', error);
    showErrorMessage('Błąd zapisu');
}
```

---

## ⚠️ Częste problemy i rozwiązania

### **1. Navigation menu nie działa**
```javascript
// Problem: Bootstrap Tab conflicts, z-index issues
// Rozwiązanie: Sprawdź z-index hierarchy

// dashboard-staff.php - navigation buttons muszą mieć wyższy z-index
.nav-link-custom {
    z-index: 1100 !important;
    position: relative !important;
    pointer-events: auto !important;
}

// header.php - navbar niższy z-index  
.dashboard-nav-fix {
    z-index: 1000 !important;
}
```

### **2. Email templates nie ładują się**
```php
// Problem: PHP syntax errors w email-templates.php
// Rozwiązanie: Sprawdź syntax errors

// Sprawdź logi PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug email-templates.php
echo '<script>console.log("EMAIL-TEMPLATES.PHP LOADED!");</script>';
```

### **3. Database connection fails**
```php
// Problem: Błędne credentials lub charset
// Rozwiązanie: includes/config.php

define('DB_HOST', '127.0.0.1');     // nie 'localhost'
define('DB_CHARSET', 'utf8mb4');    // ważne dla polskich znaków
```

### **4. SCSS nie kompiluje się**
```bash
# Problem: Node.js dependencies
# Rozwiązanie:
npm install
npm run build

# Sprawdź ścieżki w package.json
"build": "sass assets/scss:assets/css --style=compressed"
```

### **5. Modal backdrop blokuje kliknięcia**
```javascript
// Problem: Bootstrap modal backdrop nie jest usuwany
// Rozwiązanie: Automatic cleanup

function removeModalBackdrop() {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    document.body.classList.remove('modal-open');
}

// Uruchom co 2 sekundy
setInterval(removeModalBackdrop, 2000);
```

---

## 🧪 Testowanie i debugowanie

### **1. Testy struktury systemu**
```bash
# Sprawdź bazę danych
php scripts/checks/check_database_structure.php

# Test logowania i dashboard
php scripts/tests/test_login_dashboard.php

# Test fleet management
php scripts/tests/test_system_naprawiony.php
```

### **2. Debug tools**
```php
// W development - włącz debug
define('APP_DEBUG', true);

// Log do konsoli JavaScript
echo '<script>console.log(' . json_encode($data) . ');</script>';

// Log do pliku
error_log("Debug data: " . print_r($data, true));

// Sprawdź SQL queries
$stmt = $db->prepare("SELECT * FROM vehicles");
echo "Query: " . $stmt->queryString;
```

### **3. Seeding danych testowych**
```bash
# Dodaj dane testowe
php scripts/seeders/add_test_data.php

# Sprawdź strukture tabel
php scripts/checks/check_structure.php
```

### **4. Browser debugging**
```javascript
// Console commands dla testowania
console.log('Fleet locations:', window.fleetData);

// Sprawdź Bootstrap components
var tabs = bootstrap.Tab.getInstance(document.querySelector('.nav-link-custom'));
console.log('Tab instance:', tabs);

// Test AJAX requests
fetch('/api/vehicles').then(r => r.json()).then(console.log);
```

---

## 📚 Zasoby i dalsze kroki

### **1. Dokumentacja projektu**
- `docs/README.md` - Główny przegląd
- `docs/FLEET_MANAGEMENT_COMPLETED.md` - Fleet Management
- `docs/STANDARD_STYLISTYCZNY.md` - Standardy UI/UX
- `docs/CHANGELOG.md` - Historia zmian

### **2. Kluczowe pliki do zrozumienia**
```
📖 PODSTAWY:
includes/config.php - Konfiguracja
includes/db.php - Baza danych  
index.php - Router główny
auth/auth.php - Autoryzacja

🎨 UI/LAYOUT:
partials/header.php - Header z navigation
partials/footer.php - Footer
assets/scss/ - Style SCSS

🏢 BACKEND LOGIC:
classes/FleetManager.php - Logika fleet
pages/dashboard-staff.php - Panel główny
pages/checkout.php - Proces rezerwacji

📧 EMAIL SYSTEM:
pages/staff/settings/email-templates.php
includes/lang/ - Tłumaczenia
```

### **3. Workflow development**
```bash
# 1. Sklonuj/zaktualizuj repo
git pull origin main

# 2. Upewnij się że SCSS compiles
npm run build

# 3. Sprawdź bazę danych
php scripts/checks/check_database_structure.php

# 4. Testuj funkcjonalność
php scripts/tests/test_login_dashboard.php

# 5. Rozpocznij development
npm run watch  # auto-compile SCSS
```

### **4. Następne kroki dla nowego programisty**

**Dzień 1-2: Podstawy**
- [ ] Sklonuj projekt i skonfiguruj środowisko
- [ ] Uruchom bazę danych i seeding
- [ ] Zaloguj się jako admin i przejrzyj dashboard
- [ ] Przeczytaj dokumentację w `docs/`

**Dzień 3-5: Zrozumienie kodu**  
- [ ] Przeanalizuj strukturę `pages/dashboard-staff.php`
- [ ] Sprawdź jak działają klasy w `classes/`
- [ ] Zrozum routing w `index.php`
- [ ] Przetestuj proces rezerwacji end-to-end

**Tydzień 2: Pierwsze zadania**
- [ ] Dodaj nowe pole do formularza pojazdu
- [ ] Stwórz nową sekcję w panelu admin
- [ ] Zmodyfikuj szablon email
- [ ] Napisz test dla nowej funkcjonalności

**Miesiąc 1: Zaawansowane**
- [ ] Zaimplementuj nową funkcjonalność biznesową
- [ ] Optymalizuj zapytania SQL
- [ ] Dodaj nowy endpoint API
- [ ] Popraw UX/UI w jednej z sekcji

---

## 🆘 Pomoc i wsparcie

### **Gdzie szukać pomocy:**

1. **Dokumentacja lokalna**: `docs/` folder
2. **Testy systemowe**: `scripts/tests/`
3. **Debug tools**: Konsola przeglądarki + PHP error logs
4. **Code examples**: Istniejące pliki w `pages/` jako wzorce

### **Typowy dzień pracy:**

```bash
# Rano - sprawdź status
git status
php scripts/checks/check_database_structure.php

# Development - watch SCSS
npm run watch

# Debug - sprawdź logi
tail -f /path/to/php/error.log

# Przed commitem - build production
npm run build
git add .
git commit -m "feat: dodano nową funkcjonalność"
```

---

**🎉 Gratulacje! Jesteś gotowy do pracy z Rental System!**

> **Pamiętaj**: Ten system to żywy projekt. Dokumentacja może być aktualizowana. Przy problemach sprawdź zawsze najnowszą wersję w `docs/` oraz logi systemowe.

---

**📧 Kontakt**: W razie pytań, sprawdź istniejący kod jako przykład lub skonsultuj z zespołem.

**🔄 Ostatnia aktualizacja**: Październik 2025 | **Wersja**: 1.0.0