# Copilot Instructions for Rental (Vehicle Rental Management System)

## Project Overview
- **Purpose:** Advanced PHP/MySQL system for managing vehicle rentals, fleet, locations, reservations, add-ons, services, incidents, and reporting.
- **Architecture:**
  - **Backend:** PHP (OOP, key classes in `classes/`)
  - **Frontend:** Bootstrap, FontAwesome, custom SCSS/JS (see `assets/`)
  - **Database:** MySQL, normalized tables for vehicles, locations, reservations, fees, users, etc.
  - **APIs:** PHP scripts in `api/` for data/report endpoints.

## Key Components & Patterns
- **Business Logic:**
  - `classes/FleetManager.php` – fleet/location logic
  - `classes/DepositManager.php` – deposit system
  - `classes/LocationFeeManager.php` – location-based fees
- **Reservation Workflow:**
  - Main flow: search → select vehicle → reserve (see `pages/checkout.php`, `pages/checkout-confirm.php`)
  - Each reservation is linked to a specific vehicle instance
- **Add-ons & Fees:**
  - Add-ons managed via `dict_terms`/`addons` tables
  - Location fees and deposits are calculated via manager classes
- **Reporting:**
  - Reports aggregate by vehicle, class, location, status, incident, service (see `api/report-data.php`, `scripts/seeders/add_test_data.php`)

## Developer Workflows
- **Setup:**
  1. Import SQL from `database/` into MySQL
  2. Configure DB in `includes/config.php`
  3. Install frontend deps: `npm install` (see `package.json`)
  4. Build SCSS: `npm run build`
  5. Seed test data: run `scripts/seeders/add_test_data.php`
- **Testing:**
  - Functional tests/scripts in `scripts/tests/`
  - No formal test runner; run scripts directly
- **Build:**
  - SCSS compiled to CSS via npm scripts (`npm run build`)

## Project Conventions
- **Styling:**
  - Use Bootstrap, FontAwesome, and CSS variables (see `docs/STANDARD_STYLISTYCZNY.md`)
  - Always use a main header card with gradient, section cards with white headers, and appropriate icons
- **Security:**
  - All uploads validated and stored in `assets/uploads/`
  - No sensitive files in public folders
  - Only Composer-managed PHP dependencies in `vendor/`
- **File Structure:**
  - `classes/` – business logic
  - `pages/` – user-facing PHP pages
  - `api/` – AJAX/data endpoints
  - `assets/` – static files (css, js, img, uploads)
  - `scripts/` – seeders, test scripts
  - `docs/` – documentation, style guides

## Integration Points
- **External Libraries:**
  - PHP: `phpoffice/phpspreadsheet`, `psr/*`, etc. (see `composer.json`)
  - JS: `flatpickr`, Bootstrap (see `package.json`)
- **Autoloading:**
  - Custom autoloader for classes in `pages/checkout.php` and similar

## Examples
- To add a new business rule, extend the relevant manager in `classes/`
- To add a new report, create a PHP script in `api/` and use DB structure from `database/`
- To update UI, follow the card/section/variable conventions in `docs/STANDARD_STYLISTYCZNY.md`

---
For more, see `README.md` and `docs/`.
