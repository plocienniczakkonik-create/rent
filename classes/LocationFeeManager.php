<?php

/**
 * LocationFeeManager - Zarządzanie opłatami lokalizacyjnymi
 * 
 * Klasa odpowiedzialna za:
 * - Obliczanie opłat za transport między lokalizacjami
 * - Import/export opłat z plików CSV
 * - Zarządzanie tabelą opłat lokalizacyjnych
 * - Automatyczne dodawanie opłat do rezerwacji międzymiastowych
 */

class LocationFeeManager
{
    private $db;

    public function __construct($database = null)
    {
        $this->db = $database ?? db();
    }

    /**
     * Sprawdza czy system opłat lokalizacyjnych jest włączony
     */
    public function isEnabled(): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM shop_settings WHERE setting_key = 'location_fees_enabled'");
            $stmt->execute();
            return ($stmt->fetchColumn() ?? '0') === '1';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Sprawdza czy opłaty mają być automatycznie obliczane
     */
    public function isAutoCalculateEnabled(): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM shop_settings WHERE setting_key = 'location_fees_auto_calculate'");
            $stmt->execute();
            return ($stmt->fetchColumn() ?? '1') === '1';
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Oblicza opłatę lokalizacyjną między dwoma lokalizacjami
     */
    public function calculateLocationFee(int $pickupLocationId, int $returnLocationId): array
    {
        if (!$this->isEnabled()) {
            return [
                'enabled' => false,
                'amount' => 0,
                'description' => 'Opłaty lokalizacyjne wyłączone'
            ];
        }

        // Jeśli ta sama lokalizacja - brak opłaty
        if ($pickupLocationId === $returnLocationId) {
            return [
                'enabled' => false,
                'amount' => 0,
                'description' => 'Ta sama lokalizacja'
            ];
        }

        try {
            // Sprawdź czy istnieje opłata dla tej trasy
            $stmt = $this->db->prepare("
                SELECT lf.*, 
                       pl.name as pickup_name, pl.city as pickup_city,
                       rl.name as return_name, rl.city as return_city
                FROM location_fees lf
                JOIN locations pl ON lf.pickup_location_id = pl.id
                JOIN locations rl ON lf.return_location_id = rl.id
                WHERE lf.pickup_location_id = ? 
                AND lf.return_location_id = ? 
                AND lf.is_active = 1
            ");
            $stmt->execute([$pickupLocationId, $returnLocationId]);
            $fee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$fee) {
                // Sprawdź opłatę w kierunku odwrotnym (symetryczne opłaty)
                $stmt->execute([$returnLocationId, $pickupLocationId]);
                $fee = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($fee) {
                    // Zamień kierunek w opisie dla poprawnego wyświetlania
                    $temp = $fee['pickup_name'];
                    $fee['pickup_name'] = $fee['return_name'];
                    $fee['return_name'] = $temp;

                    $temp = $fee['pickup_city'];
                    $fee['pickup_city'] = $fee['return_city'];
                    $fee['return_city'] = $temp;
                }
            }

            if (!$fee) {
                // Sprawdź domyślną opłatę
                $defaultAmount = $this->getDefaultFeeAmount();
                return [
                    'enabled' => true,
                    'amount' => $defaultAmount,
                    'type' => 'default',
                    'description' => "Opłata domyślna: " . number_format($defaultAmount, 2) . " PLN"
                ];
            }

            $description = "Opłata {$fee['pickup_city']} → {$fee['return_city']}: " . number_format($fee['fee_amount'], 2) . " PLN";

            return [
                'enabled' => true,
                'amount' => (float)$fee['fee_amount'],
                'type' => $fee['fee_type'],
                'pickup_location' => $fee['pickup_name'] . ' (' . $fee['pickup_city'] . ')',
                'return_location' => $fee['return_name'] . ' (' . $fee['return_city'] . ')',
                'description' => $description
            ];
        } catch (Exception $e) {
            return [
                'enabled' => false,
                'amount' => 0,
                'description' => 'Błąd obliczania opłaty'
            ];
        }
    }

