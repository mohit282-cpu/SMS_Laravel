# Super Administrator User Guide

**Target Role**: School Principal, Head of IT, Managing Director  
**Access Level**: Tier 1 Super Admin (`user_type = 'super_admin'`)  
**Portal URL**: `https://school.yourdomain.edu.np/login`  

---

## 1. Initial Login & Password Policy

1. Navigate to `/login`.
2. Enter your authorized username or email address and the secret password provided during system initialization.
3. Upon first login, navigate immediately to **My Profile** (`/my_account`) to:
   - Verify contact phone number and recovery email.
   - Update your password to a strong phrase containing uppercase, lowercase, numbers, and symbols.
   - Set preferred calendar display mode: **Bikram Sambat (BS)**, **Anno Domini (AD)**, or **Both**.

---

## 2. Super Admin Functional Scope

As Super Admin, you hold comprehensive institutional authority:

### Institutional Management
- **School Configuration (`/super_admin/settings`)**:
  - Update School Name, Acronym, Official Phone, Email, Physical Address.
  - Set Term Start and End Dates.
  - Upload Official School Crest / Logo (auto-optimized and watermarked).
  - Configure default tuition fee structures per educational level.
- **Academic Session & Years (`/super_admin/settings`)**:
  - Activate the current academic year (e.g. 2083/84 BS).
  - Lock/Unlock examination marks entry during evaluation windows.

### User Account Administration (`/users`)
- Create and manage administrative accounts:
  - **Admins (`admin`)**: Vice Principals, Section Heads.
  - **Accountants (`accountant`)**: Finance & Bursary officers.
  - **Librarians (`librarian`)**: Catalog and borrowing officers.
  - **Teachers (`teacher`)**: Subject and class teachers.
  - **Parents (`parent`)**: Guardians linked to student records.
- Reset user passwords securely without exposing plain text.
- Activate or deactivate staff accounts upon onboarding or offboarding.

### Audit & Security Surveillance (`/super_admin/audit_logs`)
- Real-time audit trail of all administrative updates:
  - Profile modifications.
  - Fee collection adjustments.
  - Mark amendments with before/after state snapshots.
  - Failed login attempts and security alerts.

---

## 3. First-Run Setup Order of Operations

To prepare the school for operational use, follow this sequential order:
1. **System Settings**: Confirm institutional identity, phone, address, and logo.
2. **Academic Year**: Confirm current active session is set to `2083/84 BS`.
3. **Classes & Sections**: Create educational levels (e.g., Nursery to Grade 10) and assign sections (e.g., Section A, Section B).
4. **Subjects**: Define compulsory and elective subjects mapped to classes.
5. **Teachers**: Register teaching staff and assign class/subject teaching duties.
6. **Students & Parents**: Enroll students, assign admission numbers, and link guardian profiles.
7. **Fee Structure**: Establish tuition fee heads before generating term invoices.

