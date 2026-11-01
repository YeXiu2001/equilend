# Equilend: Equipment Management System for CCS

**Equilend** is a web-based equipment management, inventory tracking, and reservation platform tailored for the College of Computer Studies (CCS), with architectural provisions for multi-organization/campus-wide deployment.

---

## ⏱️ Development Timeline

- **Estimated Development Time:** _[To be determined / e.g., 6–10 Weeks]_
- **Target Launch:** _[TBD]_

---

## 🛠️ Proposed Tech Stack

| Component                    | Technology / Service                             | Notes & Justifications                                                                                                      |
| :--------------------------- | :----------------------------------------------- | :-------------------------------------------------------------------------------------------------------------------------- |
| **Full Stack Framework**     | **Laravel 13.x** + **Livewire 4.x**              | Modern, reactive PHP stack without full SPA complexity; rapid UI updates via Livewire.                                      |
| **UI & Styling**             | **Tailwind CSS v4** + **daisyUI v5**             | Utility-first CSS framework paired with daisyUI component classes for clean, responsive, and maintainable styling.          |
| **Database**                 | **MySQL**                                        | Reliable relational database management system for inventory, bookings, and audit trails.                                   |
| **Role-Based Access (RBAC)** | **Laravel Spatie** (`spatie/laravel-permission`) | Industry-standard role and permission management.                                                                           |
| **Authentication**           | **Laravel Fortify**                              | Headless authentication backend covering login, 2FA, password resets, etc.                                                  |
| **API Security**             | **Laravel Sanctum**                              | Token-based API authentication for mobile or third-party service endpoints.                                                 |
| **Email / SMTP**             | **Brevo (Free Tier)**                            | 300 emails/day; handles automated alerts and loan receipts (note: potential queue delays on free tier).                     |
| **SMS Gateway**              | _Excluded / Deferred_                            | Minimum cost threshold (~₱2,000 via Semaphore) + telco sender-name compliance (Smart/TNT); replaced with email/push alerts. |
| **Hosting & Infrastructure** | **DigitalOcean Ubuntu VPS**                      | Scalable Linux VPS instance (Nginx + PHP-FPM + MySQL + Redis/Queue worker).                                                 |
| **Analytics & Data Viz**     | **Chart.js**                                     | Visual reports for inventory status, booking trends, and penalty collections.                                               |

---

## 📦 System Modules & Features

### 1. 🔐 Authentication & Access Control

- **Role-Based Redirection:** Credential validation redirects users automatically to:
    - **Borrower Portal:** Students and non-admin faculty.
    - **Admin Dashboard:** System administrators, inventory officers, and staff.
- **Admin Pre-Registration:** High-level accounts (Admins / College Staff) are pre-registered and provisioned exclusively by the **Superadmin**.

### 2. 👤 Account & Profile Management

- View and update personal profile information (name, contact email, student ID, avatar, password).

### 3. 🏢 Organization & Multi-Tenancy Settings

- **Modular College Scoping:** Designed to allow seamless expansion across colleges or multi-campus deployments.
- **Organization-Level Configurations:**
    - College / Department name and code.
    - Official collection details (e.g., GCash merchant/account number, PayPal recipient ID).

### 4. 🎒 Borrower Portal (Student / Faculty Facing)

- **Active Borrowed Items:** Track ongoing loans, item details, checkout timestamp, and expected return deadline.
- **Booking & Reservations (CRUD):** Request equipment reservations in advance.
- **Borrowing History:** Log of past completed transactions, returns, and on-time ratings.
- **Penalty Overview:** Visibility into outstanding dues, overdue records, and settlement status.

### 5. 📦 Inventory & Asset Management

- **Item Catalog (CRUD):** Manage item names, categories, descriptions, specs, and total vs. available counts.
- **Unit-Level Tracking:** Status tracking is enforced at the unique **Item ID** level (handling batches where count $\ge 1$).
- **Restocking Logs:** Track intake of new stocks, purchase references, and batch arrival logs.
- **Reinventory / Audits:** Audit history and adjustments to reconcile lost, broken, or updated inventory.

### 6. 🔄 Booking & Loan Transactions

