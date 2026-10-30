# Frontend Toolchain Modernization Plan

- **Current stack:** Laravel Mix 2.1.14, Webpack 3, node-sass 4.14.1, Vue 2.7, Bootstrap 4
- **Build runtime:** Node 14.21.x / npm 6 (required by node-sass 4 and Webpack 3)
- **Status:** Node 14 and node-sass 4 are end-of-life. The build is reproducible and isolated in CI, but it must be replaced.
- **Owner:** Repository owner
- **Review date:** 2026-12-31 (same date as the npm risk acceptance)

---

## Why the toolchain cannot simply be upgraded

`npm run prod` is executed by `webpack 3` through `laravel-mix 2`. `node-sass 4.14.1` ships prebuilt binaries only for Node <= 14 and will not compile on current Node LTS releases. Bumping `laravel-mix` alone is not possible: Mix 6 requires Webpack 5, which requires regenerating the asset pipeline, and Mix 6 still expects a supported Sass implementation.

The `package.json` also has **zero runtime dependencies**. Every vulnerable package is a build-time `devDependency`; nothing from `node_modules` is served to a browser. That is why CI can accept the residual risk temporarily, and why the baseline in `.github/security/npm-exceptions.json` is explicitly build-only.

---

## Target stack

Recommended end state:

| Layer | Target | Reason |
| --- | --- | --- |
| Bundler | Vite 5+ (or Laravel Mix 6 if a minimal change is preferred) | Supported, fast, native Node LTS compatibility |
| Node | Node 20 or 22 LTS | Supported runtime; available in GitHub Actions |
| Vue | Vue 3 | Vue 2 reached end-of-life on 2023-12-31 |
| Sass | `sass` (Dart Sass) | node-sass is unmaintained |
| CSS | Bootstrap 5 | Bootstrap 4 is out of support |
| HTTP | Axios 1.x | Current maintained line |
| Tests | Vitest or Jest | Optional during phase 1; add once components are migrated |

Vite is preferred over Mix 6 because it removes the Webpack 3/4 legacy chain entirely and has first-class Laravel integration (`laravel-vite-plugin`). Mix 6 is the lower-risk option if the team wants to keep the existing `webpack.mix.js` structure for one release.

---

## Phased migration

### Phase 0 — Freeze and observe (current)

- Keep Node 14 for the build job only.
- Keep npm audit on Node 22 so findings are measured with a modern, stable parser.
- Pin every accepted finding to an exact installed version.
- Do **not** run `npm audit fix` or regenerate the lockfile without a full build and review; that would invalidate the baseline and can break node-sass.

### Phase 1 — Introduce Vite alongside Mix

1. Add `vite`, `laravel-vite-plugin`, `@vitejs/plugin-vue`, and `sass` as devDependencies on a branch.
2. Create `vite.config.js` with inputs for `resources/js/app.js` and `resources/sass/app.scss`.
3. Add a dual-mode Blade include or a feature flag so the application can load either `mix()` or `@vite()`.
4. Build both pipelines in CI temporarily. Compare asset output and page behavior.
5. Do not remove Mix until the Vite bundle passes the browser smoke tests.

### Phase 2 — Migrate Vue 2 to Vue 3

1. Replace `new Vue({ el, ... })` with `createApp(...).mount(...)`.
2. Replace filters with computed properties or global properties.
3. Update `v-model` custom-component semantics and `.sync` usage.
4. Update Bootstrap 4 markup to Bootstrap 5 where components changed (forms, utilities, dropdowns, modals).
5. Run the existing feature tests plus a manual pass over dashboards, tables, modals, and the PWA shell.

### Phase 3 — Remove Mix and Node 14

1. Delete `webpack.mix.js`, `mix-manifest.json`, and Mix-only packages.
2. Remove `laravel-mix`, `webpack`, `node-sass`, `vue-template-compiler`, and `babel-loader` from `package.json`.
3. Regenerate `package-lock.json` on Node 20/22.
4. Change the CI `frontend-build` job to Node 20 or 22 with `npm ci && npm run build`.
5. Remove the Node 22 audit job's special-case rationale, or collapse it into the build job.
6. Delete `.github/security/npm-exceptions.json` once the new lockfile audits clean, or reduce it to genuinely unavoidable findings.

---

## CI changes that must accompany the migration

- The build job currently pins `node-version: '14'` with a comment explaining why. That comment is the trigger for this migration.
- The audit job runs separately on Node 22. After migration, both jobs may use the same Node version.
- The npm gate compares exact version-pinned findings. Regenerating the lockfile will fail the gate by design; update the baseline only after reviewing the diff and rebuilding.
- Keep `public/js/app.js` and `public/css/app.css` assertions until the Vite output paths are finalized.

---

## Exit criteria

The migration is complete when all of the following are true:

1. `npm ci` and the production build pass on Node 20 or 22.
2. No `node-sass`, `laravel-mix`, or `webpack 3` dependency remains.
3. `npm audit --json` reports **zero high/critical** findings, or every remaining finding has a current, version-pinned, time-bound exception with a compensating control.
4. `.github/security/npm-exceptions.json` is either deleted or contains only reviewed, non-stale entries.
5. The workflow no longer pins Node 14, and the `frontend-modernization.md` review date is closed.
6. The application smoke tests pass for dashboards, forms, modals, printing, and the PWA shell.

---

## Risk accepted until then

See `.github/security/npm-exceptions.json` for the full rationale and mitigations. Summary:

- All findings are build-time only.
- CI installs strictly from the committed lockfile on an ephemeral runner.
- The development server is never exposed by CI or production.
- Any dependency or advisory change fails the gate and forces a review.
- The acceptance expires on 2026-12-31.
