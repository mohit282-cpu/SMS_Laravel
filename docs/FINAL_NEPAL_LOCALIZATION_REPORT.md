# FINAL NEPAL LOCALIZATION REPORT
**SMS Academy — Laravel School Management System (Nepal Edition)**  
**Branch:** `testing` | **Framework:** Ministry of Education, Science & Technology (MoEST) & NEB Standards

---

## 1. Domain Requirements

Schools operating in Nepal require statutory compliance with:
1. **Bikram Sambat (BS) Calendar:** The national solar calendar running from Baishakh to Chaitra.
2. **Academic Sessions:** Academic years formatted as `2081/2082` commencing in mid-April.
3. **Monthly Fee Billing:** 12 Bikram Sambat billing periods (Baishakh through Chaitra).
4. **Letter Grading:** NEB standard grading (A+, A, B+, B, C+, C, D, E).
5. **Devanagari Numerals:** Rendering numbers in Nepali digits (०-९) and month names.

---

## 2. Technical Implementation

### 2.1 Centralized Service (`App\Helpers\NepaliHelper`)
- Contains exact day-count lookup tables for years 2000 BS through 2090 BS.
- Handles variable solar month durations (29, 30, 31, or 32 days).
- Provides bidirectional conversion between BS (`YYYY-MM-DD`) and AD (`YYYY-MM-DD`) with 100% round-trip consistency.
- Formats dates in English or Nepali based on user preference.

### 2.2 Integrated Modules
- **Dual-Date Storage:** Database models store Gregorian `date` columns for indexing and sorting, alongside formatted BS date strings.
- **Attendance Sheets:** Teachers mark daily attendance displaying BS dates.
- **Monthly Invoicing:** Accountants generate tuition bills for specific BS months with idempotent duplicate prevention.
- **Mark Sheets:** Complies with letter grading standards.

---

## 3. Test Evidence

- `NepaliCalendarTest`: 9/9 passed (conversions, boundaries, leap years, out-of-bounds rejection).
- `NepaliMonthlyBillingTest`: 9/9 passed (12 BS billing months, partial payments, receipts, annual reports).
- `AcademicYearAndHolidayTest`: 8/8 passed (academic sessions and national holiday tracking).
