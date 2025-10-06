<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/i18n.php';

// Ustawienie kodowania UTF-8
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Ustaw kodowanie UTF-8 dla połączenia z bazą
$db = db();
$db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_polish_ci");

try {
    // Parametry filtrów
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01', strtotime('-6 months'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-t');
    $product = $_GET['product'] ?? '';
    $status = $_GET['status'] ?? '';
    $location = $_GET['location'] ?? '';
    $reportType = $_GET['report_type'] ?? 'summary';

    // Bazowe zapytanie - używamy pickup_at dla filtrowania dat
    $whereConditions = ["pickup_at BETWEEN ? AND ?"];
    $params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

    if (!empty($product)) {
        $whereConditions[] = "product_name LIKE ?";
        $params[] = '%' . $product . '%';
    }

    if (!empty($status)) {
        $whereConditions[] = "status = ?";
        $params[] = $status;
    }

    if (!empty($location)) {
        $whereConditions[] = "pickup_location LIKE ?";
        $params[] = '%' . $location . '%';
    }

    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

    switch ($reportType) {
        case 'kpi':
            $kpiType = $_GET['kpi_type'] ?? 'summary';

            switch ($kpiType) {
                case 'incidents':
                    // KPI dla incydentów
                    $incidentWhere = "incident_date BETWEEN ? AND ?";
                    $incidentParams = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

                    // Łączny koszt napraw
                    $stmt = db()->prepare("SELECT SUM(repair_cost) as total_cost FROM vehicle_incidents WHERE $incidentWhere");
                    $stmt->execute($incidentParams);
                    $totalCost = $stmt->fetch(PDO::FETCH_ASSOC)['total_cost'] ?? 0;

                    // Liczba incydentów
                    $stmt = db()->prepare("SELECT COUNT(*) as count FROM vehicle_incidents WHERE $incidentWhere");
                    $stmt->execute($incidentParams);
                    $totalIncidents = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

                    // Najdroższy incydent
                    $stmt = db()->prepare("
                        SELECT vi.repair_cost, v.registration_number, vi.damage_desc 
                        FROM vehicle_incidents vi
                        LEFT JOIN vehicles v ON vi.vehicle_id = v.id
                        WHERE $incidentWhere AND vi.repair_cost > 0
                        ORDER BY vi.repair_cost DESC 
                        LIMIT 1
                    ");
                    $stmt->execute($incidentParams);
                    $mostExpensive = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Pojazd z największą liczbą incydentów
                    $stmt = db()->prepare("
                        SELECT v.registration_number, COUNT(*) as count
                        FROM vehicle_incidents vi
                        LEFT JOIN vehicles v ON vi.vehicle_id = v.id
                        WHERE $incidentWhere
                        GROUP BY vi.vehicle_id, v.registration_number
                        ORDER BY count DESC
                        LIMIT 1
                    ");
                    $stmt->execute($incidentParams);
                    $mostIncidents = $stmt->fetch(PDO::FETCH_ASSOC);

                    $data = [
                        'kpi1' => [
                            'title' => 'Łączny koszt napraw',
                            'value' => number_format($totalCost, 2) . ' PLN',
                            'icon' => 'currency-dollar'
                        ],
                        'kpi2' => [
                            'title' => 'Liczba incydentów',
                            'value' => $totalIncidents,
                            'subtitle' => 'w wybranym okresie',
                            'icon' => 'exclamation-triangle'
                        ],
                        'kpi3' => [
                            'title' => 'Najdroższy incydent',
                            'value' => $mostExpensive ? $mostExpensive['registration_number'] : 'Brak danych',
                            'subtitle' => $mostExpensive ? number_format($mostExpensive['repair_cost'], 2) . ' PLN' : '',
                            'icon' => 'star'
                        ],
                        'kpi4' => [
                            'title' => 'Najwięcej incydentów',
                            'value' => $mostIncidents ? $mostIncidents['registration_number'] : 'Brak danych',
                            'subtitle' => $mostIncidents ? $mostIncidents['count'] . ' incydentów' : '',
                            'icon' => 'graph-up'
                        ]
                    ];
                    break;

                case 'services':
                    // KPI dla serwisów
                    $serviceWhere = "service_date BETWEEN ? AND ?";
                    $serviceParams = [$dateFrom, $dateTo];

                    // Łączny koszt serwisów
                    $stmt = db()->prepare("SELECT SUM(cost_total) as total_cost FROM vehicle_services WHERE $serviceWhere");
                    $stmt->execute($serviceParams);
                    $totalCost = $stmt->fetch(PDO::FETCH_ASSOC)['total_cost'] ?? 0;

                    // Liczba serwisów
                    $stmt = db()->prepare("SELECT COUNT(*) as count FROM vehicle_services WHERE $serviceWhere");
                    $stmt->execute($serviceParams);
                    $totalServices = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

                    // Najdroższy serwis
                    $stmt = db()->prepare("
                        SELECT vs.cost_total, v.registration_number, vs.workshop_name, vs.issues_found
                        FROM vehicle_services vs
                        LEFT JOIN vehicles v ON vs.vehicle_id = v.id
                        WHERE $serviceWhere
                        ORDER BY vs.cost_total DESC 
                        LIMIT 1
                    ");
                    $stmt->execute($serviceParams);
                    $mostExpensive = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Pojazd z największą liczbą serwisów
                    $stmt = db()->prepare("
                        SELECT v.registration_number, COUNT(*) as count
                        FROM vehicle_services vs
                        LEFT JOIN vehicles v ON vs.vehicle_id = v.id
                        WHERE $serviceWhere
                        GROUP BY vs.vehicle_id, v.registration_number
                        ORDER BY count DESC
                        LIMIT 1
                    ");
                    $stmt->execute($serviceParams);
                    $mostServices = $stmt->fetch(PDO::FETCH_ASSOC);

                    $data = [
                        'kpi1' => [
                            'title' => 'Koszt serwisów',
                            'value' => number_format($totalCost, 2) . ' PLN',
                            'icon' => 'currency-dollar'
                        ],
                        'kpi2' => [
                            'title' => 'Liczba serwisów',
                            'value' => $totalServices,
                            'subtitle' => 'w wybranym okresie',
                            'icon' => 'tools'
                        ],
                        'kpi3' => [
                            'title' => 'Najdroższy serwis',
                            'value' => $mostExpensive ? $mostExpensive['registration_number'] : 'Brak danych',
                            'subtitle' => $mostExpensive ? number_format($mostExpensive['cost_total'], 2) . ' PLN' : '',
                            'icon' => 'star'
                        ],
                        'kpi4' => [
                            'title' => 'Najwięcej serwisów',
                            'value' => $mostServices ? $mostServices['registration_number'] : 'Brak danych',
                            'subtitle' => $mostServices ? $mostServices['count'] . ' serwisów' : '',
                            'icon' => 'graph-up'
                        ]
                    ];
                    break;

                default: // summary
                    // KPI standardowe (rezerwacje)
                    $stmt = db()->prepare("
                        SELECT 
                            COUNT(*) as total_reservations,
                            SUM(total_gross) as total_revenue,
                            AVG(total_gross) as avg_revenue,
                            COUNT(DISTINCT product_name) as unique_products
                        FROM reservations 
                        $whereClause
                    ");
                    $stmt->execute($params);
                    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Top produkt
                    $stmt = db()->prepare("
                        SELECT product_name, COUNT(*) as count
                        FROM reservations 
                        $whereClause
                        GROUP BY product_name
                        ORDER BY count DESC
                        LIMIT 1
                    ");
                    $stmt->execute($params);
                    $topProduct = $stmt->fetch(PDO::FETCH_ASSOC);

                    $data = [
                        'kpi1' => [
                            'title' => 'Przychód',
                            'value' => number_format($summary['total_revenue'] ?? 0, 2) . ' PLN',
                            'icon' => 'currency-dollar'
                        ],
                        'kpi2' => [
                            'title' => 'Zamówienia',
                            'value' => $summary['total_reservations'] ?? 0,
                            'subtitle' => 'w wybranym okresie',
                            'icon' => 'receipt'
                        ],
                        'kpi3' => [
                            'title' => 'Top produkt',
                            'value' => $topProduct ? $topProduct['product_name'] : 'Brak danych',
                            'subtitle' => $topProduct ? $topProduct['count'] . ' rezerwacji' : '',
                            'icon' => 'star'
                        ],
                        'kpi4' => [
                            'title' => 'Średnia wartość',
                            'value' => number_format($summary['avg_revenue'] ?? 0, 2) . ' PLN',
                            'subtitle' => 'w okresie',
                            'icon' => 'graph-up'
                        ]
                    ];
                    break;

                case 'vehicles':
                    // KPI dla pojazdów - uwzględniamy wszystkie egzemplarze modelu
                    if (!empty($product)) {
                        // Liczba wszystkich egzemplarzy danego modelu
                        $stmt = db()->prepare("
                            SELECT COUNT(*) as total_model_vehicles
                            FROM vehicles v
                            LEFT JOIN products p ON v.product_id = p.id
                            WHERE p.name LIKE ?
                        ");
                        $stmt->execute(['%' . $product . '%']);
                        $modelVehicles = $stmt->fetch(PDO::FETCH_ASSOC);

                        // Aktywne pojazdy (z rezerwacjami) w okresie
                        $vehicleKpiWhere = ["r.pickup_at BETWEEN ? AND ?", "r.vehicle_id IS NOT NULL"];
                        $vehicleKpiParams = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];
                        $vehicleKpiWhere[] = "(r.product_name LIKE ? OR EXISTS(SELECT 1 FROM vehicles v2 LEFT JOIN products p2 ON v2.product_id = p2.id WHERE v2.id = r.vehicle_id AND p2.name LIKE ?))";
                        $vehicleKpiParams[] = '%' . $product . '%';
                        $vehicleKpiParams[] = '%' . $product . '%';
                    } else {
                        $vehicleKpiWhere = ["r.pickup_at BETWEEN ? AND ?", "r.vehicle_id IS NOT NULL"];
                        $vehicleKpiParams = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];
                        $modelVehicles = ['total_model_vehicles' => 0]; // Nie filtrujemy po modelu
                    }

                    if (!empty($status)) {
                        $vehicleKpiWhere[] = "r.status = ?";
                        $vehicleKpiParams[] = $status;
                    }

                    if (!empty($location)) {
                        $vehicleKpiWhere[] = "r.pickup_location LIKE ?";
                        $vehicleKpiParams[] = '%' . $location . '%';
                    }

                    $vehicleKpiWhereClause = 'WHERE ' . implode(' AND ', $vehicleKpiWhere);

                    $stmt = db()->prepare("
                        SELECT 
                            COUNT(DISTINCT r.vehicle_id) as active_vehicles,
                            COUNT(*) as total_reservations,
                            SUM(r.total_gross) as total_revenue
                        FROM reservations r 
                        $vehicleKpiWhereClause
                    ");
                    $stmt->execute($vehicleKpiParams);
                    $vehicleSummary = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Najaktywniejszy pojazd
                    $stmt = db()->prepare("
                        SELECT v.registration_number, COUNT(*) as reservations, SUM(r.total_gross) as revenue
                        FROM reservations r
                        LEFT JOIN vehicles v ON r.vehicle_id = v.id
                        $vehicleKpiWhereClause
                        GROUP BY r.vehicle_id, v.registration_number
                        ORDER BY reservations DESC
                        LIMIT 1
                    ");
                    $stmt->execute($vehicleKpiParams);
                    $topVehicle = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Najdochodowszy pojazd
                    $stmt = db()->prepare("
                        SELECT v.registration_number, SUM(r.total_gross) as revenue
                        FROM reservations r
                        LEFT JOIN vehicles v ON r.vehicle_id = v.id
                        $vehicleKpiWhereClause
                        GROUP BY r.vehicle_id, v.registration_number
                        ORDER BY revenue DESC
                        LIMIT 1
                    ");
                    $stmt->execute($vehicleKpiParams);
                    $mostProfitable = $stmt->fetch(PDO::FETCH_ASSOC);

                    $data = [
                        'kpi1' => [
                            'title' => !empty($product) ? 'Egzemplarze modelu' : 'Aktywne pojazdy',
                            'value' => !empty($product) ? ($modelVehicles['total_model_vehicles'] ?? 0) : ($vehicleSummary['active_vehicles'] ?? 0),
                            'subtitle' => !empty($product) ? (($vehicleSummary['active_vehicles'] ?? 0) . ' z rezerwacjami') : '',
                            'icon' => 'truck'
                        ],
                        'kpi2' => [
                            'title' => 'Łączne rezerwacje',
                            'value' => $vehicleSummary['total_reservations'] ?? 0,
                            'subtitle' => 'w okresie',
                            'icon' => 'calendar2-check'
                        ],
                        'kpi3' => [
                            'title' => 'Najaktywniejszy',
                            'value' => $topVehicle ? $topVehicle['registration_number'] : 'Brak danych',
                            'subtitle' => $topVehicle ? $topVehicle['reservations'] . ' rezerwacji' : '',
                            'icon' => 'trophy'
                        ],
                        'kpi4' => [
                            'title' => 'Najdochodowszy',
                            'value' => $mostProfitable ? $mostProfitable['registration_number'] : 'Brak danych',
                            'subtitle' => $mostProfitable ? number_format($mostProfitable['revenue'], 2) . ' PLN' : '',
                            'icon' => 'currency-dollar'
                        ]
                    ];
                    break;

                case 'products':
                    // KPI dla produktów
                    $stmt = db()->prepare("
                        SELECT 
                            COUNT(DISTINCT product_name) as unique_products,
                            COUNT(*) as total_reservations,
                            AVG(total_gross) as avg_price
                        FROM reservations 
                        $whereClause
                    ");
                    $stmt->execute($params);
                    $productSummary = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Najpopularniejszy produkt
                    $stmt = db()->prepare("
                        SELECT product_name, COUNT(*) as reservations
                        FROM reservations 
                        $whereClause
                        GROUP BY product_name
                        ORDER BY reservations DESC
                        LIMIT 1
                    ");
                    $stmt->execute($params);
                    $topProduct = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Najdroższy produkt (średnia cena)
                    $stmt = db()->prepare("
                        SELECT product_name, AVG(total_gross) as avg_price
                        FROM reservations 
                        $whereClause
                        GROUP BY product_name
                        ORDER BY avg_price DESC
                        LIMIT 1
                    ");
                    $stmt->execute($params);
                    $mostExpensiveProduct = $stmt->fetch(PDO::FETCH_ASSOC);

                    $data = [
                        'kpi1' => [
                            'title' => 'Rodzaje produktów',
                            'value' => $productSummary['unique_products'] ?? 0,
                            'icon' => 'grid'
                        ],
                        'kpi2' => [
                            'title' => 'Łączne rezerwacje',
                            'value' => $productSummary['total_reservations'] ?? 0,
                            'subtitle' => 'wszystkich produktów',
                            'icon' => 'cart'
                        ],
                        'kpi3' => [
                            'title' => 'Najpopularniejszy',
                            'value' => $topProduct ? $topProduct['product_name'] : 'Brak danych',
                            'subtitle' => $topProduct ? $topProduct['reservations'] . ' rezerwacji' : '',
                            'icon' => 'star'
                        ],
                        'kpi4' => [
                            'title' => 'Najdroższy (śred.)',
                            'value' => $mostExpensiveProduct ? $mostExpensiveProduct['product_name'] : 'Brak danych',
                            'subtitle' => $mostExpensiveProduct ? number_format($mostExpensiveProduct['avg_price'], 2) . ' PLN' : '',
                            'icon' => 'gem'
                        ]
                    ];
                    break;

                case 'locations':
                    // KPI dla lokalizacji
                    $stmt = db()->prepare("
                        SELECT 
                            COUNT(DISTINCT pickup_location) as unique_locations,
                            COUNT(*) as total_pickups,
                            SUM(total_gross) as location_revenue
                        FROM reservations 
                        $whereClause
                    ");
                    $stmt->execute($params);
                    $locationSummary = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Najpopularniejsza lokalizacja
                    $stmt = db()->prepare("
                        SELECT pickup_location, COUNT(*) as pickups
                        FROM reservations 
                        $whereClause
                        GROUP BY pickup_location
                        ORDER BY pickups DESC
                        LIMIT 1
                    ");
                    $stmt->execute($params);
                    $topLocation = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Najdochodowsza lokalizacja
                    $stmt = db()->prepare("
                        SELECT pickup_location, SUM(total_gross) as revenue
                        FROM reservations 
                        $whereClause
                        GROUP BY pickup_location
                        ORDER BY revenue DESC
                        LIMIT 1
                    ");
                    $stmt->execute($params);
                    $mostProfitableLocation = $stmt->fetch(PDO::FETCH_ASSOC);

                    $data = [
                        'kpi1' => [
                            'title' => 'Lokalizacje aktywne',
                            'value' => $locationSummary['unique_locations'] ?? 0,
                            'icon' => 'geo-alt'
                        ],
                        'kpi2' => [
                            'title' => 'Odbiory łącznie',
                            'value' => $locationSummary['total_pickups'] ?? 0,
                            'subtitle' => 'ze wszystkich lokalizacji',
                            'icon' => 'map'
                        ],
                        'kpi3' => [
                            'title' => 'Najpopularniejsza',
                            'value' => $topLocation ? $topLocation['pickup_location'] : 'Brak danych',
                            'subtitle' => $topLocation ? $topLocation['pickups'] . ' odbiorów' : '',
                            'icon' => 'star'
                        ],
                        'kpi4' => [
                            'title' => 'Najdochodowsza',
                            'value' => $mostProfitableLocation ? $mostProfitableLocation['pickup_location'] : 'Brak danych',
                            'subtitle' => $mostProfitableLocation ? number_format($mostProfitableLocation['revenue'], 2) . ' PLN' : '',
                            'icon' => 'currency-dollar'
                        ]
                    ];
                    break;

                case 'revenue_daily':
                case 'revenue_monthly':
                    // KPI dla przychodów
                    $stmt = db()->prepare("
                        SELECT 
                            COUNT(*) as total_reservations,
                            SUM(total_gross) as total_revenue,
                            AVG(total_gross) as avg_revenue,
                            MAX(total_gross) as max_revenue,
                            MIN(total_gross) as min_revenue
                        FROM reservations 
                        $whereClause
                    ");
                    $stmt->execute($params);
                    $revenueSummary = $stmt->fetch(PDO::FETCH_ASSOC);

                    $timeUnit = $kpiType === 'revenue_daily' ? 'dziennie' : 'miesięcznie';

                    $data = [
                        'kpi1' => [
                            'title' => 'Łączny przychód',
                            'value' => number_format($revenueSummary['total_revenue'] ?? 0, 2) . ' PLN',
                            'icon' => 'currency-dollar'
                        ],
                        'kpi2' => [
                            'title' => 'Liczba transakcji',
                            'value' => $revenueSummary['total_reservations'] ?? 0,
                            'subtitle' => $timeUnit,
                            'icon' => 'receipt'
                        ],
                        'kpi3' => [
                            'title' => 'Średnia wartość',
                            'value' => number_format($revenueSummary['avg_revenue'] ?? 0, 2) . ' PLN',
                            'subtitle' => 'na transakcję',
                            'icon' => 'bar-chart-fill'
                        ],
                        'kpi4' => [
                            'title' => 'Najwyższa transakcja',
                            'value' => number_format($revenueSummary['max_revenue'] ?? 0, 2) . ' PLN',
                            'subtitle' => 'w okresie',
                            'icon' => 'trophy'
                        ]
                    ];
                    break;
            }
            break;

        case 'vehicles':
            if (!empty($product)) {
                // Gdy filtrujemy po produkcie, pokazujemy wszystkie egzemplarze tego modelu
                // oraz wszystkie rezerwacje powiązane z tym modelem (nawet bez vehicle_id)

                // Część 1: Egzemplarze z rezerwacjami
                $stmt1 = db()->prepare("
                    SELECT 
                        r.vehicle_id,
                        v.vin,
                        v.registration_number,
                        p.name as model,
                        COUNT(*) as reservations,
                        SUM(r.total_gross) as revenue,
                        AVG(r.rental_days) as avg_days
                    FROM reservations r
                    LEFT JOIN vehicles v ON r.vehicle_id = v.id
                    LEFT JOIN products p ON v.product_id = p.id
                    WHERE r.pickup_at BETWEEN ? AND ?
                    AND r.vehicle_id IS NOT NULL
                    AND (r.product_name LIKE ? OR p.name LIKE ?)
                    " . (!empty($status) ? " AND r.status = ?" : "") . "
                    " . (!empty($location) ? " AND r.pickup_location LIKE ?" : "") . "
                    GROUP BY r.vehicle_id, v.vin, v.registration_number, p.name
                ");

                $params1 = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59', '%' . $product . '%', '%' . $product . '%'];
                if (!empty($status)) $params1[] = $status;
                if (!empty($location)) $params1[] = '%' . $location . '%';

                $stmt1->execute($params1);
                $vehiclesWithReservations = $stmt1->fetchAll(PDO::FETCH_ASSOC);

                // Część 2: Wszystkie egzemplarze danego modelu (nawet bez rezerwacji)
                $stmt2 = db()->prepare("
                    SELECT 
                        v.id as vehicle_id,
                        v.vin,
                        v.registration_number,
                        p.name as model,
                        0 as reservations,
                        0 as revenue,
                        0 as avg_days
                    FROM vehicles v
                    LEFT JOIN products p ON v.product_id = p.id
                    WHERE p.name LIKE ?
                    AND v.id NOT IN (
                        SELECT DISTINCT r.vehicle_id 
                        FROM reservations r 
                        WHERE r.vehicle_id IS NOT NULL 
                        AND r.pickup_at BETWEEN ? AND ?
                        AND (r.product_name LIKE ? OR EXISTS(
                            SELECT 1 FROM vehicles v2 
                            LEFT JOIN products p2 ON v2.product_id = p2.id 
                            WHERE v2.id = r.vehicle_id AND p2.name LIKE ?
                        ))
                    )
                ");

                $params2 = ['%' . $product . '%', $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59', '%' . $product . '%', '%' . $product . '%'];
                $stmt2->execute($params2);
                $vehiclesWithoutReservations = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                // Połącz dane
                $data = array_merge($vehiclesWithReservations, $vehiclesWithoutReservations);

                // Sortuj po liczbie rezerwacji
                usort($data, function ($a, $b) {
                    return $b['reservations'] - $a['reservations'];
                });
            } else {
                // Standardowe zapytanie gdy nie ma filtra produktu
                $vehicleWhereConditions = ["r.pickup_at BETWEEN ? AND ?", "r.vehicle_id IS NOT NULL"];
                $vehicleParams = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

                if (!empty($status)) {
                    $vehicleWhereConditions[] = "r.status = ?";
                    $vehicleParams[] = $status;
                }

                if (!empty($location)) {
                    $vehicleWhereConditions[] = "r.pickup_location LIKE ?";
                    $vehicleParams[] = '%' . $location . '%';
                }

                $vehicleWhereClause = 'WHERE ' . implode(' AND ', $vehicleWhereConditions);

                $stmt = db()->prepare("
                    SELECT 
                        r.vehicle_id,
                        v.vin,
                        v.registration_number,
                        p.name as model,
                        COUNT(*) as reservations,
                        SUM(r.total_gross) as revenue,
                        AVG(r.rental_days) as avg_days
                    FROM reservations r
                    LEFT JOIN vehicles v ON r.vehicle_id = v.id
                    LEFT JOIN products p ON v.product_id = p.id
                    $vehicleWhereClause
                    GROUP BY r.vehicle_id, v.vin, v.registration_number, p.name
                    ORDER BY reservations DESC
                    LIMIT 20
                ");
                $stmt->execute($vehicleParams);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            break;

        case 'classes':
            $stmt = db()->prepare("
                SELECT 
                    p.category as class,
                    COUNT(*) as reservations,
                    SUM(r.total_gross) as revenue,
                    AVG(r.rental_days) as avg_days
                FROM reservations r
                LEFT JOIN vehicles v ON r.vehicle_id = v.id
                LEFT JOIN products p ON v.product_id = p.id
                $whereClause
                GROUP BY p.category
                ORDER BY reservations DESC
                LIMIT 10
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'incidents':
            // Używamy oddzielnych parametrów dla incydentów (incident_date zamiast pickup_at)
            $incidentWhere = "incident_date BETWEEN ? AND ?";
            $incidentParams = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

            $stmt = db()->prepare("
                SELECT 
                    vi.vehicle_id,
                    v.registration_number,
                    p.name as model,
                    vi.damage_desc,
                    vi.fault,
                    vi.repair_cost,
                    vi.incident_date,
                    vi.location,
                    vi.driver_name,
                    CASE 
                        WHEN vi.fault = 'our' THEN 'Nasza wina'
                        WHEN vi.fault = 'other' THEN 'Wina trzeciej strony'
                        WHEN vi.fault = 'shared' THEN 'Wina wspólna'
                        ELSE 'Nieustalona'
                    END as fault_label
                FROM vehicle_incidents vi
                LEFT JOIN vehicles v ON vi.vehicle_id = v.id
                LEFT JOIN products p ON v.product_id = p.id
                WHERE $incidentWhere
                ORDER BY vi.incident_date DESC
                LIMIT 50
            ");
            $stmt->execute($incidentParams);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'services':
            // Używamy service_date zamiast pickup_at
            $serviceWhere = "service_date BETWEEN ? AND ?";
            $serviceParams = [$dateFrom, $dateTo];

            $stmt = db()->prepare("
                SELECT 
                    vs.vehicle_id,
                    v.registration_number,
                    p.name as model,
                    vs.service_date,
                    vs.workshop_name,
                    vs.cost_total,
                    vs.issues_found,
                    vs.actions_taken,
                    vs.odometer_km,
                    vs.invoice_no
                FROM vehicle_services vs
                LEFT JOIN vehicles v ON vs.vehicle_id = v.id
                LEFT JOIN products p ON v.product_id = p.id
                WHERE $serviceWhere
                ORDER BY vs.service_date DESC
                LIMIT 50
            ");
            $stmt->execute($serviceParams);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
        case 'revenue_daily':
            $stmt = db()->prepare("
                SELECT 
                    DATE(pickup_at) as date,
                    COUNT(*) as reservations,
                    SUM(total_gross) as revenue
                FROM reservations 
                $whereClause
                GROUP BY DATE(pickup_at)
                ORDER BY DATE(pickup_at)
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'revenue_monthly':
            $stmt = db()->prepare("
                SELECT 
                    DATE_FORMAT(pickup_at, '%Y-%m') as month,
                    DATE_FORMAT(pickup_at, '%M %Y') as month_label,
                    COUNT(*) as reservations,
                    SUM(total_gross) as revenue
                FROM reservations 
                $whereClause
                GROUP BY DATE_FORMAT(pickup_at, '%Y-%m')
                ORDER BY month
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'products':
            $stmt = db()->prepare("
                SELECT 
                    product_name as name,
                    COUNT(*) as reservations,
                    SUM(total_gross) as revenue,
                    AVG(total_gross) as avg_value,
                    AVG(rental_days) as avg_days
                FROM reservations 
                $whereClause
                GROUP BY product_name
                ORDER BY reservations DESC
                LIMIT 20
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'locations':
            $stmt = db()->prepare("
                SELECT 
                    pickup_location as location,
                    COUNT(*) as pickups,
                    SUM(total_gross) as revenue
                FROM reservations 
                $whereClause
                GROUP BY pickup_location
                ORDER BY pickups DESC
                LIMIT 15
            ");
            $stmt->execute($params);
            $pickups = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = db()->prepare("
                SELECT 
                    dropoff_location as location,
                    COUNT(*) as dropoffs
                FROM reservations 
                $whereClause
                GROUP BY dropoff_location
                ORDER BY dropoffs DESC
                LIMIT 15
            ");
            $stmt->execute($params);
            $dropoffs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = ['pickups' => $pickups, 'dropoffs' => $dropoffs];
            break;

        case 'status':
            $stmt = db()->prepare("
                SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(total_gross) as revenue
                FROM reservations 
                $whereClause
                GROUP BY status
                ORDER BY count DESC
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'vehicles':
            $stmt = db()->query("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM vehicles 
                GROUP BY status
                ORDER BY count DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        default: // summary
            // Podstawowe statystyki
            $stmt = db()->prepare("
                SELECT 
                    COUNT(*) as total_reservations,
                    SUM(total_gross) as total_revenue,
                    AVG(total_gross) as avg_revenue,
                    AVG(rental_days) as avg_days,
                    COUNT(DISTINCT product_name) as unique_products,
                    COUNT(DISTINCT pickup_location) as unique_locations
                FROM reservations 
                $whereClause
            ");
            $stmt->execute($params);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);

            // Statystyki statusów
            $stmt = db()->prepare("
                SELECT status, COUNT(*) as count
                FROM reservations 
                $whereClause
                GROUP BY status
            ");
            $stmt->execute($params);
            $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Top produkty
            $stmt = db()->prepare("
                SELECT product_name as name, COUNT(*) as count, SUM(total_gross) as revenue
                FROM reservations 
                $whereClause
                GROUP BY product_name
                ORDER BY count DESC
                LIMIT 5
            ");
            $stmt->execute($params);
            $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Przychód miesięczny dla wykresu
            $stmt = db()->prepare("
                SELECT 
                    DATE_FORMAT(pickup_at, '%Y-%m') as month,
                    DATE_FORMAT(pickup_at, '%M %Y') as month_label,
                    SUM(total_gross) as revenue
                FROM reservations 
                $whereClause
                GROUP BY DATE_FORMAT(pickup_at, '%Y-%m')
                ORDER BY month
            ");
            $stmt->execute($params);
            $monthlyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [
                'summary' => $summary,
                'status_stats' => $statusStats,
                'top_products' => $topProducts,
                'monthly_revenue' => $monthlyRevenue,
                'filters_applied' => [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'product' => $product,
                    'status' => $status,
                    'location' => $location
                ]
            ];
            break;
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'filters' => [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'product' => $product,
            'status' => $status,
            'location' => $location,
            'report_type' => $reportType
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
