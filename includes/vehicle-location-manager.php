<?php

/**
 * Klasa do zarządzania lokalizacją pojazdów w systemie Fleet Management
 * Automatyczne pobieranie i aktualizacja lokalizacji pojazdów
 */

class VehicleLocationManager
{
    /**
     * Pobiera aktualną lokalizację pojazdu
     */
    public static function getCurrentLocation($vehicleId)
    {
        $stmt = db()->prepare("
            SELECT 
                v.current_location_id,
                l.id as location_id,
                l.name as location_name,
                l.city as location_city,
                l.address as location_address
            FROM vehicles v
            LEFT JOIN locations l ON v.current_location_id = l.id
            WHERE v.id = :vehicle_id
        ");
        $stmt->execute(['vehicle_id' => $vehicleId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Pobiera historię lokalizacji pojazdu
     */
    public static function getLocationHistory($vehicleId, $limit = 10)
    {
        $stmt = db()->prepare("
            SELECT 
                vlh.*,
                l.name as location_name,
                l.city as location_city,
                u.first_name,
                u.last_name,
                CONCAT(u.first_name, ' ', u.last_name) as moved_by_username
            FROM vehicle_location_history vlh
            LEFT JOIN locations l ON vlh.location_id = l.id
            LEFT JOIN users u ON vlh.moved_by = u.id
            WHERE vlh.vehicle_id = :vehicle_id
            ORDER BY vlh.moved_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue('vehicle_id', $vehicleId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Aktualizuje lokalizację pojazdu
     */
    public static function updateLocation($vehicleId, $locationId, $reason = 'manual', $notes = '', $movedBy = null)
    {
        try {
            db()->beginTransaction();

            // Aktualizuj current_location_id w tabeli vehicles
            $stmt = db()->prepare("UPDATE vehicles SET current_location_id = :location_id WHERE id = :vehicle_id");
            $stmt->execute([
                'location_id' => $locationId,
                'vehicle_id' => $vehicleId
            ]);

            // Dodaj wpis do historii
            $stmt = db()->prepare("
                INSERT INTO vehicle_location_history 
                (vehicle_id, location_id, moved_at, moved_by, reason, notes)
                VALUES (:vehicle_id, :location_id, NOW(), :moved_by, :reason, :notes)
            ");
            $stmt->execute([
                'vehicle_id' => $vehicleId,
                'location_id' => $locationId,
                'moved_by' => $movedBy,
                'reason' => $reason,
                'notes' => $notes
            ]);

            db()->commit();
            return true;
        } catch (Exception $e) {
            db()->rollBack();
            error_log("Error updating vehicle location: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobiera wszystkie aktywne lokalizacje
     */
    public static function getAllLocations()
    {
        $stmt = db()->query("
            SELECT id, name, city, address, postal_code 
            FROM locations 
            WHERE is_active = 1 
            ORDER BY name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Formatuje lokalizację do wyświetlenia
     */
    public static function formatLocationDisplay($locationData)
    {
        if (!$locationData || !$locationData['location_name']) {
            return '<span class="text-muted">Nieprzypisana</span>';
        }

        $display = htmlspecialchars($locationData['location_name']);
        if ($locationData['location_city']) {
            $display .= ' - ' . htmlspecialchars($locationData['location_city']);
        }

        return $display;
    }

    /**
     * Pobiera lokalizację na podstawie rezerwacji (dla automatycznych aktualizacji)
     */
    public static function getLocationFromReservation($reservationId, $type = 'pickup')
    {
        $field = $type === 'pickup' ? 'pickup_location_id' : 'return_location_id';

        $stmt = db()->prepare("
            SELECT l.id, l.name, l.city 
            FROM reservation_routes rr
            JOIN locations l ON rr.{$field} = l.id
            WHERE rr.reservation_id = :reservation_id
        ");
        $stmt->execute(['reservation_id' => $reservationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Synchronizuje starą kolumnę location z nowym systemem
     */
    public static function syncLegacyLocation($vehicleId)
    {
        // Pobierz starą lokalizację tekstową
        $stmt = db()->prepare("SELECT location FROM vehicles WHERE id = :id");
        $stmt->execute(['id' => $vehicleId]);
        $vehicle = $stmt->fetch();

        if (!$vehicle || !$vehicle['location']) {
            return false;
        }

        // Spróbuj znaleźć pasującą lokalizację w systemie
        $legacyLocation = $vehicle['location'];
        $stmt = db()->prepare("
            SELECT id FROM locations 
            WHERE name LIKE :search OR city LIKE :search 
            ORDER BY (name = :exact) DESC, is_active DESC 
            LIMIT 1
        ");
        $stmt->execute([
            'search' => '%' . $legacyLocation . '%',
            'exact' => $legacyLocation
        ]);
        $location = $stmt->fetch();

        if ($location) {
            return self::updateLocation($vehicleId, $location['id'], 'initial', 'Migracja z starych danych: ' . $legacyLocation);
        }

        return false;
    }
}
