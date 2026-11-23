# User Roles, Permissions and Access Control Matrix

**System**: SMS Laravel (School Management System)  
**Security Model**: Role-Based Access Control (RBAC) with Middleware Guards  

---

## 1. Role Definitions & Hierarchy

```
[ Tier 1 ] Super Admin (Principal, IT Director, System Owner)
   │
[ Tier 2 ] School Admin (Vice Principal, Academic Coordinator)
   │
[ Tier 3 ] Teachers & Instructors
   │
[ Tier 4 ] Accountants & Bursary Staff
   │
[ Tier 5 ] Librarians
   │
[ Tier 6 ] Parents & Legal Guardians
   │
[ Tier 7 ] Students
```

---

## 2. Granular Permissions Matrix

| Functional Module | Super Admin | Admin | Teacher | Accountant | Librarian | Parent | Student |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **System Settings** | **Full** | Read | None | None | None | None | None |
| **Audit Logs** | **Full** | None | None | None | None | None | None |
| **Staff Accounts** | **Full** | Manage | None | None | None | None | None |
| **Classes & Sections** | **Full** | Manage | View | None | None | None | None |
| **Subjects** | **Full** | Manage | Assigned | None | None | None | None |
| **Student Profiles** | **Full** | Manage | Class | Read | Read | Child Only | Own Only |
| **Parent Profiles** | **Full** | Manage | View | View | None | Own Only | None |
| **Attendance Roll Call** | **Full** | Manage | Assigned Class | None | None | Child View | Own View |
| **Exam Schedules** | **Full** | Manage | View | None | None | View | View |
| **Marks Entry** | **Full** | Manage | Assigned Subjects | None | None | None | None |
| **Report Cards** | **Full** | Print | Print Class | None | None | Child View | Own View |
| **Fee Structure** | **Full** | Manage | None | **Full** | None | Child View | Own View |
| **Payment Collection** | **Full** | Manage | None | **Full** | None | None | None |
| **Receipt Printing** | **Full** | Manage | None | **Full** | None | Child Print | Own Print |
| **Library Catalog** | **Full** | Manage | View | None | **Full** | View | View |
| **Book Borrowing** | **Full** | Manage | None | None | **Full** | Child View | Own Request |

---

## 3. Strict Boundary Isolation Rules

### 1. Parent-Child Privacy Isolation
- Parents can **only** inspect records, report cards, fees, and attendance belonging to their explicitly linked biological/legal children (`student_records.my_parent_id = parent_user_id`).
- Direct URL tampering (e.g. attempting to view `/students/{other_id}`) is rejected with an HTTP 403 Forbidden exception.

### 2. Teacher Assignment Restrictions
- Subject teachers can **only** submit or modify marks for subjects and classes they are officially scheduled to teach.
- Class teachers can take attendance **only** for their designated section.

### 3. Finance & Bursary Isolation
- Only `accountant`, `admin`, and `super_admin` can create fee structures, record payments, and issue refunds.
- Fee adjustment logs are permanently mirrored to the tamper-evident audit trail.

