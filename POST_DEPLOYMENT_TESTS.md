# Post-Deployment Tests — Restaurant POS

Run these tests on the **live URL** after install/optimize.  
Goal: prove every major workflow still works exactly as before packaging.  
Do **not** change code to “make tests pass” unless a real defect is found.

Mark each result: **PASS / FAIL / N/A**.

---

## 0. Baseline

| # | Test | Expected | Result |
|---|------|----------|--------|
| 0.1 | Open `/` | Login page (or redirect if already auth) | |
| 0.2 | Open `/up` | Health OK (Laravel health) | |
| 0.3 | View page source / network | `public/build` CSS & JS load (no Vite “manifest not found”) | |
| 0.4 | `APP_DEBUG` | No debug stack traces for normal pages | |
| 0.5 | HTTPS | Site loads on HTTPS without mixed-content console errors | |

---

## 1. Authentication & access control

| # | Test | Expected | Result |
|---|------|----------|--------|
| 1.1 | Password login (admin) | Session created; land on dashboard (or branch/shop UI pickers) | |
| 1.2 | PIN login (cashier/waiter) | Login succeeds; role home page | |
| 1.3 | Logout | Session cleared; back to login | |
| 1.4 | Waiter user | Restricted to waiter routes only | |
| 1.5 | Billiards user (if used) | Restricted to billiards routes | |
| 1.6 | Multi-branch user | Branch select appears when required | |
| 1.7 | Shop UI select | Restaurant/bakery/ice cream picker when enabled | |
| 1.8 | Unauthorized page | 403 / redirect — no data leak | |

---

## 2. Admin catalog & master data

| # | Test | Expected | Result |
|---|------|----------|--------|
| 2.1 | Categories CRUD | Create/edit/list works | |
| 2.2 | Products CRUD + image | Saves; image visible | |
| 2.3 | Variants / addons | Attach and show on POS | |
| 2.4 | Ingredients + recipes | Recipe items save | |
| 2.5 | Floors / tables | QR code / QR card generates | |
| 2.6 | Kitchens admin | Printer IP/mode saves | |
| 2.7 | Users / roles | Permission assign saves | |
| 2.8 | Branches | Create/switch when multi-branch enabled | |

---

## 3. POS checkout (critical path)

| # | Test | Expected | Result |
|---|------|----------|--------|
| 3.1 | Open cash register | Register opens (shift or day-end mode) | |
| 3.2 | Add products to cart | Totals correct (tax/service if configured) | |
| 3.3 | Place / send to kitchen | Order saved; KOT created for kot/bot categories | |
| 3.4 | Direct items | Direct categories bill without blocking kitchen if configured | |
| 3.5 | Payment cash | Payment recorded; register totals update | |
| 3.6 | Payment card / mixed | Lines stored; accounts posted | |
| 3.7 | Change given | Net cash posting correct | |
| 3.8 | Hold / recall | Held order restores | |
| 3.9 | Open bill / add items | Appends without breaking existing bill | |
| 3.10 | Receipt print / preview | Preview or bridge/network print returns success | |
| 3.11 | Cash drawer kick | Drawer opens when configured | |
| 3.12 | Close register | Difference calculated; optional email PDF | |

Dependency chain verified by this section:

```text
PosApi → OrderNumberService → Order/Payment
      → AccountService
      → KitchenTicketService → StockMovement / Ingredient
      → LoyaltyService (if customer)
      → DeliveryPartnerLedgerService (if delivery)
      → NetworkPrinter / Print Bridge
```

---

## 4. Kitchen

| # | Test | Expected | Result |
|---|------|----------|--------|
| 4.1 | Kitchen display (public URL) | Shows pending tickets | |
| 4.2 | KDS (auth) | Status transitions pending → preparing → ready/served | |
| 4.3 | Bar panel | BOT tickets appear | |
| 4.4 | Auto / network print | Print job attempted per kitchen settings | |
| 4.5 | Customer display / dual display | Cart/LED/marketing idle content loads | |

---

## 5. Waiter & QR

| # | Test | Expected | Result |
|---|------|----------|--------|
| 5.1 | Waiter dashboard PWA | Manifest + icons load | |
| 5.2 | Waiter place order | KOT created; stock deducted | |
| 5.3 | Waiter kitchen ready view | Ready items listed | |
| 5.4 | QR menu via table code | Menu loads on phone | |
| 5.5 | QR place order | Pending approval **or** auto KOT per settings | |
| 5.6 | Waiter accept/reject QR | Approval path creates tickets | |
| 5.7 | Bill request | Flag visible to cashier/POS | |
| 5.8 | Guest rating link | Rating saves | |

