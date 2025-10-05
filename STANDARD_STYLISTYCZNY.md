# Standard Stylistyczny Projektu Rental

## Spójny Design System dla Całego Projektu

### 1. Główne Nagłówki Stron (Page Headers)

**Zastosowanie:** Główny tytuł strony/sekcji z ID lub nazwą obiektu

```html
<!-- Przykład: Nagłówek strony vehicle-detail -->
<div class="card mb-4">
    <div class="card-header text-white d-flex align-items-center justify-content-between" 
         style="background: var(--gradient-primary); border: none;">
        <h1 class="mb-0 d-flex align-items-center">
            <i class="fas fa-car me-3"></i>
            Pojazd
        </h1>
        <span class="badge bg-light text-dark fs-6">ID: 25</span>
    </div>
</div>

<!-- Przykład: Nagłówek formularza vehicle-form -->
<div class="card mb-4">
    <div class="card-header text-white d-flex align-items-center justify-content-between" 
         style="background: var(--gradient-primary); border: none;">
        <h1 class="mb-0 d-flex align-items-center">
            <i class="fas fa-edit me-3"></i>
            Edytuj pojazd
        </h1>
        <span class="badge bg-light text-dark fs-6">ID: 25</span>
    </div>
</div>
```

**Charakterystyka:**
- Tło: `var(--gradient-primary)` (brandowy gradient)
- Tekst: biały
- Ikona FontAwesome przed tytułem
- Badge z ID po prawej stronie
- Brak border w card-header

### 2. Nagłówki Sekcji (Section Headers)

**Zastosowanie:** Nagłówki dla poszczególnych sekcji wewnątrz strony

```html
<div class="card mb-4">
    <div class="card-header" style="background: white; border-bottom: 1px solid #6b7280;">
        <h5 class="mb-0 d-flex align-items-center">
            <i class="fas fa-info-circle me-2"></i>
            Podstawowe informacje
        </h5>
    </div>
    <div class="card-body">
        <!-- Zawartość sekcji -->
    </div>
</div>
```

**Charakterystyka:**
- Tło: białe (`background: white`)
- Dolna linia: cienka szara (`border-bottom: 1px solid #6b7280`)
- Ikona FontAwesome przed tytułem
- Tytuł: `h5` z `mb-0`
- Margines dolny karty: `mb-4`

### 3. Zestaw Ikon dla Różnych Sekcji

```html
<!-- Podstawowe informacje -->
<i class="fas fa-info-circle me-2"></i>

<!-- Stan i lokalizacja -->
<i class="fas fa-tachometer-alt me-2"></i>

<!-- Terminy -->
<i class="fas fa-calendar-alt me-2"></i>

<!-- Notatki -->
<i class="fas fa-sticky-note me-2"></i>

<!-- Metryka pojazdu -->
<i class="fas fa-clipboard-list me-2"></i>

<!-- Usługi -->
<i class="fas fa-tools me-2"></i>

<!-- Incydenty -->
<i class="fas fa-exclamation-triangle me-2"></i>

<!-- Statystyki -->
<i class="fas fa-chart-bar me-2"></i>

<!-- Koszty kolizji -->
<i class="fas fa-dollar-sign me-2"></i>

<!-- Pojazd/Auto -->
<i class="fas fa-car me-3"></i>

<!-- Edycja -->
<i class="fas fa-edit me-3"></i>

<!-- Dodawanie -->
<i class="fas fa-plus me-3"></i>
```

### 4. Struktura Kart (Cards)

**Standardowa karta sekcji:**

```html
<div class="card mb-4">
    <div class="card-header" style="background: white; border-bottom: 1px solid #6b7280;">
        <h5 class="mb-0 d-flex align-items-center">
            <i class="fas fa-[ikona] me-2"></i>
            [Nazwa sekcji]
        </h5>
    </div>
    <div class="card-body">
        <!-- Zawartość -->
    </div>
</div>
```

### 5. Formularie - Layout Sekcji

**Podział formularza na logiczne sekcje:**

1. **Podstawowe informacje** - dane identyfikacyjne (model, nr rejestracyjny, VIN, status)
2. **Stan i lokalizacja** - przebieg, lokalizacja, stan techniczny
3. **Terminy** - daty przeglądów, ubezpieczeń, certyfikatów
4. **Notatki** - dodatkowe informacje opisowe

### 6. Zmienne CSS do Kolorów

**Używamy systemu CSS Variables:**

```css
:root {
    --color-primary: #8b5cf6;           /* Brandowy fiolet */
    --color-primary-dark: #7c3aed;     /* Ciemniejszy fiolet */
    --gradient-primary: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}
```

**Integracja z ThemeConfig:**
- Kolory są konfigurowalne przez panel admina
- Automatyczne generowanie CSS variables
- Wsparcie dla custom brandingu

### 7. Responsive Design

**Bootstrap classes dla responsywności:**
- `col-md-6` dla pól formularza
- `col-md-4` dla węższych pól
- `col-md-3` dla krótkich pól (status, liczby)
- `col-12` dla pełnej szerokości (textarea, długie pola)

### 8. Przykład Kompletnej Strony

```php
<?php
// Nagłówek strony z brandowym gradientem
?>
<div class="card mb-4">
    <div class="card-header text-white d-flex align-items-center justify-content-between" 
         style="background: var(--gradient-primary); border: none;">
        <h1 class="mb-0 d-flex align-items-center">
            <i class="fas fa-car me-3"></i>
            [Nazwa obiektu]
        </h1>
        <span class="badge bg-light text-dark fs-6">ID: [id]</span>
    </div>
</div>

<?php
// Sekcje z białymi nagłówkami i szarą linią
?>
<div class="card mb-4">
    <div class="card-header" style="background: white; border-bottom: 1px solid #6b7280;">
        <h5 class="mb-0 d-flex align-items-center">
            <i class="fas fa-info-circle me-2"></i>
            Sekcja 1
        </h5>
    </div>
    <div class="card-body">
        <!-- Zawartość -->
    </div>
</div>

<div class="card mb-4">
    <div class="card-header" style="background: white; border-bottom: 1px solid #6b7280;">
        <h5 class="mb-0 d-flex align-items-center">
            <i class="fas fa-calendar-alt me-2"></i>
            Sekcja 2
        </h5>
    </div>
    <div class="card-body">
        <!-- Zawartość -->
    </div>
</div>
```

## Zastosowanie w Przyszłych Stronach

1. **Zawsze** używaj głównego nagłówka z gradientem dla strony
2. **Zawsze** dziel zawartość na logiczne sekcje z białymi nagłówkami
3. **Zawsze** używaj odpowiednich ikon FontAwesome
4. **Zawsze** stosuj spójne marginesy (`mb-4` dla kart)
5. **Zawsze** używaj zmiennych CSS zamiast hardkodowanych kolorów

Ten standard zapewnia:
- ✅ Spójność wizualną w całym projekcie
- ✅ Łatwość utrzymania i rozwoju
- ✅ Brandowanie z możliwością customizacji
- ✅ Responsywność na wszystkich urządzeniach
- ✅ Przyjazność dla użytkownika