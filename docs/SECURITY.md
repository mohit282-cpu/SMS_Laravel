# Security Policy & Controls

**Project:** SMS Laravel — School Management System  
**Branch:** `testing`  
**Security Baseline:** OWASP ASVS aligned, Zero-Trust CI/CD supply chain

---

## 1. Authentication, Authorization & Session Security
- **Role-Based Access Control (RBAC):** Strict multi-tenant and role separation (`super_admin`, `admin`, `teacher`, `student`, `parent`, `accountant`, `librarian`) enforced via route middleware (`teamSA`, `teamSAT`, `super_admin`) and FormRequest authorization methods.
- **IDOR Protection:** Cross-student and cross-parent access is strictly blocked at the controller/model layer; users cannot view another student's marks, attendance, or financial records by altering query parameters or record IDs.
- **Diagnostic Endpoint Protection:** `/health/diagnostic` requires either authenticated `super_admin` session or `X-Health-Key` matching `HEALTH_CHECK_SECRET`.
- **Public Health Probe:** `/health/readiness` returns only `status`, `timestamp`, and 40-char verified `release` SHA with HTTP `Cache-Control: no-cache, no-store, must-revalidate` and zero stack traces or internal environment variables.

---

## 2. Supply-Chain & Dependency Security Gates
- **Composer Audit Gate:** `.github/scripts/audit-checker.php` runs `composer audit --locked --format=json` and fails closed on any advisory not covered by an unexpired, owner-assigned exception in `.github/security/composer-exceptions.json` (currently 13 Laravel 8 / `dompdf` / `flysystem` advisories including CVE-2026-128083, all with verified compensating controls).
- **npm Build-Toolchain Gate:** `.github/scripts/npm-audit-checker.js` verifies that all 79 build-time findings match `.github/security/npm-exceptions.json` exactly; confirms `0` runtime npm dependencies on production servers.
- **Migration Safety Gate:** `.github/scripts/check-migration-safety.php` runs in strict mode by default in CI, detecting Schema destructive operations, table/column renames, and raw destructive SQL. Approvals require structured entries in `.github/security/migration-exceptions.json` or fully-qualified `@approved-destructive: {...}` docblocks with justification, preservation strategy, owner, and unexpired date.

---

## 3. Release Archive & Extraction Hardening
- **Pre-Extraction Binary Validator:** `.github/scripts/verify-release-archive.php` validates archive streams directly in memory before filesystem extraction.
- **Cryptographic & Structural Validation:**
  - Verifies gzip magic bytes (`0x1F, 0x8B`).
  - Verifies gzip footer CRC32 and uncompressed ISIZE against decompressor stream hash.
  - Validates every 512-byte TAR header checksum (signed and unsigned sum comparison).
  - Strictly validates octal numeric fields (`mode`, `uid`, `gid`, `size`, `mtime`, `chksum`).
  - Requires exactly two consecutive 512-byte zero blocks for end-of-archive termination; rejects missing second zero block or corrupted end sequences.
  - Rejects unexpected non-zero trailing data after end-of-archive.
  - Enforces exact record lengths on PAX extended headers; rejects dangerous keys (`linkpath`, `schily.*`).
  - Validates GNU LongName records.
  - Rejects absolute paths, Windows drive paths (`C:`), path traversal (`..`), and control characters.
  - Rejects symlinks, hardlinks, device nodes, FIFOs, and duplicate normalized entries.
  - Enforces resource limits: 150 MB archive, 50,000 entries, 25 MB single file, 500 MB total uncompressed, 100:1 compression ratio.

---

## 4. CD Deployment & CI Gate Integrity
- **Exact CI Gate Verifier:** `.github/scripts/verify-ci-gate.php` establishes a deterministic link: `repository` -> `workflow (ci.yml)` -> `workflow run ID` -> `head_sha` -> `check_suite_id` -> `CI Integration Gate`.
- **Fail-Closed API Handling:** Fails closed on HTTP 401, 403, 404, 429, missing auth token, malformed JSON, or ambiguous duplicate check runs.
- **Deterministic Rerun Policy:** When multiple workflow runs exist for the exact commit SHA, the latest run (highest run number/ID) must be `completed` and `success`. A failed or pending rerun immediately invalidates the gate.
- **Deployment Script Safety:** `.github/workflows/cd.yml` eliminates unsafe error suppression (`|| true`), employs an active `reload_php_fpm` detector, parses readiness responses via strict PHP JSON decoding, and executes an automated rollback trap without destructive `migrate:rollback`.
