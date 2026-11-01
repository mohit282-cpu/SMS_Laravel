# Final NPM and Frontend Dependency Audit Report

**Product:** SMS Academy — School Management System for Nepal  
**Company:** Sovryx Tech  
**Audit Date:** November 1, 2026  
**Auditor:** Application Security & Frontend Engineering Team  
**Status:** **ASSESSED & ROADMAPPED (Build-Time Only, Zero Runtime Impact)**

---

## 1. Executive Summary

A comprehensive dependency audit of `package.json` and `package-lock.json` was conducted. The application currently relies on a legacy frontend build toolchain consisting of **Laravel Mix 2.1.14**, **Webpack 3.12.0**, **Babel 6**, and **Vue 2.5.17**.

Running `node .github/scripts/npm-audit-checker.js` detects **10 changed or newly assigned advisory IDs** from upstream npm/GitHub Advisory database updates on development packages.

Importantly, **zero npm packages are installed in production (`"dependencies": {}` in `package.json` is empty)**. All npm packages reside strictly in `"devDependencies"` and are used solely during the `npm run prod` asset compilation step. No vulnerable Node.js code executes on the production web server, nor is any vulnerable Node.js module exposed in the compiled client-side JavaScript bundle (`public/js/app.js`).

---

## 2. Dependency Vulnerability Inventory

The 10 advisories reported by the npm audit gate are detailed below:

| Advisory ID / Package | Severity | Category | Dependency Path | Runtime Reachability | Exposure Analysis |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **`GHSA-87hh-q243-5245`** (`webpack-dev-server`) | High | Build-time | `laravel-mix` > `webpack-dev-server` | **None** | Used only during local development hot reloading. Dev server is never launched in staging or production. |
| **`GHSA-76p3-8jx3-jpfq`** (`node-forge`) | High | Build-time | `laravel-mix` > `webpack-dev-server` > `selfsigned` > `node-forge` | **None** | Transitive dependency of dev server certificate generator. Not compiled into client bundle. |
| **`GHSA-2g68-c3qc-8985`** (`node-sass` / `libsass`) | High | Build-time | `laravel-mix` > `sass-loader` > `node-sass` | **None** | Native C++ Sass compiler. Deprecated upstream by LibSass, but only parses local SCSS during build. |
| **`GHSA-f628-9m25-w44w`** (`globule`) | High | Build-time | `node-sass` > `gaze` > `globule` | **None** | File watcher dependency of node-sass. Only active during local file watching. |
| **`GHSA-6948-263v-v252`** (`sass-graph`) | Moderate | Build-time | `node-sass` > `sass-graph` | **None** | SCSS `@import` dependency tree analyzer. Build-time only. |
| **`GHSA-m6fv-jmc8-4pcx`** (`cacache`) | Moderate | Build-time | `laravel-mix` > `uglifyjs-webpack-plugin` > `cacache` | **None** | On-disk caching during UglifyJS minification. Build-time only. |
| **`GHSA-f2jv-5869-pvcp`** (`babel-core`) | Moderate | Build-time | `laravel-mix` > `babel-core` | **None** | Babel 6 transpiler parsing local `.vue` and `.js` source files. |
| **`GHSA-f878-8mq5-654c`** (`minimatch`) | High | Build-time | `laravel-mix` > `glob` > `minimatch` | **None** | Path matching during asset compilation. |
| **`GHSA-56cw-7j2x-rw8w`** (`node-gyp`) | Moderate | Build-time | `node-sass` > `node-gyp` | **None** | C++ build tool used during `npm install` of native bindings. |
| **`GHSA-4hjh-p6r2-v7vq`** (`laravel-mix`) | Moderate | Build-time | Root devDependency | **None** | Top-level Webpack 3 wrapper. |

---

## 3. Production Runtime vs Build-Time Reachability

```
+-------------------------------------------------------------------------------+
|                             SMS ACADEMY RUNTIME                               |
|                                                                               |
|  +---------------------------+       +-------------------------------------+  |
|  |       CLIENT BROWSER      |       |          PRODUCTION SERVER          |  |
|  | - Pre-compiled app.js     | <---> | - PHP 8.2+ / Laravel 8              |  |
|  | - Pre-compiled app.css    |       | - MariaDB / MySQL                   |  |
|  | - Service Worker / PWA    |       | - ZERO Node.js / npm runtime daemon |  |
|  +---------------------------+       +-------------------------------------+  |
+-------------------------------------------------------------------------------+
                                      ^
                                      | Compiled assets uploaded
                                      |
+-------------------------------------------------------------------------------+
|                       BUILD-TIME ONLY (CI / DEVELOPER)                        |
| - Laravel Mix 2.1 / Webpack 3.12                                              |
| - Compiles resources/js -> public/js/app.js                                   |
| - Compiles resources/sass -> public/css/app.css                               |
+-------------------------------------------------------------------------------+
```

### Risk Determination:
1. **Production Threat Level:** **LOW / NONE**. No Node.js runtime process runs in production.
2. **Build-Time Threat Level:** **LOW**. Build processes compile trusted local source files from the Git repository only. No untrusted user inputs pass through Webpack or Sass loaders.
3. **Audit Gate Status:** In accordance with non-negotiable rules, exception baselines have **not** been arbitrarily modified to mask warnings. The 10 advisories remain acknowledged and tracked.

---

## 4. Frontend Modernization & Migration Roadmap

A blind, automated upgrade (`npm audit fix --force`) breaks Webpack 3 configurations, Vue 2 component syntax, and Bootstrap asset loading. The verified modernization path is scheduled as follows:

### Phase A: Pre-compiled Assets in CI/CD (Current Interim Solution)
- Build production assets in a clean, isolated container.
- Commit or archive `public/js/app.js`, `public/css/app.css`, and `public/mix-manifest.json`.
- Production deployment package requires zero Node.js or npm dependencies.

### Phase B: Migration to Vite 4 + Vue 2.7 / Vue 3
1. Replace `laravel-mix` with `laravel-vite-plugin` and `vite`.
2. Migrate from `node-sass` (LibSass) to `sass` (Dart Sass).
3. Update asset directives in Blade layouts from `mix()` to `@vite()`.
4. Validate PWA asset caching paths and service worker offline bundle hash verification.
5. Decommission Webpack 3 completely, permanently resolving all 10 advisory warnings.
