# Security and Privacy Operations Guide

**Application**: SMS Laravel (School Management System)  
**Security Standard**: ISO/IEC 27001 & OWASP Top 10 Aligned  
**Compliance**: Children's Online Privacy & Nepal National Education Information Policy  

---

## 1. Production Security Architecture

### Cryptographic Protection
- **Passwords**: One-way hashed using standard bcrypt with configurable cost factor (`BCRYPT_ROUNDS=10` or higher in production). Plaintext passwords are never logged, stored, or transmitted.
- **Session Tokens**: Cryptographically random 40-character tokens rotated on authentication and privilege elevation.
- **CSRF Tokens**: Form requests and AJAX operations strictly require valid CSRF tokens via `_token` parameter or `X-CSRF-TOKEN` headers.

### Transport Security & Cookie Flags
- **HTTPS Enforcement**: All HTTP traffic is redirected to HTTPS at the web server and reverse proxy layers.
- **Secure Cookie Flags**:
  - `Secure`: Cookie only transmitted over HTTPS connections.
  - `HttpOnly`: JavaScript cannot read session cookies (mitigating XSS session hijacking).
  - `SameSite`: Configured to `Lax` to prevent Cross-Site Request Forgery.

### Anti-Brute-Force & Rate Limiting
- **Login Throttling**: 5 failed login attempts per identity/IP triggers a temporary lockout with exponential backoff.
- **Admissions / Public Search Rate Limiting**: Maximum 10 queries per minute per IP to prevent automated scraping of student results.
- **Push Notification Subscriptions**: Throttled to 30 registrations per minute.

---

## 2. Student Privacy & Data Protection

1. **Minor Demographic Protection**: Student residential addresses, contact numbers, and parent financial ledgers are restricted to authorized institutional staff.
2. **Access Isolation**: Students and parents have access solely to their own academic grades, attendance percentages, and fee invoices.
3. **Audit Trail**: Every modification to student marks, attendance records, or financial waivers is permanently stamped with:
   - Modifying User ID
   - Timestamp
   - IP address
   - Prior state vs new state values.

---

## 3. Vulnerability Reporting & Patch Management

- Report potential security vulnerabilities directly to the institutional IT security desk:
  - **Email**: `security@apexschool.edu.np`
- Security patches must be applied from authorized Git release tags only.
- Never test penetrations or security tools directly against the live production database.