    /**
     * Pobiera domyślną kwotę opłaty z ustawień
     */
    public function getDefaultFeeAmount(): float
    {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM location_fees_settings WHERE setting_key = 'default_fee_amount'");
            $stmt->execute();
            return (float)($stmt->fetchColumn() ?? 50.00);
        } catch (Exception $e) {
            return 50.00;
        }
    }

    /**
     * Zapisuje opłatę lokalizacyjną dla rezerwacji
     */
    public function saveReservationLocationFee(int $reservationId, int $pickupLocationId, int $returnLocationId, array $feeData): bool
    {
        try {
            if (!$feeData['enabled'] || $feeData['amount'] <= 0) {
                return true; // Brak opłaty do zapisania
            }

            $stmt = $this->db->prepare("
                INSERT INTO reservation_location_fees 
                (reservation_id, pickup_location_id, return_location_id, fee_amount, fee_type) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                fee_amount = VALUES(fee_amount),
                fee_type = VALUES(fee_type)
            ");

            $stmt->execute([
                $reservationId,
                $pickupLocationId,
                $returnLocationId,
                $feeData['amount'],
                $feeData['type'] ?? 'fixed'
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Pobiera opłatę lokalizacyjną dla rezerwacji
     */
    public function getReservationLocationFee(int $reservationId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT rlf.*, 
                       pl.name as pickup_name, pl.city as pickup_city,
                       rl.name as return_name, rl.city as return_city
                FROM reservation_location_fees rlf
                LEFT JOIN locations pl ON rlf.pickup_location_id = pl.id
                LEFT JOIN locations rl ON rlf.return_location_id = rl.id
                WHERE rlf.reservation_id = ?
            ");
            $stmt->execute([$reservationId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Pobiera wszystkie opłaty lokalizacyjne
     */
    public function getAllLocationFees(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT lf.*, 
                       pl.name as pickup_name, pl.city as pickup_city,
                       rl.name as return_name, rl.city as return_city
                FROM location_fees lf
                JOIN locations pl ON lf.pickup_location_id = pl.id
                JOIN locations rl ON lf.return_location_id = rl.id
                WHERE lf.is_active = 1
                ORDER BY pl.name, rl.name
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Sprawdza czy trasa już istnieje (uwzględniając symetrię)
     */
    public function routeExists(int $pickupLocationId, int $returnLocationId): array
    {
        try {
            // Sprawdź bezpośrednią trasę
            $stmt = $this->db->prepare("
                SELECT * FROM location_fees 
                WHERE pickup_location_id = ? AND return_location_id = ? AND is_active = 1
            ");
            $stmt->execute([$pickupLocationId, $returnLocationId]);
            $direct = $stmt->fetch(PDO::FETCH_ASSOC);

            // Sprawdź trasę odwrotną (symetryczną)
            $stmt->execute([$returnLocationId, $pickupLocationId]);
            $reverse = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'direct' => $direct ?: null,
                'reverse' => $reverse ?: null,
                'exists' => !empty($direct) || !empty($reverse)
            ];
        } catch (Exception $e) {
            return ['direct' => null, 'reverse' => null, 'exists' => false];
        }
    }

    /**
     * Dodaje lub aktualizuje opłatę lokalizacyjną z walidacją unikalności
     */
    public function setLocationFee(int $pickupLocationId, int $returnLocationId, float $amount, string $type = 'fixed'): array
    {
        // Walidacja: ta sama lokalizacja
        if ($pickupLocationId === $returnLocationId) {
            return [
                'success' => false,
                'error' => 'Lokalizacja odbioru i zwrotu nie może być taka sama'
            ];
        }

        // Sprawdź czy trasa już istnieje (symetrycznie)
        $existing = $this->routeExists($pickupLocationId, $returnLocationId);

        if ($existing['exists']) {
            $existingRoute = $existing['direct'] ?: $existing['reverse'];
            $direction = $existing['direct'] ? 'bezpośrednia' : 'odwrotna';

            return [
                'success' => false,
                'error' => "Trasa już istnieje jako {$direction}",
                'existing_fee' => $existingRoute['fee_amount'],
                'existing_direction' => $direction
            ];
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO location_fees 
                (pickup_location_id, return_location_id, fee_amount, fee_type, is_active) 
                VALUES (?, ?, ?, ?, 1)
            ");

            $stmt->execute([$pickupLocationId, $returnLocationId, $amount, $type]);
            return [
                'success' => true,
                'message' => 'Opłata lokalizacyjna została dodana'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Błąd bazy danych: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Aktualizuje istniejącą opłatę lokalizacyjną
     */
    public function updateLocationFee(int $pickupLocationId, int $returnLocationId, float $amount, string $type = 'fixed'): array
    {
        $existing = $this->routeExists($pickupLocationId, $returnLocationId);

        if (!$existing['exists']) {
            return [
                'success' => false,
                'error' => 'Trasa nie istnieje'
            ];
        }

        try {
            // Aktualizuj bezpośrednią trasę jeśli istnieje, inaczej odwrotną
            if ($existing['direct']) {
                $stmt = $this->db->prepare("
                    UPDATE location_fees 
                    SET fee_amount = ?, fee_type = ?, updated_at = NOW()
                    WHERE pickup_location_id = ? AND return_location_id = ?
                ");
                $stmt->execute([$amount, $type, $pickupLocationId, $returnLocationId]);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE location_fees 
                    SET fee_amount = ?, fee_type = ?, updated_at = NOW()
                    WHERE pickup_location_id = ? AND return_location_id = ?
                ");
                $stmt->execute([$amount, $type, $returnLocationId, $pickupLocationId]);
            }

            return [
                'success' => true,
                'message' => 'Opłata lokalizacyjna została zaktualizowana'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Błąd bazy danych: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Usuwa opłatę lokalizacyjną
     */
    public function removeLocationFee(int $pickupLocationId, int $returnLocationId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE location_fees 
                SET is_active = 0 
                WHERE pickup_location_id = ? AND return_location_id = ?
            ");
            $stmt->execute([$pickupLocationId, $returnLocationId]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Import opłat z pliku CSV
     * Format: pickup_location_id, return_location_id, fee_amount
     */
    public function importFromCSV(string $csvFilePath): array
    {
        $result = [
            'success' => false,
            'imported' => 0,
            'errors' => [],
            'total_rows' => 0
        ];

        if (!file_exists($csvFilePath)) {
            $result['errors'][] = 'Plik CSV nie istnieje';
            return $result;
        }

        try {
            $handle = fopen($csvFilePath, 'r');
            if (!$handle) {
                $result['errors'][] = 'Nie można otworzyć pliku CSV';
                return $result;
            }

            $this->db->beginTransaction();

            $row = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row++;
                $result['total_rows']++;

                // Pomiń nagłówek jeśli istnieje
                if ($row === 1 && (strtolower($data[0]) === 'pickup_location_id' || strtolower($data[0]) === 'odbiór')) {
                    continue;
                }

                // Sprawdź format
                if (count($data) < 3) {
                    $result['errors'][] = "Wiersz $row: Nieprawidłowy format (oczekiwane 3 kolumny)";
                    continue;
                }

                $pickupLocationId = (int)trim($data[0]);
                $returnLocationId = (int)trim($data[1]);
                $feeAmount = (float)str_replace(',', '.', trim($data[2]));

                // Sprawdź czy lokalizacje istnieją
                if (!$this->locationExists($pickupLocationId) || !$this->locationExists($returnLocationId)) {
                    $result['errors'][] = "Wiersz $row: Lokalizacja nie istnieje (odbiór: $pickupLocationId, zwrot: $returnLocationId)";
                    continue;
                }

                // Dodaj opłatę
                if ($this->setLocationFee($pickupLocationId, $returnLocationId, $feeAmount)) {
                    $result['imported']++;
                } else {
                    $result['errors'][] = "Wiersz $row: Błąd zapisywania opłaty";
                }
            }

            fclose($handle);
            $this->db->commit();
            $result['success'] = true;
        } catch (Exception $e) {
            $this->db->rollback();
            $result['errors'][] = 'Błąd importu: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Export opłat do pliku CSV
     */
    public function exportToCSV(string $csvFilePath): bool
    {
        try {
            $handle = fopen($csvFilePath, 'w');
            if (!$handle) {
                return false;
            }

            // Nagłówek
            fputcsv($handle, ['pickup_location_id', 'return_location_id', 'fee_amount', 'pickup_city', 'return_city']);

            // Dane
            $fees = $this->getAllLocationFees();
            foreach ($fees as $fee) {
                fputcsv($handle, [
                    $fee['pickup_location_id'],
                    $fee['return_location_id'],
                    $fee['fee_amount'],
                    $fee['pickup_city'],
                    $fee['return_city']
                ]);
            }

            fclose($handle);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Sprawdza czy lokalizacja istnieje
     */
    private function locationExists(int $locationId): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM locations WHERE id = ? AND is_active = 1");
            $stmt->execute([$locationId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Pobiera statystyki opłat lokalizacyjnych
     */
    public function getLocationFeeStats(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_fees,
                    AVG(fee_amount) as average_fee,
                    MIN(fee_amount) as min_fee,
                    MAX(fee_amount) as max_fee,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_fees
                FROM location_fees
            ");
            $stmt->execute();

            $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            // Dodaj statystyki użycia
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_reservations_with_fees,
                    SUM(fee_amount) as total_fees_collected
                FROM reservation_location_fees
            ");
            $stmt->execute();
            $usageStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            return array_merge($stats, $usageStats);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Pobiera najczęściej używane trasy
     */
    public function getPopularRoutes(int $limit = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    rlf.pickup_location_id,
                    rlf.return_location_id,
                    pl.name as pickup_name,
                    pl.city as pickup_city,
                    rl.name as return_name,
                    rl.city as return_city,
                    COUNT(*) as usage_count,
                    AVG(rlf.fee_amount) as average_fee
                FROM reservation_location_fees rlf
                JOIN locations pl ON rlf.pickup_location_id = pl.id
                JOIN locations rl ON rlf.return_location_id = rl.id
                GROUP BY rlf.pickup_location_id, rlf.return_location_id
                ORDER BY usage_count DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
