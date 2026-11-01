# NEPAL LOCALIZATION AND BIKRAM SAMBAT REPORT
**SMS Academy — Laravel School Management System (Nepal Edition)**  
**Branch:** `testing` | **Domain:** Nepal School Academic & Administrative Framework

---

## 1. Nepal Education System Context

Schools operating in Nepal adhere to statutory standards set by the Ministry of Education, Science and Technology (MoEST) and the National Examination Board (NEB):
1. **Official Calendar:** Bikram Sambat (BS) solar calendar (Baishakh to Chaitra).
2. **Academic Sessions:** Academic years commence in Baishakh (mid-April) and conclude in Chaitra (mid-April following year).
3. **Grading System:** Letter grading system (A+, A, B+, B, C+, C, D, E) based on GPA calculation.
4. **Monthly Tuition:** Schools bill tuition fees monthly based on BS months (Baishakh, Jestha, Ashadh, Shrawan, Bhadra, Ashwin, Kartik, Mangshir, Poush, Magh, Falgun, Chaitra).

---

## 2. Bikram Sambat (BS) Architecture & Implementation

### 2.1 Centralized Service (`App\Helpers\NepaliHelper`)
All date conversion logic across the system is centralized in `NepaliHelper` to prevent scattered or inconsistent calculation logic.
- **Authoritative Data Source:** Maintains an exact lookup table of days per month for Bikram Sambat years 2000 BS through 2090 BS.
- **Leap Year and Variable Month Boundaries:** Correctly handles months with 29, 30, 31, or 32 days depending on the solar cycle.
- **Round-Trip Fidelity:** Converts BS date string (`YYYY-MM-DD`) to Gregorian AD (`YYYY-MM-DD`) and vice-versa with 100% round-trip fidelity.

### 2.2 Dual-Date Storage Strategy
To ensure maximum compatibility with SQL date operations, date indexing, and reporting while presenting natural BS dates to staff and parents:
- **AD Date:** Stored in native SQL `date` columns (e.g. `attendance_date`, `due_date`) for date sorting, intervals, and indexing.
- **BS Date:** Stored alongside as `VARCHAR(10)` formatted `YYYY-MM-DD` (e.g. `attendance_date_bs`, `due_date_bs`) or calculated on-the-fly.

### 2.3 Localization Features
- **Nepali Month Names:** बैशाख, जेठ, असार, साउन, भदौ, असोज, कात्तिक, मंसीर, पुस, माघ, फागुन, चैत.
- **Nepali Numerals:** Helper methods convert ASCII digits (0-9) to Devanagari numerals (०, १, २, ३, ४, ५, ६, ७, ८, ९).
- **User Preference:** Users can toggle their display preference between English and Nepali in account settings.

---

## 3. Integrated Nepal Modules

1. **Academic Years (`academic_years` table):** Defines sessions formatted as `2081/2082` with BS start/end boundaries.
2. **Public Holidays (`holidays` table):** Tracks national festivals (Dashain, Tihar, Chhath, Buddha Jayanti, Teej, etc.) and public closures.
3. **Attendance System:** Teachers mark daily attendance recording both Gregorian and BS date.
4. **Monthly Fee Invoicing:** Accountant generates monthly tuition bills for specific BS months with idempotent duplicate prevention.
5. **Mark Sheets:** Complies with NEB letter grading criteria with continuous assessment and theoretical splits.

---

## 4. Test Verification Summary

- `tests/Feature/NepaliCalendarTest.php`: 9 tests verifying conversions, boundary dates, leap years, out-of-bounds rejection.
- `tests/Feature/NepaliMonthlyBillingTest.php`: 9 tests verifying 12-month BS billing, partial payments, and receipts.
- `tests/Feature/AcademicYearAndHolidayTest.php`: 8 tests verifying sessions and holiday management.
**Status:** 100% Passed.
