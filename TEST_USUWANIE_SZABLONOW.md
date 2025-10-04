## Test usuwania szablonów email

### Problem:
Po usunięciu szablonu:
- ✅ Komunikat pokazuje się poprawnie  
- ❌ Lista szablonów nie odświeża się
- ❌ Brak domyślnie zaznaczonego szablonu po prawej

### Rozwiązanie:
1. **Naprawiona logika `$current_template`** - nie nadpisuje ustawień z POST
2. **Dodane przekierowanie JavaScript** - aktualizuje URL po 2 sekundach
3. **Walidacja istnienia szablonu** - sprawdza czy wybrany szablon istnieje
4. **Auto-wybór pierwszego szablonu** - jeśli aktualny nie istnieje

### Jak testować:
1. Przejdź do: Ustawienia → Email → Szablony emaili
2. Wybierz dowolny szablon
3. Kliknij "Usuń" i potwierdź
4. Obserwuj:
   - Komunikat sukcesu przez 2 sekundy
   - Automatyczne przekierowanie na pierwszy dostępny szablon
   - Lista odświeża się i pokazuje pierwszy szablon

### Poprawiony flow:
```
Usuń szablon → Komunikat sukcesu → Przekierowanie (2s) → Nowy URL z edit= → Lista i edytor odświeżone
```