- **Online Booking Workflow:** Review, approve, or reject student reservation requests (mandatory rejection remarks).
- **Checkout & Check-in Management:** Record physical handovers and returns.
- **Overdue Tracking:** Real-time list of unreturned items paired with borrower contact records.
- **Penalty Processing:** Assign, complete, or void penalties directly from transaction views.
- **Automated Triggers:** System notifications dispatched upon loan creation and reservation approval.

### 7. 💳 Penalty Collections & Payments

- **Payment Log (CRUD):** Record penalty payments against student balance.
- **Multi-Channel Support:** Supports Cash, GCash, Maya (PayMaya), and PayPal transactions with reference tracking.

### 8. 📅 Equipment Availability Calendar

- Visual calendar showing when equipment is scheduled, reserved, under maintenance, or available.

### 9. 📊 Reporting & Analytics _(Tentative)_

- **Inventory Report:** Stock turnover, asset conditions, and missing/damaged equipment summaries.
- **Transaction Report:** Daily, weekly, and monthly loan frequency metrics.
- **Penalty & Collection Report:** Total fines levied, collected, and outstanding balances.

---

## ⚙️ Key Functional Rules & Automation

1. **Advance Deadline Reminders:** Automated notification sent at least **1 hour prior** to scheduled return time.
2. **Penalty Notifications:** Automated alerts sent to borrowers as soon as a penalty is imposed.
3. **Borrowing Suspension:** Automatic borrowing lock applied to accounts with unsettled penalties or unresolved overdue equipment.
4. **Damage / Loss Assessment:** Dedicated workflow to assess repair/replacement fees for damaged or destroyed equipment.

---

## 🎨 UI Design & Color Palette

The color system is derived from the official **College of Computer Studies (CCS)** emblem logo, implemented using **daisyUI v5** semantic tokens on top of **Tailwind CSS v4**.

### 🌟 Brand Palette (Extracted from CCS Logo)

| Token           |  Swatch   | Hex Code  | RGB                 | Content / Contrast     | Usage & Application                                                                      |
| :-------------- | :-------: | :-------- | :------------------ | :--------------------- | :--------------------------------------------------------------------------------------- |
| **`primary`**   | `#0d6e78` | `#0d6e78` | `rgb(13, 110, 120)` | `#ffffff` (5.6:1 AA)   | Main action buttons, active navigation states, key brand elements (central shield wave)  |
| **`secondary`** | `#0e3744` | `#0e3744` | `rgb(14, 55, 68)`   | `#ffffff` (10.4:1 AAA) | Headers, secondary actions, structural emphasis (top "CCS" wordmark & hull)              |
| **`accent`**    | `#1ea399` | `#1ea399` | `rgb(30, 163, 153)` | `#0d1927` (8.5:1 AAA)  | Highlights, badges, focus rings, CTAs, status indicators (bottom dynamic swoop)          |
| **`neutral`**   | `#0d1927` | `#0d1927` | `rgb(13, 25, 39)`   | `#ffffff` (17.5:1 AAA) | Dark buttons, table headers, dark surfaces, high-contrast framing (outer shield outline) |

### 🚦 Semantic & Status Colors

Retained standard feedback colors for consistency across modals, alerts, toasts, and form validations:

| Token         | Hex Code  | Purpose                                                         |
| :------------ | :-------- | :-------------------------------------------------------------- |
| **`info`**    | `#0284c7` | System announcements, instructions, neutral updates             |
| **`success`** | `#16a34a` | Successful bookings, completed returns, penalty settlements     |
| **`warning`** | `#f59e0b` | Due date warnings, maintenance reminders, pending approvals     |
| **`error`**   | `#dc2626` | Overdue loans, validation errors, damage reports, account locks |

### 🌓 Theme Configuration (daisyUI v5)

Themes are configured in `resources/css/app.css` using the `@plugin 'daisyui/theme'` syntax:

- **Light Mode (`light`):** Uses the crisp white and cool-tinted base backgrounds (`#ffffff`, `#f8fafb`) with `#0d6e78` as primary.
- **Dark Mode (`dark`):** Automatically activates on system dark preference (`prefersdark: true`), leveraging deep midnight slate (`#0d1927`) and elevated mint-teal highlights (`#1ea399`).

> Always use semantic daisyUI utility classes (e.g., `btn-primary`, `bg-base-100`, `text-primary`, `badge-accent`) rather than hardcoded hex values to guarantee proper light/dark theme switching.
