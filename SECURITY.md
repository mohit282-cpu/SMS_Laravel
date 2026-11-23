# 🛡️ Security Policy & Vulnerability Disclosure

**Product:** SMS Academy — Enterprise School Management System  
**Organization:** Sovryx Tech Pvt. Ltd. / SMS Academy Core Security Team  
**Standard:** OWASP ASVS & ISO/IEC 27001 Aligned  
**Compliance:** Children's Online Privacy Protection & Nepal National Education Information Security Policy  

---

## 📌 1. Supported Versions

Security updates, bug fixes, and vulnerability patches are actively maintained for the following versions:

| Version | Branch | Release Status | Supported | Security Patch Window |
|---|---|---|:---:|---|
| **2.x (Current)** | `main`, `testing` | **Active Production** | :white_check_mark: | Active vulnerability patches & supply chain monitoring |
| **1.x** | `legacy-v1` | **Maintenance** | :warning: | Critical security advisories only (until Dec 2026) |
| **< 1.0** | - | **Deprecated** | :x: | End-of-Life (EOL) — Upgrade required |

---

## 🚨 2. Reporting a Vulnerability

We take the security of student records, financial transactions, and school infrastructure with utmost seriousness. If you believe you have discovered a vulnerability in SMS Academy, we ask you to report it to us **responsibly and privately**.

### ✉️ Primary Security Contact
- **Email:** `security@smsacademy.edu.np`
- **Backup Contact:** `info@smsacademy.edu.np`
- **PGP/GPG Key:** Available upon request for encrypted communications.

### 📋 What to Include in Your Report
To accelerate investigation and triage, please provide:
1. **Description & Impact:** High-level summary of the vulnerability and its potential impact (e.g., unauthorized data disclosure, privilege escalation, ledger manipulation).
2. **Steps to Reproduce:** Exact, reproducible steps or curl commands.
3. **Proof-of-Concept (PoC):** Non-destructive reproduction script, payload, or screenshots.
4. **Affected Components:** File paths, endpoints, HTTP methods, and parameters.
5. **Environment Details:** Software version, PHP version, database engine, or browser used.
6. **Suggested Remediation (Optional):** Patch suggestion or architectural mitigation.

> [!IMPORTANT]
> **Do not submit vulnerability reports through public GitHub issues, discussions, or social media.** All disclosures must remain confidential until a verified patch has been deployed.

---

## ⏱️ 3. Vulnerability Response Timeline & SLA

Our security engineering team adheres to a strict Coordinated Vulnerability Disclosure (CVD) SLA:

| Stage | Target Timeline | Action Description |
|---|---|---|
| **Initial Acknowledgment** | $\le$ **24 hours** | Receipt confirmation and ticket assignment. |
| **Triage & Classification** | $\le$ **48 hours** | Vulnerability reproduction and CVSS v3.1 severity rating. |
| **Patch Development** | $\le$ **7 days** | Core fix developed, peer-reviewed, and verified in CI test suites. |
| **Deployment / Release** | $\le$ **14 days** | Security hotfix tagged and deployed (Critical issues: $\le$ 72 hours). |
| **Public Advisory** | **Post-Deployment** | Coordinated advisory published with credit to the researcher. |

---

## 🎯 4. Scope: In-Scope vs. Out-of-Scope

### ✅ In-Scope Vulnerabilities
- **Authentication & Authorization:**
  - Authentication bypass, session fixation, credential harvesting.
  - Privilege escalation (e.g., Student or Parent escalating to Teacher, Accountant, or Super Admin).
  - Insecure Direct Object References (IDOR) across student marks, fees, or profiles.
- **Data & Business Logic:**
  - SQL Injection, parameter tampering, race conditions in financial ledgers.
  - Double-billing, discount waiver manipulation, invoice tampering.
- **Application & Server-Side Security:**
  - Server-Side Request Forgery (SSRF) in Web Push or external webhook integrations.
  - Remote Code Execution (RCE), unsafe file upload handling, path traversal.
  - Cross-Site Scripting (Stored or Reflected XSS) affecting authenticated roles.
  - Exposure of minor demographic or unredacted personal identifiable information (PII).

