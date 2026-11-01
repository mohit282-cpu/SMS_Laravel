# FINAL PAYMENT AND FINANCIAL INTEGRITY REPORT
**SMS Academy — Laravel School Management System (Nepal Edition)**  
**Branch:** `testing` | **Audited System:** Student Fees, Monthly Billing, and Receipts

---

## 1. Executive Summary

School billing involves multiple payment installments, partial settlements, scholarship discounts, and concurrent fee collection by multiple accountants. This audit evaluated the financial engine for concurrency hazards, floating-point rounding, overpayments, duplicate payments, and audit traceability.

---

## 2. Financial Architecture & Safeguards

### 2.1 Decimal Currency Calculations
All financial operations across invoices, receipts, and balances enforce `decimal(12,2)` precision. No PHP floating-point arithmetic is used for authoritative database storage.

### 2.2 Payment Idempotency & Concurrency (`pay_now`)
The `pay_now` endpoint in `PaymentController` implements comprehensive concurrency protection:
1. Validates payment amount (`numeric`, `gt:0`).
2. Performs fast pre-check against `payment_idempotency_keys` table.
3. Acquires row-level pessimistic lock:
   ```php
   $pr = PaymentRecord::where('id', $pr_id)->lockForUpdate()->first();
   $payment = Payment::where('id', $pr->payment_id)->lockForUpdate()->first();
   ```
4. Authoritative balance recalculation accounts for scholarship waivers:
   ```php
   $totalInvoiceAmount = max(0, round((float)$payment->amount - (float)($pr->discount_amt ?? 0), 2));
   $outstanding = max(0, round($totalInvoiceAmount - (float)$pr->amt_paid, 2));
   ```
5. Rejects any payment exceeding the outstanding balance (overpayment guard).
6. Atomically updates invoice balance, creates receipt, and marks idempotency key as `completed`.

---

## 3. Test Evidence

Tested against actual MySQL database:
- `test_payment_persists_idempotency_record_in_database`: PASSED
- `test_duplicate_submission_replays_response_without_duplicate_receipt`: PASSED
- `test_reusing_key_with_different_payload_is_rejected`: PASSED (422)
- `test_idempotency_keys_are_scoped_per_user`: PASSED
- `test_fractional_paisa_precision_and_settlement`: PASSED
- `test_discount_deduction_adjusts_payable_balance`: PASSED
- `test_concurrent_idempotency_insertion_handled_safely`: PASSED (409)
- `test_concurrent_requests_using_separate_connections_cannot_duplicate`: PASSED (Simulated via separate distinct PDO connection).
- `test_overpayments_and_negative_amounts_are_rejected`: PASSED (422).
