<?php
// Importy do sklepu: CSV/XLSX dla modeli, egzemplarzy, lokalizacji, klas, typów, dodatków
?>
<div class="card">
    <div class="card-header" style="background: var(--gradient-primary); color: #fff;">
        <h3 class="h6 mb-0"><i class="bi bi-arrow-left-right me-2"></i>Import / Export</h3>
    </div>
    <div class="card-body">
        <div class="row">
        <div class="col-md-6">
            <h6>Eksport danych</h6>
            <form method="post" action="pages/staff/settings/export-shop.php" target="_blank">
                <div class="mb-3">
                    <label for="export_type" class="form-label">Typ danych do eksportu</label>
                    <select class="form-select" id="export_type" name="export_type" required>
                        <option value="models">Modele pojazdów</option>
                        <option value="vehicles">Egzemplarze</option>
                        <option value="locations">Lokalizacje</option>
                        <option value="classes">Klasy samochodów</option>
                        <option value="types">Typy samochodów</option>
                        <option value="extras">Dodatki</option>
                    </select>
                </div>
                <button type="submit" name="export_csv" class="btn btn-outline-primary">Eksportuj do CSV</button>
                <button type="submit" name="export_xlsx" class="btn btn-outline-success ms-2">Eksportuj do XLSX</button>
            </form>
        </div>
            <div class="col-md-6">
                <h6>Import danych</h6>
                <form method="post" enctype="multipart/form-data" action="pages/staff/settings/import-shop.php" target="_blank">
                    <div class="mb-3">
                        <label for="import_file" class="form-label">Wybierz plik CSV lub XLSX</label>
                        <input type="file" class="form-control" id="import_file" name="import_file" accept=".csv,.xlsx" required>
                    </div>
                    <div class="mb-3">
                        <label for="import_type" class="form-label">Typ danych do importu</label>
                        <select class="form-select" id="import_type" name="import_type" required onchange="showSample(this.value)">
                            <option value="models">Modele pojazdów</option>
                            <option value="vehicles">Egzemplarze</option>
                            <option value="locations">Lokalizacje</option>
                            <option value="classes">Klasy samochodów</option>
                            <option value="types">Typy samochodów</option>
                            <option value="extras">Dodatki</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Importuj</button>
                </form>
                <div class="mt-3">
                    <h6>Przykładowy plik do importu</h6>
                    <div id="sample-file" style="font-family:monospace;font-size:0.95em;background:#f8f9fa;border-radius:6px;padding:10px;white-space:pre;overflow-x:auto;"></div>
                </div>
                <script>
                const samples = {
                    models: `id,name,sku,price,category,status\n1,Ford Focus,FOC-001,90000,osobowe,active`,
                    vehicles: `id,product_id,registration_number,vin,status\n1,1,KR12345,WF0XXXGCHX8L12345,available`,
                    locations: `id,name,slug,sort_order,status\n1,Warszawa,warszawa,1,active`,
                    classes: `id,name,slug,sort_order,status\n1,Premium,premium,1,active`,
                    types: `id,name,slug,sort_order,status\n1,Sedan,sedan,1,active`,
                    extras: `id,name,slug,sort_order,status,price,charge_type\n1,Fotelik dziecięcy,fotelik,1,active,50,once`
                };
                function showSample(type) {
                    document.getElementById('sample-file').textContent = samples[type] || '';
                }
                document.addEventListener('DOMContentLoaded', function() {
                    showSample(document.getElementById('import_type').value);
                });
                </script>
    </div>
</div>