### ❌ Out-of-Scope Vulnerabilities
- Volumetric Denial of Service (DoS / DDoS) attacks against web servers or network infrastructure.
- Social engineering, phishing, or physical attacks targeting school administrative staff or students.
- Issues requiring physical access to a logged-in workstation or rooted/jailbroken mobile devices.
- Reports from automated vulnerability scanners without a validated, non-destructive proof-of-concept.
- Missing HTTP security headers on static marketing pages that do not handle authenticated sessions.
- Vulnerabilities in third-party CDN libraries (e.g., FontAwesome, Google Fonts) unless directly exploitable in SMS Academy core.

---

## 🛡️ 5. Core Architectural Security Safeguards

SMS Academy implements defense-in-depth engineering across the application lifecycle:

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│                              DEFENSE-IN-DEPTH CONTROLS                                │
├───────────────────────────────┬───────────────────────────────┬───────────────────────┤
│   🔐 IDENTITY & ACCESS        │   💳 TRANSACTION LEDGER       │   🌐 NETWORK & COMMS  │
│   • Multi-tier 7-role RBAC    │   • Row-level locks (InnoDB)  │   • Anti-SSRF WebPush │
│   • Bcrypt password hashing   │   • Idempotent payment keys   │   • Private Cache-Ctrl│
│   • Scoped student records    │   • Immutable receipt audits  │   • TLS 1.3 / HTTPS   │
│   • Parent-child isolation    │   • No double-spend race cond │   • Sub-resource flags│
└───────────────────────────────┴───────────────────────────────┴───────────────────────┘
```

1. **Anti-SSRF Web Push Validation (`PushEndpointValidator`):**
   Validates all Web Push subscription endpoints against DNS rebinding, internal loopbacks (`127.0.0.1`), private networks (RFC 1918), link-local addresses, and cloud provider metadata services (`169.254.169.254`). Outbound redirects are strictly disabled.
2. **Strict Private Response Caching (`NoStorePrivateResponses`):**
   Enforces `Cache-Control: private, no-store, no-cache, must-revalidate` across all authenticated views, marksheet prints, and financial receipt downloads to prevent proxy/browser disk caching.
3. **Idempotent Financial Ledger:**
   Payment submissions utilize database row-level locks (`SELECT ... FOR UPDATE`) tied to unique idempotency keys, eliminating duplicate payments or concurrent race conditions.
4. **Diagnostic & Health Endpoint Token Protection:**
   Internal diagnostics require `HEALTH_CHECK_SECRET` token authentication or an active `super_admin` session. Public readiness probes return only binary status flags and release commit hashes without leaking stack traces or configuration variables.
5. **Minor Student Privacy Protection:**
   Student addresses, guardian phone numbers, and financial distress indicators are encrypted at rest and restricted solely to authorized administrative personnel.

---

## 🔒 6. Production Hardening Checklist for Institutions

Deploying schools and system administrators must follow these mandatory security practices:

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`.
- [ ] Enforce HTTPS termination with modern TLS (TLS 1.2 / 1.3) and HSTS.
- [ ] Configure `SESSION_SECURE_COOKIE=true` and `SESSION_HTTP_ONLY=true`.
- [ ] Set `SESSION_SAME_SITE=lax`.
- [ ] Ensure `.env` and `storage/` directory permissions are restricted (`chmod 600 .env`, `chmod 755 -R storage bootstrap/cache`).
- [ ] Rotate `APP_KEY` and Web Push VAPID keys periodically via `php artisan key:generate` and `php artisan webpush:vapid`.
- [ ] Maintain offsite, encrypted database backups via `php artisan db:backup` and test restore workflows regularly.
- [ ] Restrict database ports (`3306`) from public internet access using firewall/VPC rules.

---

## 🤝 7. Safe Harbor Policy

SMS Academy strongly supports ethical security researchers who assist in protecting our educational institutions. If you conduct vulnerability research in good faith and adhere to these guidelines:

1. **We will not initiate legal action** against you or request law enforcement investigation.
2. **We will work collaboratively** with you to understand, reproduce, and validate the issue.
3. **We will publicly credit and thank you** in our release notes and Security Hall of Fame (unless you request anonymity).
4. **We ask that you:**
   - Avoid privacy violations, destruction of data, and service interruption.
   - Give us reasonable time to remedy the vulnerability before publicly discussing or publishing findings.
   - Act within the boundaries of ethical security research.

---

*Thank you for helping keep SMS Academy, our schools, teachers, students, and parents safe!*
