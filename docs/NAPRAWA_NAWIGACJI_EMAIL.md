## Naprawa nawigacji w szablonach email

### Problem:
Klikanie na linki w sekcji "Szablony email" (Anulowanie rezerwacji, Potwierdzenie płatności itp.) przenosi na stronę główną zamiast przełączać między szablonami.

### Rozwiązanie:
1. **Dodano funkcję `settings_url()`** w `email-templates.php`:
   - Generuje poprawne linki z pełną ścieżką URL
   - Zachowuje wszystkie parametry nawigacji
   - Dodaje hash `#pane-settings` dla Bootstrap tabs

2. **Naprawiono linki w liście szablonów**:
   - Zastąpiono błędne URL względne linkami bezwzględnymi
   - Używa globalnej zmiennej `$BASE` dla poprawnej ścieżki

3. **Poprawiono JavaScript dla przełączania statusu**:
   - Dodano obsługę błędów w `toggleTemplate()`
   - Używa aktualnego URL dla zachowania kontekstu nawigacji

### Testowanie:
```
http://localhost:8080/index.php?page=dashboard-staff&section=settings&settings_section=email&settings_subsection=templates#pane-settings
```

### Lista szablonów do testowania:
- ✅ Potwierdzenie rezerwacji (`booking_confirmation`)
- ✅ Anulowanie rezerwacji (`booking_cancellation`) 
- ✅ Potwierdzenie płatności (`payment_confirmation`)
- ✅ Przypomnienie o rezerwacji (`reminder`)

### Struktura linków:
```
/index.php?page=dashboard-staff&section=settings&settings_section=email&settings_subsection=templates&edit={template_key}#pane-settings
```

Wszystkie pliki przeszły test składni PHP i są gotowe do użycia.