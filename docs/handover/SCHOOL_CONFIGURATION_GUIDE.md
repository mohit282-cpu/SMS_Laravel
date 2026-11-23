# School Configuration & Academic Setup Guide

**Target Audience**: Academic Directors, Exam Controllers, Bursars  
**Module Scope**: Classes, Sections, Subjects, Grading, Examinations, Fees  

---

## 1. Class & Section Architecture

### Step 1: Create Academic Classes
Navigate to **Academics -> Classes** (`/classes`):
- Click **Create New Class**.
- Select the **Class Type**:
  - `Creche` / `Pre Nursery` / `Nursery` (Early Childhood)
  - `Primary` (Grade 1 - 5)
  - `Junior Secondary` (Grade 6 - 8)
  - `Senior Secondary` (Grade 9 - 10 / SEE level, Grade 11 - 12 / NEB level)
- Enter the Class Name (e.g., `Grade 10`).

### Step 2: Create Class Sections
Navigate to **Academics -> Sections** (`/sections`):
- Click **Create Section**.
- Enter Section Name (e.g., `Section A (Sagarmatha)`, `Section B (Annapurna)`).
- Select the Class to bind with.
- Assign the **Class Teacher** (responsible for daily morning roll call and pastoral welfare).

---

## 2. Curriculum & Subject Mapping

Navigate to **Academics -> Subjects** (`/subjects`):
- Click **Create Subject**.
- Fields:
  - **Subject Name**: (e.g., `Compulsory Mathematics`, `Science & Technology`, `Nepali`, `English`).
  - **Short Name / Code**: (e.g., `MATH10`, `SCI10`).
  - **Class**: Link to the target grade.
  - **Assigned Teacher**: Link to the designated instructor.

---

## 3. Nepal National Curriculum & Grading Configuration

The system incorporates the official National Examination Board (NEB) and Curriculum Development Centre (CDC) standard letter grading matrix:

| Grade | Marks Percentage Range | Grade Point (GPA) | Performance Level | Remark |
|---|---|---|---|---|
| **A+** | 90% – 100% | 4.0 | Outstanding | उत्कृष्ट |
| **A** | 80% – 89% | 3.6 | Excellent | सर्वोत्तम |
| **B+** | 70% – 79% | 3.2 | Very Good | धेरै राम्रो |
| **B** | 60% – 69% | 2.8 | Good | राम्रो |
| **C+** | 50% – 59% | 2.4 | Satisfactory | सन्तोषजनक |
| **C** | 40% – 49% | 2.0 | Acceptable | ग्राह्य |
| **D** | 35% – 39% | 1.6 | Basic | आधारभूत |
| **NG** | Below 35% | 0.0 | Non-Graded | स्तर अवर्गीकृत |

These benchmarks are pre-seeded in the database reference tables. Custom grade boundaries may be inspected or tuned under **Examinations -> Grades**.

---

## 4. Student Enrollment & Parent Linking

### Enrolling a New Student (`/students/create`):
1. **Personal Information**: Full Name, Nepali Script Name (`name_ne`), Gender, Date of Birth (dual AD/BS picker), Blood Group.
2. **Geographic Demographics**: Province (1-7), District (77), Municipality/Rural Municipality (753), Ward Number, Tole.
3. **Academic Admission**: Unique Admission Number (`APEX-2083-XXX`), Class, Section, Admitted Session.
4. **Parent Association**:
   - Link existing parent from database by phone/email; OR
   - Create a new Parent Account simultaneously with contact phone number.

---

## 5. Financial Structure & Fee Heads

Navigate to **Finance -> Payments** (`/payments`):
1. Create Fee Heads:
   - Example: `Monthly Tuition Fee`, `Computer & Science Lab Fee`, `Examination Fee`, `Transportation Fee`.
2. Assign target class or apply across entire school.
3. Generate Term Invoices:
   - Generates individual student ledger entries (`payment_records`).
4. Collect Payment & Print Receipt:
   - Cash / Cheque / Bank Transfer / Digital Wallet reference.
   - Generates serial-numbered, anti-counterfeit receipt (`receipts`).

