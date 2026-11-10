# npm CI Security Remediation & Build Toolchain Risk Assessment

**Product:** SMS Academy (School Management System — Nepal)  
**Organization:** Sovryx Tech Pvt. Ltd.  
**Policy File:** `.github/security/npm-exceptions.json`  
**Gate Script:** `.github/scripts/npm-audit-checker.js`  
**Date:** 2026-11-10  
**Status:** AUDITED, FINGERPRINTED & GATED  

---

## 1. Executive Summary

This document details the security posture and vulnerability remediation policy for the frontend asset build toolchain of SMS Academy.

A modern `npm audit --json` scan against `package-lock.json` reveals:
- **Total High/Critical Findings:** 79 (Critical: 33, High: 46)
- **Runtime npm Dependencies:** **0** (Zero runtime dependencies)
- **Vulnerable Packages:** All 79 vulnerabilities reside exclusively in `devDependencies` (specifically legacy Laravel Mix 2.1.14, Webpack 3.12.0, Webpack Dev Server 2.11.5, node-sass 4.14.1, babel-core 6.26.3, and node-forge 0.10.0).

Because zero npm packages are shipped to browsers or deployed to production servers (only compiled static CSS/JS bundles in `public/` are released), the residual runtime risk to end users is zero. However, to prevent supply chain contamination and development-time exploits, the CI build is isolated and protected by a version-pinned, review-dated security gate.

---

## 2. Toolchain Inventory & Architectural Constraint

The repository frontend was authored on a legacy Vue 2.7 / Laravel Mix 2 stack:
- **Build Engine:** Webpack 3.12.0
- **Compiler Wrapper:** `laravel-mix@2.1.14`
- **Sass Compiler:** `node-sass@4.14.1` (requires Node 14 C++ native bindings)
- **UI Framework:** Bootstrap 4, jQuery, Vue 2.7

Because `node-sass 4.14` has no pre-compiled binaries for Node versions newer than 14, upgrading Node in the current build pipeline without modernizing the build system (to Vite or modern Mix + Dart Sass) causes native compilation errors.

### Two-Tier CI Strategy:
1. **Compilation Tier (Node 14):** In `.github/workflows/ci.yml`, the `frontend-build` job runs on Node 14 using `npm ci` strictly from the committed `package-lock.json`. It compiles static assets (`public/js/app.js`, `public/css/app.css`) and uploads them as verified artifacts.
2. **Security Audit Tier (Node 22):** The `npm-audit` job runs separately on Node 22 to execute modern `npm audit --json`. It validates every finding against the cryptographic fingerprint in `.github/security/npm-exceptions.json`.

---

## 3. High & Critical Vulnerability Triage & Compensating Controls

### 3.1 Major Vulnerability Clusters

| Package | Installed Version | Severity | Major Advisories | Technical Justification & Compensating Controls |
|---|---|---|---|---|
| `webpack-dev-server` | 2.11.5 | Critical | GHSA-m592-p7pm-4h6m, GHSA-cw24-3cmg-rh85 | Dev server allows remote code execution / HTTP header injection. **Compensating Control:** Webpack dev server is never run in CI or production. Only `npm run prod` (static Webpack compilation) is executed. |
| `node-forge` | 0.10.0 | High | GHSA-2328-F5F3-GJ25, GHSA-2R2C-G63R-VCCR, GHSA-554W-WPV2-VW27, etc. | Cryptographic issues in JavaScript TLS/SSL implementation. **Compensating Control:** Transitive dependency of webpack-dev-server. Not used in static asset compilation; production TLS is handled by Nginx / OpenSSL. |
| `node-sass` | 4.14.1 | Critical / High | Deprecated libsass binaries | Native C++ memory vulnerabilities. **Compensating Control:** Executes solely in ephemeral GitHub Actions VM for 45 seconds to generate CSS, then VM is destroyed. No untrusted input is processed. |
| `babel-core` | 6.26.3 | High | ReDoS, prototype pollution | Legacy transpile pipeline. **Compensating Control:** Only compiles trusted application source files in `resources/assets/`. |
| `serialize-javascript` | 1.9.1 | High | Remote code execution via regex | Webpack bundle serializer. **Compensating Control:** Only serializes known application module maps. |
| `ssri` | 5.3.0 | High | Subresource integrity bypass | Transitive build dependency. **Compensating Control:** Hashes verified locally in container. |

---

## 4. Policy File Specifications (`.github/security/npm-exceptions.json`)

The exception file enforces:
- **Version Pinning:** Every entry requires `package`, `version`, and exact `advisories` array.
- **Expiry Date:** Review is mandated before `2026-12-31`.
- **Zero Growth Guarantee:** If any dependency changes, or if npm reports a new or removed advisory, the gate fails immediately.
- **Automated Validation:**
  ```bash
  node .github/scripts/npm-audit-checker.js
  ```
  Result: `npm security gate passed: 79 reviewed high/critical build-toolchain finding(s) matched exactly; 0 runtime dependencies.`

---

## 5. Long-Term Modernization Roadmap

The permanent remediation for all 79 vulnerabilities is the frontend toolchain modernization documented in [docs/frontend-modernization.md](file:///z:/Xampp/htdocs/SMS_Laravel/docs/frontend-modernization.md):
1. **Branch:** Dedicated `feat/frontend-modernization` branch.
2. **Engine:** Migrate from Laravel Mix 2 + Webpack 3 to **Vite 5** + `@vitejs/plugin-vue2` (or Vue 3).
3. **Sass:** Replace `node-sass` with **Dart Sass** (`sass`).
4. **Runtime:** Upgrade CI frontend build to **Node 20 or Node 22 LTS**.
5. **Outcome:** 100% elimination of all 79 legacy vulnerabilities, zero exceptions required.

