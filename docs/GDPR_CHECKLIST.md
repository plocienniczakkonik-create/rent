# GDPR/RODO Checklist – wdrożenie w systemie rental

## 1. Zgody użytkownika
- [x] Rejestracja zgód w bazie (user_consents)
- [x] UI do zarządzania zgodami (panel klienta)
- [x] Możliwość cofnięcia zgody marketingowej
- [x] Akceptacja polityki prywatności (wymagana)

## 2. Żądania RODO
- [x] Rejestr żądań w bazie (gdpr_requests)
- [x] UI do wysyłania żądań (panel klienta)
- [x] Historia żądań i statusy
- [x] Panel admina do obsługi żądań
- [x] Eksport danych użytkownika (JSON)
- [x] Anonimizacja/usunięcie danych użytkownika

## 3. Polityka prywatności
- [x] Modal z polityką w panelu klienta
- [ ] Uzupełnienie treści polityki zgodnej z RODO

## 4. Logi i audyt
- [x] Logowanie operacji na danych (rejestr żądań)
- [ ] Rozszerzenie logów o operacje admina (eksport, usunięcie)

## 5. Bezpieczeństwo
- [x] Dostęp do panelu admina tylko dla uprawnionych
- [x] Szyfrowanie newralgicznych danych (hasła, sesje)
- [ ] Przegląd uprawnień i dostępów

## 6. Optymalizacja technologiczna
- [x] Indeksy na user_id, consent_type, request_type
- [x] Modularna architektura API i UI
- [x] Możliwość rozbudowy o nowe typy zgód/żądań

## 7. Dokumentacja
- [x] Dokumentacja wdrożenia (ten plik)
- [ ] Instrukcja dla admina: obsługa żądań, eksport, anonimizacja
- [ ] Instrukcja dla użytkownika: jak zarządzać zgodami i żądaniami

## 8. Testy
- [x] Dane testowe w bazie
- [x] Widoki testowe w panelu admina i klienta
- [ ] Testy automatyczne (do rozbudowy)

---

**Uwaga:** Uzupełnij treść polityki prywatności oraz instrukcje dla admina i użytkownika. Przeprowadź testy automatyczne i przegląd uprawnień.
