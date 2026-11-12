# SMS Academy — Unified SaaS Design System

**Product:** SMS Academy (`Smart School Management. Simplified.`)  
**Organization:** Sovryx Tech Pvt. Ltd.  
**Stylesheet:** [`public/assets/css/sms-design-system.css`](file:///z:/Xampp/htdocs/SMS_Laravel/public/assets/css/sms-design-system.css)  
**Branch:** `testing`  
**Date:** 2026-11-12  

---

## 1. Color Tokens

| Token | Value | Purpose |
|---|---|---|
| `--sms-primary` | `#FF5700` | Primary brand accent, primary CTAs, active navigation |
| `--sms-primary-hover` | `#E04800` | Primary hover state |
| `--sms-primary-active` | `#C23E00` | Primary active/pressed state |
| `--sms-navy` | `#111827` | Top navbar, sidebar background, primary dark headings |
| `--sms-background` | `#F8FAFC` | Application canvas background |
| `--sms-surface` | `#FFFFFF` | Cards, modals, tables, dropdowns |
| `--sms-surface-subtle` | `#F1F5F9` | Table headers, session pills, subtle containers |
| `--sms-text-primary` | `#111827` | Primary body and heading text |
| `--sms-text-secondary` | `#64748B` | Secondary labels, metadata, breadcrumbs |
| `--sms-border` | `#E2E8F0` | Standard 1px card and table borders |
| `--sms-success` | `#16A34A` | Success badges, active session dot |
| `--sms-warning` | `#D97706` | Pending/warning states |
| `--sms-danger` | `#DC2626` | Errors, destructive actions, required marks |
| `--sms-info` | `#2563EB` | Informational highlights |

---

## 2. Typography & Spacing

- **Font Stack:** `'Roboto', 'Noto Sans Devanagari', -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif` (supports English + Nepali Devanagari script).
- **Base Font Size / Line Height:** `0.875rem` / `1.55`.
- **Spacing Scale:** `--sms-space-1` (`0.25rem`) through `--sms-space-8` (`2rem`).
- **Radii:** `--sms-radius-sm` (`8px`), `--sms-radius-md` (`10px`), `--sms-radius-lg` (`12px`), `--sms-radius-pill` (`9999px`).
- **Elevation:** `--sms-shadow-sm`, `--sms-shadow-md`, `--sms-shadow-lg` (subtle slate shadows; zero heavy blur/glassmorphism).

---

## 3. Reusable Blade Components (`resources/views/components/`)

### `<x-stat-card>`
```blade
<x-stat-card
    :label="__('Total Classes')"
    :value="Qs::formatNumber($count)"
    icon="icon-windows2"
    variant="orange"
    :href="route('classes.index')"
/>
```
Supports `variant`: `orange`, `navy`, `blue`, `indigo`, `emerald`, `amber`, `rose`.

### `<x-card>`
```blade
<x-card :title="__('Student List')" icon="icon-users">
    <x-slot name="actions">
        <a href="{{ route('students.create') }}" class="btn btn-primary btn-sm">{{ __('Admit Student') }}</a>
    </x-slot>
    ...
</x-card>
```

### `<x-empty-state>`
```blade
<x-empty-state
    :title="__('No records found')"
    :message="__('No students match the selected class.')"
    icon="icon-users"
/>
```

### `<x-confirm-modal>`
```blade
<x-confirm-modal
    id="delete-student-modal"
    :title="__('Delete Student')"
    :message="__('This action cannot be undone.')"
/>
```

---

## 4. Accessibility & Print Rules

- Keyboard focus rings: `:focus-visible { outline: 2px solid var(--sms-primary); outline-offset: 2px; }`.
- Reduced motion: `@media (prefers-reduced-motion: reduce)` disables transitions/animations.
- Print: `@media print` automatically hides `.navbar`, `.sidebar`, `.sms-mobile-dock`, `.sms-footer`, and `.no-print`.

