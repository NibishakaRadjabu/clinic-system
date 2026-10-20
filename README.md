# Clinic Management System

A PHP 8.1+ and MySQL clinic workflow for registration, check-in, triage, consultation, laboratory results, prescriptions, dispensing, billing, follow-up, and discharge.

## Project structure

- `index.php` — front controller and page entry point.
- `config.php` — MySQL connection settings.
- `schema.sql` — relational database schema and starter medicine catalog.
- `migrations/` — one-time database updates for existing installations.
- `app/bootstrap.php` — session, shared helpers, and request initialization.
- `app/actions.php` — validated POST actions for clinic workflows (internal-only).
- `app/helpers.php` — shared escaping, form, status, and formatting helpers.
- `views/layout.php`, `views/auth.php`, and `views/loading.php` — shared layouts and accessible request-loading indicator (internal-only).
- `views/pages/` — dashboard, registration, patient search, visit, pharmacy, staff, receipt, and not-found pages.
- `assets/css/style.css` — responsive application styles.
- `assets/Designer.png` — clinic-management logo used in page branding, browser icons, and receipts.
- `assets/js/loading.js` — displays the loading indicator while POST forms are submitted.
- `assets/js/autosave.js` and `assets/js/live-dashboard.js` — restore user-scoped form drafts and refresh the dashboard queue.
- `storage/reports/` — uploaded laboratory reports; web access is denied by `storage/.htaccess`.

## Run with XAMPP

1. Copy this folder into `C:\xampp\htdocs\clinic system`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`, import `schema.sql`, and confirm the `clinic_management` database was created.
4. If your MySQL credentials differ from the XAMPP defaults, update `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` in `config.php`.
5. If you already have an earlier installation, import any missing migrations in order: `001_sequential_visit_workflow.sql`, `002_multi_clinic_tenants.sql`, `003_form_drafts.sql`, `004_prescription_payment_handoff.sql`, then `005_airtel_money_payment_method.sql`. Migration 002 creates the first `Main Clinic` tenant and assigns the existing clinic's records and staff to it.
6. Visit `http://localhost/clinic%20system/`. On a fresh database, create the first clinic and its administrator. Additional clinics can register from the sign-in screen.

Each clinic signs in by selecting its clinic name and entering its own account. Clinic administrators can rename their clinic and add staff. Patient records, visits, queue numbers, medicines, prescriptions, and billing are kept within the selected clinic.

Clinic administrators can edit staff names, usernames, roles, account status, and optionally reset passwords, or delete other staff accounts. The signed-in administrator cannot delete their own account, and every clinic must retain at least one active administrator. Deleting staff preserves clinical records while clearing the deleted account from author fields.

The application uses PHP sessions and password hashing. Use HTTPS and configure backups and appropriate access controls before storing real patient information. The starter medicine catalog is editable by adding medicines from the Pharmacy section.

Visit steps unlock in order. Triage requires vital signs and complaints; ordered tests must be completed and reviewed by a doctor. The doctor writes prescriptions; pharmacy prepares the medication and prints a prescription slip for the patient; the cashier records payment and issues a receipt; the pharmacist verifies the receipt and then dispenses the medication. The invoice must be settled and all prescribed medication dispensed before follow-up scheduling or discharge. Laboratory testing, pharmacy, and follow-up can be skipped when they are not needed. Receptionists register arrivals and check in returning patients, including patients due for follow-up. Completed visits are read-only.

The interface supports English, French, and Kiswahili. Choose a language from the selector in the sign-in screen or the application header; the choice is remembered for the current session.

Editable registration, check-in, triage, consultation, lab-result, prescription, invoice, payment, and follow-up forms save drafts automatically for the signed-in staff member. Drafts are isolated by clinic and user, restored when the same form is reopened, and removed after that form is successfully submitted. Uploaded lab reports and irreversible workflow actions are never auto-submitted or saved as drafts. The dashboard refreshes its visit counts and patient queue every 10 seconds while open.

The signed-in application also provides floating quick actions to register a patient, download a clinic-scoped patient CSV export, or check in an existing patient using their patient ID.

Doctor accounts see a live attention queue on the dashboard for visits that are ready for the doctor, in consultation, or awaiting review of available results. Each patient row states the doctor’s next action and links directly to that visit.

The accessible request-loading indicator appears while valid POST forms are being submitted; it does not appear during ordinary page viewing.
