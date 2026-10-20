CREATE DATABASE IF NOT EXISTS clinic_management
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinic_management;

CREATE TABLE IF NOT EXISTS clinics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinic_id BIGINT UNSIGNED NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin','Receptionist','Nurse','Doctor','Laboratory','Pharmacist','Cashier') NOT NULL DEFAULT 'Receptionist',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id),
    UNIQUE KEY uq_users_clinic_username (clinic_id, username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS patients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_number VARCHAR(30) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    date_of_birth DATE NULL,
    gender ENUM('Female','Male','Other') NOT NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(150) NULL,
    address VARCHAR(255) NULL,
    insurance_provider VARCHAR(150) NULL,
    insurance_number VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_patients_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id),
    UNIQUE KEY uq_patients_clinic_id (clinic_id, id),
    UNIQUE KEY uq_patients_clinic_number (clinic_id, patient_number),
    INDEX idx_patient_name (full_name),
    INDEX idx_patient_phone (phone)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS visits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinic_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    queue_number INT UNSIGNED NOT NULL,
    visit_date DATE NOT NULL,
    status ENUM('Waiting','Ready for Doctor','In Consultation','Waiting for Results','Results Available','Ready for Pharmacy','Ready for Billing','Follow-up Scheduled','Completed') NOT NULL DEFAULT 'Waiting',
    reason_for_visit VARCHAR(500) NULL,
    checked_in_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    CONSTRAINT fk_visits_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id),
    CONSTRAINT fk_visits_patient FOREIGN KEY (clinic_id, patient_id) REFERENCES patients(clinic_id, id),
    CONSTRAINT fk_visits_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_daily_queue (clinic_id, visit_date, queue_number),
    INDEX idx_visit_queue (clinic_id, visit_date, status, queue_number)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS queue_counters (
    clinic_id BIGINT UNSIGNED NOT NULL,
    visit_date DATE NOT NULL,
    last_number INT UNSIGNED NOT NULL,
    CONSTRAINT fk_queue_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id),
    PRIMARY KEY (clinic_id, visit_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS triage (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_id BIGINT UNSIGNED NOT NULL UNIQUE,
    weight_kg DECIMAL(6,2) NULL,
    height_cm DECIMAL(6,2) NULL,
    blood_pressure VARCHAR(20) NULL,
    temperature_c DECIMAL(4,1) NULL,
    pulse_bpm SMALLINT UNSIGNED NULL,
    oxygen_percent DECIMAL(5,2) NULL,
    complaints TEXT NULL,
    recorded_by BIGINT UNSIGNED NULL,
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_triage_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    CONSTRAINT fk_triage_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS consultations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_id BIGINT UNSIGNED NOT NULL UNIQUE,
    chief_complaint TEXT NULL,
    medical_history TEXT NULL,
    symptoms TEXT NULL,
    examination TEXT NULL,
    diagnosis TEXT NULL,
    treatment_plan TEXT NULL,
    final_assessment TEXT NULL,
    lab_reviewed_at DATETIME(6) NULL,
    recorded_by BIGINT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_consult_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    CONSTRAINT fk_consult_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lab_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_id BIGINT UNSIGNED NOT NULL,
    test_name VARCHAR(150) NOT NULL,
    instructions TEXT NULL,
    status ENUM('Ordered','In Progress','Results Available') NOT NULL DEFAULT 'Ordered',
    requested_by BIGINT UNSIGNED NULL,
    ordered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lab_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    CONSTRAINT fk_lab_user FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_lab_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lab_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lab_order_id BIGINT UNSIGNED NOT NULL UNIQUE,
    result_text TEXT NOT NULL,
    report_file VARCHAR(255) NULL,
    entered_by BIGINT UNSIGNED NULL,
    entered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_result_order FOREIGN KEY (lab_order_id) REFERENCES lab_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_result_user FOREIGN KEY (entered_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS medicines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinic_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    strength VARCHAR(80) NULL,
    stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_medicines_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id),
    UNIQUE KEY uq_medicines_clinic_name (clinic_id, name),
    UNIQUE KEY uq_medicines_clinic_id (clinic_id, id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS prescriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_id BIGINT UNSIGNED NOT NULL,
    prescribed_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    status ENUM('Pending','Prepared','Partially Dispensed','Dispensed') NOT NULL DEFAULT 'Pending',
    prepared_by BIGINT UNSIGNED NULL,
    prepared_at DATETIME NULL,
    dispensed_by BIGINT UNSIGNED NULL,
    dispensed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_prescription_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    CONSTRAINT fk_prescription_user FOREIGN KEY (prescribed_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_prescription_preparer FOREIGN KEY (prepared_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_prescription_dispenser FOREIGN KEY (dispensed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS prescription_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prescription_id BIGINT UNSIGNED NOT NULL,
    medicine_id BIGINT UNSIGNED NOT NULL,
    dosage VARCHAR(120) NOT NULL,
    duration VARCHAR(100) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    instructions VARCHAR(255) NULL,
    dispensed_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_item_prescription FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    CONSTRAINT fk_item_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id),
    INDEX idx_item_prescription (prescription_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_id BIGINT UNSIGNED NOT NULL UNIQUE,
    consultation_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    other_charges DECIMAL(10,2) NOT NULL DEFAULT 0,
    notes VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoice_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    CONSTRAINT fk_invoice_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash','Card','Insurance','Airtel Money','Other') NOT NULL,
    reference VARCHAR(120) NULL,
    received_by BIGINT UNSIGNED NULL,
    paid_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_user FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_payment_invoice (invoice_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS follow_ups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_id BIGINT UNSIGNED NOT NULL,
    scheduled_date DATE NOT NULL,
    specialist VARCHAR(150) NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_followup_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    CONSTRAINT fk_followup_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS form_drafts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinic_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    visit_id BIGINT UNSIGNED NULL,
    draft_key VARCHAR(100) NOT NULL,
    payload JSON NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_form_drafts_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    CONSTRAINT fk_form_drafts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_form_drafts_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
    UNIQUE KEY uq_form_drafts_owner_key (clinic_id, user_id, draft_key),
    INDEX idx_form_drafts_visit (clinic_id, user_id, visit_id)
) ENGINE=InnoDB;
