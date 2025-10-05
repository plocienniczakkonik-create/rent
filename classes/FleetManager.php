<?php

/**
 * FleetManager - Zarządzanie lokalizacjami floty pojazdów
 * 
 * Klasa odpowiedzialna za:
 * - Śledzenie aktualnych lokalizacji pojazdów
 * - Aktualizowanie lokalizacji po rezerwacjach
 * - Sprawdzanie dostępności pojazdów w poszczególnych lokalizacjach
 * - Filtrowanie wyników wyszukiwania według lokalizacji
 */

class FleetManager
{
    private $db;

    public function __construct($database = null)
    {
        $this->db = $database ?? db();
    }

    /**
     * Sprawdza czy system zarządzania flotą jest włączony
     */
    public function isEnabled(): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM shop_settings WHERE setting_key = 'fleet_management_enabled'");
            $stmt->execute();
            return ($stmt->fetchColumn() ?? '0') === '1';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Pobiera wszystkie aktywne lokalizacje
     */
    public function getActiveLocations(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, city, address, postal_code 
                FROM locations 
                WHERE is_active = 1 
                ORDER BY name ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Pobiera lokalizację po ID
     */
    public function getLocationById(int $locationId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, city, address, postal_code 
                FROM locations 
                WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([$locationId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Pobiera domyślną lokalizację z ustawień
     */
    public function getDefaultLocation(): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM shop_settings WHERE setting_key = 'fleet_default_location'");
            $stmt->execute();
            $defaultId = $stmt->fetchColumn();

            if ($defaultId) {
                return $this->getLocationById((int)$defaultId);
            }

            // Jeśli brak domyślnej, zwróć pierwszą dostępną
            $locations = $this->getActiveLocations();
            return $locations[0] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Aktualizuje aktualną lokalizację pojazdu
     */
    public function updateVehicleLocation(int $vehicleId, int $locationId, string $reason = 'manual', ?int $movedBy = null, ?string $notes = null): bool
    {
        try {
            $this->db->beginTransaction();

            // Aktualizuj current_location_id w tabeli vehicles
            $stmt = $this->db->prepare("UPDATE vehicles SET current_location_id = ? WHERE id = ?");
            $stmt->execute([$locationId, $vehicleId]);

            // Dodaj wpis do historii
            $stmt = $this->db->prepare("
                INSERT INTO vehicle_location_history 
                (vehicle_id, location_id, moved_by, reason, notes) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$vehicleId, $locationId, $movedBy, $reason, $notes]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Pobiera pojazdy dostępne w danej lokalizacji
     */
    public function getAvailableVehiclesInLocation(int $locationId, ?string $productSku = null): array
    {
        try {
            $sql = "
                SELECT v.*, p.name as product_name, p.sku, l.name as location_name, l.city
                FROM vehicles v
                JOIN products p ON v.product_id = p.id
                LEFT JOIN locations l ON v.current_location_id = l.id
                WHERE v.status = 'available' 
                AND v.current_location_id = ?
            ";

            $params = [$locationId];

            if ($productSku) {
                $sql .= " AND p.sku = ?";
                $params[] = $productSku;
            }

            $sql .= " ORDER BY p.name, v.registration_number";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Sprawdza czy produkt jest dostępny w danej lokalizacji
     */
    public function isProductAvailableInLocation($productIdentifier, int $locationId, ?string $dateFrom = null, ?string $dateTo = null): bool
    {
        try {
            // Obsługa zarówno ID jak i SKU produktu
            if (is_numeric($productIdentifier)) {
                // Jeśli to ID, znajdź SKU
                $stmt = $this->db->prepare("SELECT sku FROM products WHERE id = ?");
                $stmt->execute([$productIdentifier]);
                $productSku = $stmt->fetchColumn();
                if (!$productSku) {
                    return false;
                }
            } else {
                // Jeśli to SKU, użyj bezpośrednio
                $productSku = $productIdentifier;
            }

            // Pobierz dostępne pojazdy dla tego produktu w tej lokalizacji
            $vehicles = $this->getAvailableVehiclesInLocation($locationId, $productSku);

            if (empty($vehicles)) {
                return false;
            }

            // Jeśli nie ma dat, sprawdź tylko czy są pojazdy
            if (!$dateFrom || !$dateTo) {
                return true;
            }

            // Sprawdź czy któryś pojazd jest dostępny w tym okresie
            foreach ($vehicles as $vehicle) {
                if ($this->isVehicleAvailableInPeriod($vehicle['id'], $dateFrom, $dateTo)) {
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Sprawdza czy pojazd jest dostępny w danym okresie
     */
    private function isVehicleAvailableInPeriod(int $vehicleId, string $dateFrom, string $dateTo): bool
    {
        try {
            // Sprawdź nakładające się rezerwacje dla tego konkretnego pojazdu
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM reservations r
                JOIN products p ON r.sku = p.sku
                JOIN vehicles v ON v.product_id = p.id
                WHERE v.id = ?
                AND r.status != 'cancelled'
                AND (
                    (r.pickup_at < ? AND r.return_at > ?) OR
                    (r.pickup_at < ? AND r.return_at > ?) OR
                    (r.pickup_at >= ? AND r.return_at <= ?)
                )
            ");

            $stmt->execute([
                $vehicleId,
                $dateTo,
                $dateFrom,     // Koniec nowej < początek istniejącej
                $dateFrom,
                $dateTo,     // Początek nowej < koniec istniejącej  
                $dateFrom,
                $dateTo      // Nowa mieści się w istniejącej
            ]);

            return $stmt->fetchColumn() == 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Pobiera statystyki lokalizacji (liczba pojazdów w każdej lokalizacji)
     */
    public function getLocationStats(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    l.id, l.name, l.city,
                    COUNT(v.id) as total_vehicles,
                    COUNT(CASE WHEN v.status = 'available' THEN 1 END) as available_vehicles,
                    COUNT(CASE WHEN v.status = 'booked' THEN 1 END) as booked_vehicles,
                    COUNT(CASE WHEN v.status = 'maintenance' THEN 1 END) as maintenance_vehicles
                FROM locations l
                LEFT JOIN vehicles v ON v.current_location_id = l.id
                WHERE l.is_active = 1
                GROUP BY l.id, l.name, l.city
                ORDER BY l.name
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Przenosi pojazd do lokalizacji zwrotu po zakończonej rezerwacji
     */
    public function handleReservationReturn(int $reservationId): bool
    {
        try {
            // Sprawdź czy auto-update jest włączony
            $stmt = $this->db->prepare("SELECT setting_value FROM shop_settings WHERE setting_key = 'fleet_auto_update_location'");
            $stmt->execute();
            $autoUpdate = ($stmt->fetchColumn() ?? '1') === '1';

            if (!$autoUpdate) {
                return true; // Nie aktualizuj automatycznie
            }

            // Znajdź szczegóły rezerwacji i trasę
            $stmt = $this->db->prepare("
                SELECT r.sku, rr.return_location_id, rr.pickup_location_id
                FROM reservations r
                LEFT JOIN reservation_routes rr ON rr.reservation_id = r.id
                WHERE r.id = ?
            ");
            $stmt->execute([$reservationId]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reservation || !$reservation['return_location_id']) {
                return false;
            }

            // Znajdź pojazd dla tego SKU
            $stmt = $this->db->prepare("
                SELECT v.id FROM vehicles v
                JOIN products p ON v.product_id = p.id
                WHERE p.sku = ? AND v.current_location_id = ?
                LIMIT 1
            ");
            $stmt->execute([$reservation['sku'], $reservation['pickup_location_id']]);
            $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$vehicle) {
                return false;
            }

            // Przenieś pojazd do lokalizacji zwrotu
            return $this->updateVehicleLocation(
                $vehicle['id'],
                $reservation['return_location_id'],
                'rental_return',
                null,
                "Auto move after reservation #$reservationId"
            );
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Pobiera historię lokalizacji pojazdu
     */
    public function getVehicleLocationHistory(int $vehicleId, int $limit = 50): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    vlh.*,
                    l.name as location_name,
                    l.city,
                    u.first_name,
                    u.last_name
                FROM vehicle_location_history vlh
                LEFT JOIN locations l ON vlh.location_id = l.id
                LEFT JOIN users u ON vlh.moved_by = u.id
                WHERE vlh.vehicle_id = ?
                ORDER BY vlh.moved_at DESC
                LIMIT ?
            ");
            $stmt->execute([$vehicleId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
