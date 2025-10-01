<!-- WYSZUKIWARKA -->
<section aria-label="Wyszukiwarka" id="offer" class="wyszukiwarkaCL pt-4">
    <div class="container py-4 search-wrapper ">
        <div class="card p-3 p-md-4" style="border-radius:18px; box-shadow:0 6px 24px rgba(0,0,0,.08); border:1px solid rgba(0,0,0,.06); overflow: visible;">
            <form id="search-form" action="/search" method="get" novalidate>

                <!-- Główna linia -->
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-3">
                        <label class="form-label mb-1">Miejsce odbioru</label>
                        <select class="form-select" id="pickupLocation" name="pickup_location" required>
                            <option value="" disabled selected>Wybierz...</option>
                            <option>Warszawa Lotnisko</option>
                            <option>Warszawa Centrum</option>
                            <option>Kraków</option>
                            <option>Gdańsk</option>
                            <option>Wrocław</option>
                            <option>Poznań</option>
                            <option>Katowice</option>
                        </select>
                    </div>

                    <div class="col-12 col-lg-3">
                        <label class="form-label mb-1">Miejsce zwrotu</label>
                        <select class="form-select" id="dropoffLocation" name="dropoff_location" required>
                            <option value="" disabled selected>Wybierz...</option>
                            <option>To samo co odbiór</option>
                            <option>Warszawa Lotnisko</option>
                            <option>Warszawa Centrum</option>
                            <option>Kraków</option>
                            <option>Gdańsk</option>
                            <option>Wrocław</option>
                            <option>Poznań</option>
                            <option>Katowice</option>
                        </select>
                    </div>

                    <div class="col-12 col-lg-3">
                        <label class="form-label mb-1">Data odbioru</label>
                        <input type="datetime-local" class="form-control" id="pickupDateTime" name="pickup_datetime" required>
                    </div>

                    <div class="col-12 col-lg-3">
                        <label class="form-label mb-1">Data zwrotu</label>
                        <input type="datetime-local" class="form-control" id="dropoffDateTime" name="dropoff_datetime" required>
                    </div>
                </div>

                <hr class="my-3" />

                <!-- Filtry + przycisk po prawej -->
                <div class="d-flex align-items-center flex-wrap gap-2" style="overflow: visible;">
                    <!-- Grupa chipów -->
                    <div class="d-flex flex-wrap gap-2 flex-grow-1" style="overflow: visible;">

                        <!-- Typ pojazdu -->
                        <div class="dropdown" data-bs-display="static" style="overflow: visible;">
                            <button class="btn btn-light rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Typ pojazdu
                            </button>
                            <ul class="dropdown-menu shadow mt-2" style="position:absolute; inset:auto auto 0 0; transform:translateY(100%);">
                                <li><a class="dropdown-item" data-value="">Dowolny</a></li>
                                <li><a class="dropdown-item" data-value="economy">Miejski/Economy</a></li>
                                <li><a class="dropdown-item" data-value="compact">Kompakt</a></li>
                                <li><a class="dropdown-item" data-value="suv">SUV</a></li>
                                <li><a class="dropdown-item" data-value="van">Van</a></li>
                                <li><a class="dropdown-item" data-value="premium">Premium</a></li>
                            </ul>
                            <input type="hidden" name="class" id="classHidden">
                        </div>

                        <!-- Skrzynia biegów -->
                        <div class="dropdown" data-bs-display="static" style="overflow: visible;">
                            <button class="btn btn-light rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Skrzynia biegów
                            </button>
                            <ul class="dropdown-menu shadow mt-2" style="position:absolute; inset:auto auto 0 0; transform:translateY(100%);">
                                <li><a class="dropdown-item" data-value="">Dowolna</a></li>
                                <li><a class="dropdown-item" data-value="manual">Manualna</a></li>
                                <li><a class="dropdown-item" data-value="automatic">Automatyczna</a></li>
                            </ul>
                            <input type="hidden" name="transmission" id="transHidden">
                        </div>

                        <!-- Minimalna liczba miejsc -->
                        <div class="dropdown" data-bs-display="static" style="overflow: visible;">
                            <button class="btn btn-light rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Minimalna liczba miejsc
                            </button>
                            <ul class="dropdown-menu shadow mt-2" style="position:absolute; inset:auto auto 0 0; transform:translateY(100%);">
                                <li><a class="dropdown-item" data-value="">Dowolna</a></li>
                                <li><a class="dropdown-item" data-value="2">2</a></li>
                                <li><a class="dropdown-item" data-value="4">4</a></li>
                                <li><a class="dropdown-item" data-value="5">5</a></li>
                                <li><a class="dropdown-item" data-value="7">7</a></li>
                                <li><a class="dropdown-item" data-value="9">9</a></li>
                            </ul>
                            <input type="hidden" name="seats_min" id="seatsHidden">
                        </div>

                        <!-- Rodzaj paliwa -->
                        <div class="dropdown" data-bs-display="static" style="overflow: visible;">
                            <button class="btn btn-light rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Rodzaj paliwa
                            </button>
                            <ul class="dropdown-menu shadow mt-2" style="position:absolute; inset:auto auto 0 0; transform:translateY(100%);">
                                <li><a class="dropdown-item" data-value="">Dowolny</a></li>
                                <li><a class="dropdown-item" data-value="benzyna">Benzyna</a></li>
                                <li><a class="dropdown-item" data-value="diesel">Diesel</a></li>
                                <li><a class="dropdown-item" data-value="hybryda">Hybryda</a></li>
                                <li><a class="dropdown-item" data-value="elektryczny">Elektryczny</a></li>
                            </ul>
                            <input type="hidden" name="fuel" id="fuelHidden">
                        </div>
                    </div>

                    <!-- CTA wyrównany do prawej -->
                    <button class="btn rounded-pill px-4 ms-auto" type="submit"
                        style="background:#188f45; border-color:#255b35; color:#fff; white-space:nowrap;">
                        Pokaż samochody
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>