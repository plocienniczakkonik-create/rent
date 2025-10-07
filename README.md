# Rental – System Zarządzania Wynajmem Pojazdów

## Spis treści
1. [Opis projektu](#opis-projektu)
2. [Architektura i logika działania](#architektura-i-logika-działania)
3. [Struktura baz danych](#struktura-baz-danych)
4. [Kluczowe klasy i pliki](#kluczowe-klasy-i-pliki)
5. [Scenariusze użytkownika](#scenariusze-uzytkownika)
6. [Standard stylistyczny](#standard-stylistyczny)
7. [Instrukcja uruchomienia](#instrukcja-uruchomienia)
8. [Testy i seedowanie danych](#testy-i-seedowanie-danych)
9. [Raportowanie i analityka](#raportowanie-i-analityka)
10. [Wskazówki rozwojowe](#wskazowki-rozwojowe)

---

## Opis projektu
System rental to zaawansowana aplikacja do zarządzania wynajmem pojazdów, flotą, lokalizacjami, rezerwacjami, dodatkami, serwisami, kolizjami i raportami. Pozwala na szczegółową analizę zamówień, tras, statusów, opłat, kaucji oraz historii pojazdów.

## Architektura i logika działania
- Backend: PHP (klasy FleetManager, DepositManager, LocationFeeManager)
- Frontend: Bootstrap, FontAwesome, responsywny design
- Baza danych: MySQL, podział na logiczne tabele (pojazdy, lokalizacje, rezerwacje, opłaty, dodatki, użytkownicy, serwisy, kolizje)
- Bezpieczeństwo: walidacja, prepared statements, haszowanie haseł, XSS prevention
- Workflow: od wyszukiwania pojazdu, przez rezerwację, po raportowanie i analizę

## Struktura baz danych
- `locations` – miejsca odbioru/zwrotu
- `vehicles` – pojazdy, z aktualną lokalizacją
- `products` – modele pojazdów, powiązane z egzemplarzami
- `reservation_routes` – trasy rezerwacji (pickup/return)
- `vehicle_location_history` – historia lokalizacji pojazdów
- `reservations` – rezerwacje, powiązane z trasą, pojazdem, klientem
- `location_fees` – opłaty między lokalizacjami (symetryczne)
- `shop_deposit_settings`, `reservation_deposits` – system kaucji
- `addons` – dodatki do wynajmu
- `users` – użytkownicy, role, uprawnienia
- `services`, `incidents` – serwisy i kolizje pojazdów

## Kluczowe klasy i pliki
- `classes/FleetManager.php` – zarządzanie flotą i lokalizacjami
- `classes/DepositManager.php` – system kaucji
- `classes/LocationFeeManager.php` – opłaty lokalizacyjne
- `pages/checkout.php`, `pages/checkout-confirm.php` – proces rezerwacji
- `scripts/seeders/add_test_data.php` – seedowanie danych testowych
- `scripts/tests/` – testy funkcjonalności, workflow, klas

## Scenariusze użytkownika
- Wyszukiwanie pojazdu wg lokalizacji, dat, klasy, modelu
- Rezerwacja konkretnego egzemplarza pojazdu
- Finalizacja zamówienia z pełnymi danymi (kto, co, gdzie, kiedy, na ile dni)
- Zarządzanie flotą, lokalizacjami, historią pojazdów
- Dodawanie, edycja, usuwanie pojazdów, użytkowników, dodatków
- Raportowanie wg egzemplarza, klasy, miejsca wynajmu, statusu, kolizji, serwisów

## Standard stylistyczny
- Bootstrap, FontAwesome, zmienne CSS
- Podział na sekcje/karty, logiczne nagłówki
- Responsywność, spójność wizualna
- Ikony dla różnych sekcji (info, lokalizacja, terminy, notatki, usługi, incydenty, statystyki)
- Zgodność z plikiem `docs/STANDARD_STYLISTYCZNY.md`

## Instrukcja uruchomienia
1. Skonfiguruj bazę danych MySQL (importuj pliki z folderu `database/`)
2. Ustaw dane dostępowe w `includes/config.php`
3. Uruchom serwer lokalny (np. XAMPP)
4. Zainstaluj zależności frontendowe: `npm install`, zbuduj SCSS: `npm run build`
5. Seeduj dane testowe: uruchom `scripts/seeders/add_test_data.php`
6. Przetestuj funkcje: uruchom skrypty z `scripts/tests/`

## Testy i seedowanie danych
- Skrypt `add_test_data.php` dodaje przykładowe pojazdy, rezerwacje, lokalizacje, opłaty
- Testy klas i workflow w folderze `scripts/tests/`
- Dane testowe zgodne z workflow produkcyjnym

## Raportowanie i analityka
- Raporty umożliwiają analizę wg egzemplarza, klasy, miejsca wynajmu, modelu, trasy, statusu, kolizji, serwisów
- Dane rezerwacji zawierają: klienta, pojazd, egzemplarz, trasę, daty, status, opłaty, kaucje, dodatki
- Historia lokalizacji i statusów pojazdów dostępna w raportach
- Możliwość rozbudowy raportów o dodatkowe filtry i agregacje

## Wskazówki rozwojowe
- Upewnij się, że każda rezerwacja jest powiązana z konkretnym egzemplarzem pojazdu
- Rozwijaj raporty o analizy per auto, per klasa, per miejsce wynajmu, per incydent, per serwis
- Optymalizuj strukturę baz danych pod kątem agregacji i analityki
- Stosuj standard stylistyczny i metodologię z pliku `docs/STANDARD_STYLISTYCZNY.md`

## Audyt bezpieczeństwa plików i zależności (2025)

### uploads/ – bezpieczeństwo i struktura
- Folder `uploads/` zawiera podfoldery `incidents/` oraz `services/`, które są puste lub zawierają tylko oczekiwane katalogi. Brak plików wrażliwych lub nieautoryzowanych.
- Folder `assets/uploads/products/` przechowuje wyłącznie obrazy produktów, zgodnie z założeniami systemu.
- Brak plików tymczasowych, logów, backupów czy innych niepożądanych danych w katalogach uploadów.
- System waliduje i kontroluje uploadowane pliki, minimalizując ryzyko ataków (np. upload shelli, plików wykonywalnych).

### vendor/ – zależności PHP (Composer)
- Wszystkie zależności PHP instalowane są przez Composer, brak ręcznie dodanych bibliotek.
- Wykorzystywane pakiety to m.in.: `phpoffice/phpspreadsheet`, `maennchen/zipstream-php`, `markbaker/complex`, `markbaker/matrix`, `psr/http-client`, `psr/http-factory`, `psr/http-message`, `psr/simple-cache`, `composer/pcre`.
- Wszystkie pakiety są aktualne, pochodzą z oficjalnych repozytoriów, posiadają licencje open source (MIT/BSD).
- Brak nieużywanych lub porzuconych zależności.

### Zależności frontendowe (npm)
- Frontend korzysta z lokalnych wersji Bootstrap, Chart.js, Flatpickr oraz własnych modułów JS/SCSS.
- Zależności JS i SCSS są zarządzane przez npm i budowane przez skrypt `npm run build`.

### Podsumowanie bezpieczeństwa
- System nie przechowuje żadnych wrażliwych plików w katalogach publicznych.
- Uploadowane pliki są walidowane i przechowywane w dedykowanych folderach.
- Wszystkie zależności są aktualne, bez znanych podatności.
- Brak debug logów i niepotrzebnych plików informacyjnych.

---

> Audyt przeprowadzono 07.10.2025. System spełnia aktualne standardy bezpieczeństwa i jakości kodu.

---

> Ten README powstał na bazie pełnej analizy kodu, baz danych, workflow i standardów projektu rental. Pozwala szybko wrócić do pracy z projektem, zrozumieć logikę, strukturę i możliwości systemu.
