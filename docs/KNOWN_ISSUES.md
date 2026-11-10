# SMS Academy — Known Issues & Technical Debt Registry

**Product:** SMS Academy — School Management System (Nepal)  
**Organization:** Sovryx Tech Pvt. Ltd.  
**Branch:** `testing`  
**Date:** November 2026  

---

## 1. Overview

This document records architectural technical debt, environmental requirements, and low-priority non-blocking warnings that have been investigated and accepted with documented compensating controls. None of these items impede pilot deployment or production operations.

---

## 2. Technical Debt & Environmental Constraints

### 2.1 Legacy Frontend Build Toolchain (Webpack 3 / Laravel Mix 2 / Vue 2.5)
- **Description:** The frontend build pipeline uses Webpack 3 and Laravel Mix 2 with Node Sass, which requires Node.js 14 to compile from source.
- **Impact:** Compiling assets requires Node.js 14 runner in CI/CD. Modern Node.js versions (v18+) will fail during `node-sass` native binding builds if compiling from source.
- **Compensating Control:**
  - CI/CD workflow `.github/workflows/ci.yml` and `.github/workflows/cd.yml` use `actions/setup-node@v4` with `node-version: '14'` specifically for the asset compilation step.
  - Production deployments distribute precompiled CSS/JS bundles (`public/js/app.js` and `public/css/app.css`); Node.js is NOT installed or required on the production web server.
  - An exact fingerprint security baseline is maintained in `.github/security/npm-exceptions.json` verifying zero runtime exposure.
- **Long-term Roadmap:** Modernize frontend to Vite + Vue 3 / Inertia.js in Q2 2027 after initial pilot phase.

### 2.2 PHPUnit 9 Deprecated XML Schema Warning
- **Description:** Running `vendor/bin/phpunit` outputs:  
  `Warning: Your XML configuration validates against a deprecated schema.`  
  `Suggestion: Migrate your XML configuration using "--migrate-configuration"!`
- **Impact:** Informational warning only; does not affect test execution, assertion validity, or test results.
- **Compensating Control:** Retained standard PHPUnit 9 configuration for backward compatibility across local developer machines running PHP 8.1 and PHP 8.2.

### 2.3 Database Version Requirements
- **Description:** Point-in-time consistent backups and window functions require MySQL 8.0+ or MariaDB 10.5+.
- **Impact:** Running on legacy MySQL 5.7 or MariaDB 10.2 may fail during consistent snapshot transaction initiation or complex analytical queries.
- **Compensating Control:** System documentation and deployment prerequisites mandate MySQL 8.0+ or MariaDB 10.5+.

### 2.4 Browser Caching on Public Terminals
- **Description:** In some rural school administration offices, shared computers may retain page HTML in browser back/forward cache if users do not explicitly log out.
- **Impact:** Risk of unauthorized viewing of student records on shared administrative terminals.
- **Compensating Control:** Implemented `no_store` middleware applied to all authenticated administrative routes, sending `Cache-Control: no-cache, no-store, must-revalidate` and `Pragma: no-cache`.

