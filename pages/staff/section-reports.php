<?php
// expects: $reports 
$reports = $reports ?? [
    'revenue_today' => 0.0,
    'orders_today' => 0,
    'top_product' => __('no_data', 'admin', 'Brak danych'),
    'monthly_revenue' => [],
    'vehicle_stats' => [],
    'top_products' => [],
    'user_stats' => ['total_users' => 0, 'new_this_week' => 0, 'active_this_week' => 0]
];

$monthlyRevenueJson = json_encode(array_column($reports['monthly_revenue'], 'revenue'));
$monthlyLabelsJson = json_encode(array_column($reports['monthly_revenue'], 'month'));
?>

<div class="card section-reports">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="h6 mb-0"><i class="bi bi-bar-chart-line me-2"></i><?= __('reports', 'admin', 'Raporty') ?></h2>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-clean btn-sm" onclick="exportReports('csv')">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>CSV
            </button>
            <button type="button" class="btn btn-clean btn-sm" onclick="exportReports('pdf')">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </button>
        </div>
    </div>

    <!-- Panel filtrów -->
    <div class="filters-panel p-3 border-bottom">
        <form id="reportsFilters" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-medium"><?= __('date_from', 'admin', 'Data od') ?></label>
                <input type="date" class="form-control form-control-sm" name="date_from" id="date_from"
                    value="<?= date('Y-m-01', strtotime('-2 months')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-medium"><?= __('date_to', 'admin', 'Data do') ?></label>
                <input type="date" class="form-control form-control-sm" name="date_to" id="date_to"
                    value="<?= date('Y-m-t') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium"><?= __('product', 'admin', 'Produkt') ?></label>
                <select class="form-select form-select-sm" name="product" id="product">
                    <option value=""><?= __('all_products', 'admin', 'Wszystkie produkty') ?></option>
                    <?php
                    $products = db()->query("SELECT DISTINCT product_name FROM reservations WHERE product_name IS NOT NULL ORDER BY product_name")->fetchAll();
                    foreach ($products as $prod): ?>
                        <option value="<?= htmlspecialchars($prod['product_name']) ?>"><?= htmlspecialchars($prod['product_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium"><?= __('status', 'admin', 'Status') ?></label>
                <select class="form-select form-select-sm" name="status" id="status">
                    <option value=""><?= __('all', 'admin', 'Wszystkie') ?></option>
                    <option value="pending">Oczekująca</option>
                    <option value="confirmed">Potwierdzona</option>
                    <option value="cancelled">Anulowana</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium"><?= __('location', 'admin', 'Lokalizacja') ?></label>
                <select class="form-select form-select-sm" name="location" id="location">
                    <option value=""><?= __('all_locations', 'admin', 'Wszystkie lokalizacje') ?></option>
                    <?php
                    $locations = db()->query("SELECT DISTINCT pickup_location FROM reservations WHERE pickup_location IS NOT NULL ORDER BY pickup_location")->fetchAll();
                    foreach ($locations as $loc): ?>
                        <option value="<?= htmlspecialchars($loc['pickup_location']) ?>"><?= htmlspecialchars($loc['pickup_location']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <div class="mt-3">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn-filter-type active" data-report-type="summary" onclick="setReportType('summary')">
                    <i class="bi bi-speedometer2 me-1"></i>Podsumowanie
                </button>
                <button type="button" class="btn-filter-type" data-report-type="revenue_daily" onclick="setReportType('revenue_daily')">
                    <i class="bi bi-graph-up me-1"></i>Przychód dzienny
                </button>
                <button type="button" class="btn-filter-type" data-report-type="revenue_monthly" onclick="setReportType('revenue_monthly')">
                    <i class="bi bi-calendar-month me-1"></i>Przychód miesięczny
                </button>
                <button type="button" class="btn-filter-type" data-report-type="products" onclick="setReportType('products')">
                    <i class="bi bi-box-seam me-1"></i>Produkty
                </button>
                <button type="button" class="btn-filter-type" data-report-type="locations" onclick="setReportType('locations')">
                    <i class="bi bi-geo-alt me-1"></i>Lokalizacje
                </button>
                <button type="button" class="btn-filter-type" data-report-type="vehicles" onclick="setReportType('vehicles')">
                    <i class="bi bi-truck me-1"></i>Pojazdy
                </button>
            </div>
            <button type="button" class="btn btn-primary btn-sm ms-2" onclick="applyFilters()">
                <i class="bi bi-funnel me-1"></i><?= __('apply_filters', 'admin', 'Zastosuj filtry') ?>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetFilters()">
                <i class="bi bi-arrow-clockwise me-1"></i><?= __('reset', 'admin', 'Resetuj') ?>
            </button>
        </div>
    </div>

    <div class="card-body">
        <!-- Loading indicator -->
        <div id="reportsLoading" class="text-center py-5" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Ładowanie...</span>
            </div>
            <div class="mt-2 text-muted">Generowanie raportu...</div>
        </div>

        <!-- Content area -->
        <div id="reportsContent">
            <!-- Główne statystyki - summary view -->
            <div id="summaryView">
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div class="stats-content">
                                <div class="stats-label"><?= __('revenue_today', 'admin', 'Przychód dziś') ?></div>
                                <div class="stats-value" id="revenue-today"><?= number_format((float)$reports['revenue_today'], 2, ',', ' ') ?> PLN</div>
                                <div class="stats-sublabel" id="revenue-change"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="bi bi-receipt"></i>
                            </div>
                            <div class="stats-content">
                                <div class="stats-label"><?= __('orders_today', 'admin', 'Rezerwacje') ?></div>
                                <div class="stats-value" id="orders-count"><?= (int)$reports['orders_today'] ?></div>
                                <div class="stats-sublabel" id="orders-subtitle">w wybranym okresie</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="bi bi-star"></i>
                            </div>
                            <div class="stats-content">
                                <div class="stats-label"><?= __('top_product', 'admin', 'Top produkt') ?></div>
                                <div class="stats-value" id="top-product"><?= htmlspecialchars($reports['top_product']) ?></div>
                                <div class="stats-sublabel" id="top-product-count"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            <div class="stats-content">
                                <div class="stats-label"><?= __('avg_value', 'admin', 'Średnia wartość') ?></div>
                                <div class="stats-value" id="avg-value">0 PLN</div>
                                <div class="stats-sublabel" id="avg-days">średnio dni</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wykresy -->
                <div class="row g-4 mb-4">
                    <div class="col-12 col-lg-8">
                        <div class="chart-card">
                            <div class="chart-header">
                                <h5 id="chart-title"><?= __('monthly_revenue', 'admin', 'Przychód miesięczny') ?></h5>
                                <div class="chart-controls">
                                    <button type="button" class="btn btn-clean btn-sm" onclick="toggleChartType()">
                                        <i class="bi bi-bar-chart" id="chart-toggle-icon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="mainChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="chart-card">
                            <div class="chart-header">
                                <h5 id="pie-chart-title"><?= __('status_distribution', 'admin', 'Rozkład statusów') ?></h5>
                            </div>
                            <div class="chart-container">
                                <canvas id="pieChart" width="300" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data table area -->
            <div class="row">
                <div class="col-12">
                    <div class="table-card">
                        <div class="table-header">
                            <h5 id="table-title"><?= __('detailed_data', 'admin', 'Szczegółowe dane') ?></h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm" id="dataTable">
                                <thead id="tableHead">
                                    <!-- Headers will be populated by JavaScript -->
                                </thead>
                                <tbody id="tableBody">
                                    <!-- Data will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Integration -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Globalne zmienne
    let currentReportType = 'summary';
    let mainChart = null;
    let pieChart = null;
    let isBarChart = true;
    let currentData = null;

    // Inicjalne dane dla wykresów
    const initialMonthlyData = <?= $monthlyRevenueJson ?>;
    const initialMonthlyLabels = <?= $monthlyLabelsJson ?>;
    const initialVehicleData = <?= json_encode($reports['vehicle_stats']) ?>;

    // === FILTRY I ŁADOWANIE DANYCH ===
    function applyFilters() {
        const formData = new FormData(document.getElementById('reportsFilters'));
        const params = new URLSearchParams();

        for (let [key, value] of formData.entries()) {
            if (value) params.append(key, value);
        }
        params.append('report_type', currentReportType);

        showLoading(true);

        fetch('api/reports-data.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentData = data.data;
                    updateView();
                    // Pokazuj powiadomienie tylko jeśli nie zmieniamy języka
                    if (!window.languageJustChanged) {
                        showNotification('Raport został zaktualizowany', 'success');
                    }
                } else {
                    showNotification('Błąd ładowania danych: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showNotification('Błąd połączenia: ' + error.message, 'error');
            })
            .finally(() => {
                showLoading(false);
            });
    }

    function setReportType(type) {
        // Update active button
        document.querySelectorAll('[data-report-type]').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-report-type="${type}"]`).classList.add('active');

        currentReportType = type;
        applyFilters();
    }

    function resetFilters() {
        document.getElementById('reportsFilters').reset();
        document.getElementById('date_from').value = '<?= date('Y-m-01', strtotime('-2 months')) ?>';
        document.getElementById('date_to').value = '<?= date('Y-m-t') ?>';

        setReportType('summary');
    }

    function showLoading(show) {
        document.getElementById('reportsLoading').style.display = show ? 'block' : 'none';
        document.getElementById('reportsContent').style.display = show ? 'none' : 'block';
    }

    function showNotification(message, type = 'info') {
        // Simple notification system
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
        document.body.appendChild(notification);

        setTimeout(() => notification.remove(), 5000);
    }

    // === AKTUALIZACJA WIDOKU ===
    function updateView() {
        if (!currentData) return;

        switch (currentReportType) {
            case 'summary':
                updateSummaryView();
                break;
            case 'revenue_daily':
            case 'revenue_monthly':
                updateRevenueView();
                break;
            case 'products':
                updateProductsView();
                break;
            case 'locations':
                updateLocationsView();
                break;
            case 'status':
                updateStatusView();
                break;
            case 'vehicles':
                updateVehiclesView();
                break;
        }
    }

    function updateSummaryView() {
        if (!currentData.summary) return;

        const summary = currentData.summary;

        // Update stats cards
        document.getElementById('revenue-today').textContent =
            Number(summary.total_revenue || 0).toLocaleString('pl-PL', {
                minimumFractionDigits: 2
            }) + ' PLN';
        document.getElementById('orders-count').textContent = summary.total_reservations || 0;
        document.getElementById('avg-value').textContent =
            Number(summary.avg_revenue || 0).toLocaleString('pl-PL', {
                minimumFractionDigits: 0
            }) + ' PLN';
        document.getElementById('avg-days').textContent = Math.round(summary.avg_days || 0) + ' dni średnio';

        // Update top product
        if (currentData.top_products && currentData.top_products.length > 0) {
            document.getElementById('top-product').textContent = currentData.top_products[0].name;
            document.getElementById('top-product-count').textContent = currentData.top_products[0].count + ' rezerwacji';
        }

        // Update charts
        updateMainChart(currentData.monthly_revenue, 'month_label', 'revenue', 'Przychód miesięczny');
        updatePieChart(currentData.status_stats, 'status', 'count', 'Rozkład statusów');

        // Update table
        updateTable(currentData.top_products, [{
                key: 'name',
                label: 'Produkt'
            },
            {
                key: 'count',
                label: 'Rezerwacje',
                class: 'text-center'
            },
            {
                key: 'revenue',
                label: 'Przychód (PLN)',
                class: 'text-end',
                format: 'currency'
            }
        ]);
    }

    function updateRevenueView() {
        const labelKey = currentReportType === 'revenue_daily' ? 'date' : 'month_label';
        const title = currentReportType === 'revenue_daily' ? 'Przychód dzienny' : 'Przychód miesięczny';

        updateMainChart(currentData, labelKey, 'revenue', title);
        updatePieChart(currentData, labelKey, 'reservations', 'Liczba rezerwacji');

        updateTable(currentData, [{
                key: labelKey,
                label: 'Okres'
            },
            {
                key: 'reservations',
                label: 'Rezerwacje',
                class: 'text-center'
            },
            {
                key: 'revenue',
                label: 'Przychód (PLN)',
                class: 'text-end',
                format: 'currency'
            }
        ]);
    }

    function updateProductsView() {
        updateMainChart(currentData.slice(0, 10), 'name', 'revenue', 'Przychód z produktów');
        updatePieChart(currentData.slice(0, 8), 'name', 'reservations', 'Liczba rezerwacji');

        updateTable(currentData, [{
                key: 'name',
                label: 'Produkt'
            },
            {
                key: 'reservations',
                label: 'Rezerwacje',
                class: 'text-center'
            },
            {
                key: 'revenue',
                label: 'Przychód (PLN)',
                class: 'text-end',
                format: 'currency'
            },
            {
                key: 'avg_value',
                label: 'Śr. wartość',
                class: 'text-end',
                format: 'currency'
            },
            {
                key: 'avg_days',
                label: 'Śr. dni',
                class: 'text-center',
                format: 'decimal'
            }
        ]);
    }

    function updateLocationsView() {
        updateMainChart(currentData.pickups.slice(0, 10), 'location', 'revenue', 'Przychód z lokalizacji');
        updatePieChart(currentData.pickups.slice(0, 8), 'location', 'pickups', 'Liczba odbiorów');

        updateTable(currentData.pickups, [{
                key: 'location',
                label: 'Lokalizacja'
            },
            {
                key: 'pickups',
                label: 'Odbiory',
                class: 'text-center'
            },
            {
                key: 'revenue',
                label: 'Przychód (PLN)',
                class: 'text-end',
                format: 'currency'
            }
        ]);
    }

    function updateStatusView() {
        updateMainChart(currentData, 'status', 'revenue', 'Przychód wg statusu');
        updatePieChart(currentData, 'status', 'count', 'Rozkład statusów');

        updateTable(currentData, [{
                key: 'status',
                label: 'Status'
            },
            {
                key: 'count',
                label: 'Liczba',
                class: 'text-center'
            },
            {
                key: 'revenue',
                label: 'Przychód (PLN)',
                class: 'text-end',
                format: 'currency'
            }
        ]);
    }

    function updateVehiclesView() {
        updateMainChart(currentData, 'status', 'count', 'Liczba pojazdów wg statusu');
        updatePieChart(currentData, 'status', 'count', 'Rozkład statusów pojazdów');

        updateTable(currentData, [{
                key: 'status',
                label: 'Status pojazdu'
            },
            {
                key: 'count',
                label: 'Liczba',
                class: 'text-center'
            }
        ]);
    }

    // === WYKRESY ===
    function updateMainChart(data, labelKey, valueKey, title) {
        if (!data || !Array.isArray(data)) return;

        document.getElementById('chart-title').textContent = title;

        if (mainChart) {
            mainChart.destroy();
        }

        const ctx = document.getElementById('mainChart').getContext('2d');
        const labels = data.map(item => item[labelKey] || '');
        const values = data.map(item => parseFloat(item[valueKey]) || 0);

        mainChart = new Chart(ctx, {
            type: isBarChart ? 'bar' : 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: title,
                    data: values,
                    backgroundColor: isBarChart ? 'rgba(102, 126, 234, 0.8)' : 'rgba(102, 126, 234, 0.1)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2,
                    borderRadius: isBarChart ? 8 : 0,
                    fill: !isBarChart,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('pl-PL') + (valueKey === 'revenue' ? ' PLN' : '');
                            }
                        }
                    }
                }
            }
        });
    }

    function updatePieChart(data, labelKey, valueKey, title) {
        if (!data || !Array.isArray(data)) return;

        document.getElementById('pie-chart-title').textContent = title;

        if (pieChart) {
            pieChart.destroy();
        }

        const ctx = document.getElementById('pieChart').getContext('2d');
        const labels = data.map(item => item[labelKey] || '');
        const values = data.map(item => parseFloat(item[valueKey]) || 0);

        const colors = [
            'rgba(16, 185, 129, 0.8)', 'rgba(245, 158, 11, 0.8)', 'rgba(239, 68, 68, 0.8)',
            'rgba(107, 114, 128, 0.8)', 'rgba(139, 92, 246, 0.8)', 'rgba(236, 72, 153, 0.8)',
            'rgba(34, 197, 94, 0.8)', 'rgba(251, 113, 133, 0.8)'
        ];

        pieChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors.slice(0, values.length),
                    borderColor: colors.slice(0, values.length).map(c => c.replace('0.8', '1')),
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    function updateTable(data, columns) {
        if (!data || !Array.isArray(data)) return;

        const tableHead = document.getElementById('tableHead');
        const tableBody = document.getElementById('tableBody');

        // Update headers
        tableHead.innerHTML = '<tr>' + columns.map(col =>
            `<th class="${col.class || ''}">${col.label}</th>`
        ).join('') + '</tr>';

        // Update body
        tableBody.innerHTML = data.map((row, index) =>
            '<tr>' + columns.map(col => {
                let value = row[col.key] || '';

                if (col.format === 'currency') {
                    value = Number(value).toLocaleString('pl-PL', {
                        minimumFractionDigits: 2
                    }) + ' PLN';
                } else if (col.format === 'decimal') {
                    value = Number(value).toFixed(1);
                }

                if (col.key === 'name' && columns.length > 3) {
                    value = `<div class="d-flex align-items-center">
                    <div class="rank-badge">${index + 1}</div>
                    <div>${value}</div>
                </div>`;
                }

                return `<td class="${col.class || ''}">${value}</td>`;
            }).join('') + '</tr>'
        ).join('');
    }

    function toggleChartType() {
        isBarChart = !isBarChart;
        document.getElementById('chart-toggle-icon').className =
            isBarChart ? 'bi bi-graph-up' : 'bi bi-bar-chart';

        if (currentData) {
            updateView();
        }
    }

    // === EKSPORT ===
    function exportReports(format) {
        const formData = new FormData(document.getElementById('reportsFilters'));
        const params = new URLSearchParams();

        for (let [key, value] of formData.entries()) {
            if (value) params.append(key, value);
        }
        params.append('report_type', currentReportType);
        params.append('export_format', format);

        if (format === 'csv') {
            exportToCSV();
        } else if (format === 'pdf') {
            exportToPDF();
        }
    }

    function exportToCSV() {
        if (!currentData) {
            showNotification('Brak danych do eksportu', 'error');
            return;
        }

        let csvContent = '';
        let data = currentData;

        if (currentReportType === 'summary') {
            data = currentData.top_products || [];
            csvContent = 'Produkt,Rezerwacje,Przychód\\n';
            data.forEach(row => {
                csvContent += `"${row.name}",${row.count},${row.revenue}\\n`;
            });
        } else if (Array.isArray(data)) {
            const headers = Object.keys(data[0] || {});
            csvContent = headers.join(',') + '\\n';
            data.forEach(row => {
                csvContent += headers.map(h => `"${row[h] || ''}"`).join(',') + '\\n';
            });
        }

        const blob = new Blob([csvContent], {
            type: 'text/csv;charset=utf-8;'
        });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `raport_${currentReportType}_${new Date().toISOString().split('T')[0]}.csv`;
        link.click();

        showNotification('Raport CSV został pobrany', 'success');
    }

    function exportToPDF() {
        const formData = new FormData(document.getElementById('reportsFilters'));
        const params = new URLSearchParams();

        for (let [key, value] of formData.entries()) {
            if (value) params.append(key, value);
        }
        params.append('report_type', currentReportType);

        // Otwórz PDF w nowym oknie
        const url = 'api/export-pdf.php?' + params.toString();
        window.open(url, '_blank');

        showNotification('Raport PDF został otwarty w nowym oknie', 'success');
    }

    // === INICJALIZACJA ===
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize charts with default data
        setTimeout(() => {
            updateMainChart(<?= json_encode($reports['monthly_revenue']) ?>, 'month', 'revenue', 'Przychód miesięczny');

            const vehicleData = <?= json_encode($reports['vehicle_stats']) ?>;
            if (vehicleData.length > 0) {
                updatePieChart(vehicleData, 'status', 'count', 'Status pojazdów');
            }

            // Auto-apply filters on form change
            const filterInputs = document.querySelectorAll('#reportsFilters input, #reportsFilters select');
            filterInputs.forEach(input => {
                input.addEventListener('change', () => {
                    // Debounce automatic filtering
                    clearTimeout(window.filterTimeout);
                    window.filterTimeout = setTimeout(applyFilters, 1000);
                });
            });

            // Load initial data
            applyFilters();
        }, 100);
    });
</script>