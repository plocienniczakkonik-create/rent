# 🚀 Rental System - Quick Reference

> **Cheat sheet dla programistów - najważniejsze informacje w jednym miejscu**

---

## ⚡ Szybki start (5 minut)

```bash
# 1. Setup
npm install && npm run build

# 2. Database
mysql -u root -p
CREATE DATABASE rental;
SOURCE database/database_fleet_basic.sql;

# 3. Login
http://localhost/rental/index.php?page=login
admin@test.com / admin123

# 4. Development
npm run watch  # auto-compile SCSS
```

---

## 📁 Najważniejsze pliki

```
🔧 KONFIGURACJA:
includes/config.php         # DB credentials, BASE_URL
includes/db.php             # PDO connection
package.json                # npm scripts

🔐 AUTORYZACJA:
auth/auth.php               # require_staff(), require_admin()

🎨 FRONTEND:
partials/header.php         # Navigation menu
assets/scss/main.scss       # Style główne
pages/dashboard-staff.php   # Panel administratora

🏢 BACKEND:
classes/FleetManager.php    # Zarządzanie flotą
classes/DepositManager.php  # System kaucji
pages/checkout.php          # Proces rezerwacji

📧 EMAIL:
pages/staff/settings/email-templates.php
includes/lang/pl/admin.php  # Tłumaczenia
```

---

## 🔧 Częste komendy

```bash
# SCSS
npm run watch              # Development (auto-compile)
npm run build             # Production (compressed)

# Database
mysql -u root rental      # Connect to DB
php scripts/checks/check_database_structure.php

# Tests
php scripts/tests/test_login_dashboard.php
php scripts/tests/test_system_naprawiony.php
```

---

## 🎯 Typowe zadania

### **Dodaj nowe pole do formularza pojazdu**
```php
# 1. Database
ALTER TABLE vehicles ADD COLUMN new_field VARCHAR(100);

# 2. Form (pages/vehicle-form.php)
<input type="text" name="new_field" class="form-control">

# 3. Save (pages/vehicle-save.php)
$new_field = $_POST['new_field'] ?? '';
$stmt = $db->prepare("UPDATE vehicles SET new_field = ? WHERE id = ?");
```

### **Stwórz nową sekcję w dashboard**
```php
# 1. Navigation (pages/dashboard-staff.php)
<button class="nav-link-custom" data-bs-target="#pane-newsection">
    <i class="bi bi-icon"></i> Nowa Sekcja
</button>

# 2. Content
<div class="tab-pane fade" id="pane-newsection">
    <?php include __DIR__ . '/staff/section-newsection.php'; ?>
</div>

# 3. Create file
pages/staff/section-newsection.php
```

### **Dodaj nowy endpoint API**
```php
# 1. Create file: api/new-endpoint.php
<?php
require_once dirname(__DIR__) . '/includes/db.php';
header('Content-Type: application/json');

$data = ['status' => 'success'];
echo json_encode($data);

# 2. JavaScript call
fetch('/rental/api/new-endpoint.php')
    .then(r => r.json())
    .then(data => console.log(data));
```

---

## 🐛 Debug commands

```php
# PHP Debug
error_log("Debug: " . print_r($data, true));
echo '<script>console.log(' . json_encode($data) . ');</script>';

# Database Debug  
echo $stmt->queryString;  # Show SQL query
var_dump($stmt->errorInfo());  # Show SQL errors

# JavaScript Debug
console.log('Data:', data);
console.table(arrayData);
debugger;  # Breakpoint
```

---

## ⚠️ Problem fixing

```php
# Navigation not working
# Fix: z-index hierarchy in dashboard-staff.php
.nav-link-custom { z-index: 1100 !important; }

# Email templates not loading  
# Fix: Check PHP syntax errors
echo '<script>console.log("LOADED");</script>';

# Database connection failed
# Fix: includes/config.php
define('DB_HOST', '127.0.0.1');  // not 'localhost'

# SCSS not compiling
npm install && npm run build

# Modal backdrop blocking clicks
document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
```

---

## 📊 Database quick reference

```sql
-- Key tables
products           # Modele pojazdów  
vehicles           # Egzemplarze (+ current_location_id)
reservations       # Rezerwacje (+ fleet management fields)
locations          # Lokalizacje odbioru/zwrotu
location_fees      # Opłaty między lokalizacjami (symetryczne)
users              # Użytkownicy (role: admin, staff, client)
dict_terms         # Słowniki (car_class, car_type, fuel_type)

-- Common queries
SELECT v.*, p.name FROM vehicles v JOIN products p ON v.product_id = p.id;
SELECT * FROM reservations WHERE status = 'active';
SELECT * FROM location_fees WHERE from_location_id = 1;
```

---

## 🎨 UI patterns

```html
<!-- Page header -->
<div class="dashboard-header p-4 rounded-4 mb-4" 
     style="background: var(--gradient-primary); color: white;">
    <h2><i class="fas fa-icon me-3"></i>Tytuł Sekcji</h2>
</div>

<!-- Form section -->
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-info-circle me-2"></i>Podstawowe informacje</h5>
    </div>
    <div class="card-body">
        <!-- fields -->
    </div>
</div>

<!-- Action buttons -->
<button class="btn btn-primary">
    <i class="bi bi-check-lg"></i> Zapisz
</button>
<button class="btn btn-clean">
    <i class="bi bi-arrow-left"></i> Wstecz  
</button>
```

---

## 🔐 Security checklist

```php
# ✅ DO
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
echo htmlspecialchars($user_input);
require_staff(); // Authorization

# ❌ DON'T  
$db->query("SELECT * FROM users WHERE id = $user_id");
echo $_POST['input'];
// No authorization check
```

---

## 📱 Responsive breakpoints

```scss
// Mobile first approach
.element {
    // Mobile styles (default)
    
    @media (min-width: 768px) {
        // Tablet styles
    }
    
    @media (min-width: 992px) {
        // Desktop styles  
    }
}
```

---

## 🔄 Git workflow

```bash
# Feature development
git checkout -b feature/new-functionality
# ... development work ...
git add .
git commit -m "feat: dodano nową funkcjonalność"
git push origin feature/new-functionality

# Before merge
npm run build  # Compile SCSS for production
git add assets/css/
git commit -m "build: compiled SCSS for production"
```

---

## 📞 Emergency contacts

- **Documentation**: `docs/` folder
- **Tests**: `scripts/tests/`  
- **Examples**: Existing files in `pages/`
- **Error logs**: Browser console + PHP error log

---

**💡 Pro tip**: When stuck, look at similar existing functionality in the codebase first!

**🚀 Happy coding!**