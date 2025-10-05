# Scripts

Ten katalog zawiera narzędziowe skrypty porządkowo-maintenance'owe. Nie są one ładowane w aplikacji produkcyjnej.

- tests/ — różne testy integracyjne/eksperymentalne (manualne podglądy, diagnostyka UI)
- debug/ — skrypty diagnostyczne do badania problemów
- analysis/ — analizy struktury/SQL/wybranych domen (np. pojazdy, checkout)
- checks/ — skrypty weryfikujące (spójność bazy, tabele, konfiguracje)
- migrations/ — skrypty włączające/zmieniające funkcje (enable_*, migrate_*)
- seeders/ — skrypty uzupełniające dane (add_*, fix_*)

Uwaga: jeśli odwołujesz się do któregoś z tych plików bezpośrednio z przeglądarki, rób to świadomie wyłącznie na środowisku deweloperskim.
