# Nepal Administrative Location System

## 1. Hierarchy & Dataset Overview
- **Hierarchy:** `Nepal (नेपाल)` → `Province (प्रदेश)` → `District (जिल्ला)` → `Local Government (स्थानीय तह)` → `Ward No. (वडा नं.)` → `Tole / Street`
- **Verified Dataset (`database/data/nepal_locations.json`):**
  - **7** Provinces (`nepal_provinces`)
  - **77** Districts (`nepal_districts`)
  - **753** Local Governments (`nepal_local_levels`):
    - **6** Metropolitan Cities (`metropolitan` / महानगरपालिका)
    - **11** Sub-Metropolitan Cities (`sub_metropolitan` / उपमहानगरपालिका)
    - **276** Municipalities (`municipality` / नगरपालिका)
    - **460** Rural Municipalities (`rural_municipality` / गाउँपालिका)
- **Source:** Government of Nepal / MoFAGA administrative structure.

## 2. Database Schema
- `nepal_provinces`: `id`, `country_code`, `name`, `name_ne`, `code` (unique), `is_active`, `effective_date`, `data_source`, timestamps
- `nepal_districts`: `id`, `province_id` (FK → `nepal_provinces.id`), `name`, `name_ne`, `code`, `is_active`, `effective_date`, `data_source`, timestamps
- `nepal_local_levels`: `id`, `district_id` (FK → `nepal_districts.id`), `name`, `name_ne`, `code`, `level_type`, `total_wards`, `is_active`, `effective_date`, `data_source`, timestamps
- `nepal_wards`: `id`, `local_level_id` (FK → `nepal_local_levels.id`), `ward_number`, `name`, `name_ne`, `is_active`, timestamps
- `student_records` & `users`: nullable `province_id`, `district_id`, `local_level_id`, `ward_no`, `tole`, `street` columns (preserving legacy `state_id`/`lga_id` compatibility for historical records).

## 3. Migrations & Seeding
```bash
php artisan migrate
php artisan db:seed --class=NepalGeographySeeder
# Or strict CLI import:
php artisan nepal:import-locations database/data/nepal_locations.json --strict-official-counts
```

## 4. API & Web Endpoints
### Authenticated Web Cascading Endpoints (`routes/web.php`)
- `GET /nepal-locations/provinces?q=`
- `GET /nepal-locations/districts?province_id=&q=`
- `GET /nepal-locations/local-levels?district_id=&q=`
- `GET /nepal-locations/wards?local_level_id=`

### Public Cached REST API Endpoints (`routes/api.php`, `throttle:120,1`)
- `GET /api/provinces`
- `GET /api/provinces/{province}/districts`
- `GET /api/districts/{district}/local-governments`
- `GET /api/local-governments/{id}`

## 5. UI Component & Module Integration
- **Reusable Blade Component:** `<x-nepal-address-selector :provinces="$provinces" :provinceId="..." :districtId="..." :localLevelId="..." :wardNo="..." :tole="..." :street="..." :required="true" />`
- **Integrated Modules:** Student Admission (`students/add`), Student Edit/Profile (`students/edit`, `students/show`), User Management (`users/index`, `users/edit`, `users/show`), and Admin Location Directory (`/nepal-locations/manage`).
- **Cascading JS:** Handled globally in `resources/views/partials/js/custom_js.blade.php` via `.sms-nepal-address-group` with loading states and Select2 synchronization.

## 6. Admin Management & Rollback
- **Admin UI:** `/nepal-locations/manage` (`teamSA` view access; `super_admin` status toggle/edit with `AuditLogService` logging and `Cache::flush()`).
- **Rollback:** `php artisan migrate:rollback --step=2` removes the `users` and `student_records` Nepal location columns without touching existing user/student records.