---

## 6. Self ordering

| # | Test | Expected | Result |
|---|------|----------|--------|
| 6.1 | `/self-order` | UI loads | |
| 6.2 | Checkout | Order persists; kitchen visibility per implementation | |

---

## 7. Inventory, purchases, accounts

| # | Test | Expected | Result |
|---|------|----------|--------|
| 7.1 | Recipe sale stock | Ingredient qty decreases; stock_movements row | |
| 7.2 | Direct product stock | Product stock decreases when tracked | |
| 7.3 | Manual inventory adjust | Movement logged | |
| 7.4 | Purchase create | Stock increases | |
| 7.5 | Supplier payment cash/bank | Account outflow | |
| 7.6 | Supplier cheque | Cheque pending; ledger posts on clear | |
| 7.7 | Expense | Account postExpense | |
| 7.8 | Account transfer | Balances move correctly | |
| 7.9 | Reports: stock | Opens with data | |

---

## 8. Delivery partners & loyalty

| # | Test | Expected | Result |
|---|------|----------|--------|
| 8.1 | Delivery order with partner | Partner fee / settlement fields set | |
| 8.2 | Partner ledger due | Debit entry created when tracked | |
| 8.3 | Receive partner payment | Credits + account inflow + allocations | |
| 8.4 | Loyalty enroll | Token + card URL | |
| 8.5 | Earn stamps on paid order | Stamp log + counters | |
| 8.6 | Redeem free item | Free line / redeem log | |
| 8.7 | Public loyalty card | Opens without auth | |

---

## 9. Notifications & marketing

| # | Test | Expected | Result |
|---|------|----------|--------|
| 9.1 | SMS test | External API success; `notification_logs` row | |
| 9.2 | WhatsApp test | Meta API success | |
| 9.3 | SMTP test | Mail arrives | |
| 9.4 | Promo campaign dry run | Validates recipients / template rules | |
| 9.5 | In-app inbox | Reminder appears for admin/manager | |
| 9.6 | Marketing ads on customer display | Idle posters show | |

---

## 10. Reports & AI

| # | Test | Expected | Result |
|---|------|----------|--------|
| 10.1 | Sales report | Totals match sample orders | |
| 10.2 | Products / customers / payments / shifts | Pages render | |
| 10.3 | Waiter report | Rankings calculate | |
| 10.4 | Day-end report | Aggregates shifts | |
| 10.5 | AI assistant chat | Local or Gemini reply; no 500 | |
| 10.6 | AI tools (sales/low stock) | Tool answers when enabled | |

---

## 11. Billiards (if enabled)

| # | Test | Expected | Result |
|---|------|----------|--------|
| 11.1 | Desk view | Tables list | |
| 11.2 | Create booking → start → complete | Amount calculated | |
| 11.3 | Payment | BilliardPayment + account inflow | |
| 11.4 | SMS e-bill / reminder | Sends when configured | |
| 11.5 | Public display | Feed updates | |
| 11.6 | Print receipt | Preview/print OK | |

---

## 12. Barcode / labels / PWA / installer lock

| # | Test | Expected | Result |
|---|------|----------|--------|
| 12.1 | Barcode label browser print | Layout renders | |
| 12.2 | Table QR card | Printable card | |
| 12.3 | Waiter PWA install prompt | Manifest valid | |
| 12.4 | Revisit `/install` when installed | Blocked / redirected (not wipe) | |
| 12.5 | Storage fallback | Image URL works without symlink | |

---

## 13. Performance / hosting sanity

| # | Test | Expected | Result |
|---|------|----------|--------|
| 13.1 | Warm caches | `optimize:hosting` without error | |
| 13.2 | Cron | `schedule:run` exits 0 | |
| 13.3 | Error log | No recurring fatals in `storage/logs` after smoke | |
| 13.4 | Session persistence | Stay logged in across page loads | |

---

## Sign-off

| Environment URL | |
|-----------------|---|
| Deploy date | |
| Tester | |
| Overall | PASS / FAIL |
| Blocking issues | |
| Notes | |
