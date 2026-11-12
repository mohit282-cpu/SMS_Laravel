# Testing & Verification Guide

## 1. Local Test Commands
```bash
# Composer manifest & PHP syntax lint
composer validate --strict

# Full PHPUnit suite (uses disposable mysql_test database in phpunit.xml)
vendor/bin/phpunit

# Targeted CI/CD & security test suites
vendor/bin/phpunit tests/Unit/MigrationSafetyScannerTest.php
vendor/bin/phpunit tests/Unit/ReleaseArchiveSecurityTest.php
vendor/bin/phpunit tests/Unit/VerifyCiGateTest.php
vendor/bin/phpunit tests/Feature/HealthCheckTest.php
vendor/bin/phpunit tests/Feature/NepalLocationHierarchyTest.php
vendor/bin/phpunit tests/Feature/UiShellAndNavigationTest.php
```

## 2. Security & Migration Scanners
```bash
php .github/scripts/audit-checker.php
node .github/scripts/npm-audit-checker.js
php .github/scripts/check-migration-safety.php
```

## 3. Disposable Database Verification
```bash
php .github/scripts/verify-ci-database.php --connection=mysql_test
php artisan migrate:fresh --force --database=mysql_test
php artisan migrate:rollback --force --database=mysql_test
php artisan migrate --force --database=mysql_test
php artisan db:seed --force --database=mysql_test
```

