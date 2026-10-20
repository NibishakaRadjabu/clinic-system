CREATE TABLE clinics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO clinics (name, slug) VALUES ('Main Clinic', 'main-clinic');

ALTER TABLE users
    ADD COLUMN clinic_id BIGINT UNSIGNED NULL AFTER id;
ALTER TABLE patients
    ADD COLUMN clinic_id BIGINT UNSIGNED NULL AFTER id;
ALTER TABLE visits
    ADD COLUMN clinic_id BIGINT UNSIGNED NULL AFTER id;
ALTER TABLE medicines
    ADD COLUMN clinic_id BIGINT UNSIGNED NULL AFTER id;
ALTER TABLE queue_counters
    ADD COLUMN clinic_id BIGINT UNSIGNED NULL FIRST;

UPDATE users
SET clinic_id = (SELECT id FROM clinics WHERE slug = 'main-clinic');
UPDATE patients
SET clinic_id = (SELECT id FROM clinics WHERE slug = 'main-clinic');
UPDATE visits
SET clinic_id = (SELECT id FROM clinics WHERE slug = 'main-clinic');
UPDATE medicines
SET clinic_id = (SELECT id FROM clinics WHERE slug = 'main-clinic');
UPDATE queue_counters
SET clinic_id = (SELECT id FROM clinics WHERE slug = 'main-clinic');

ALTER TABLE users
    DROP INDEX username,
    MODIFY clinic_id BIGINT UNSIGNED NOT NULL,
    ADD UNIQUE KEY uq_users_clinic_username (clinic_id, username),
    ADD CONSTRAINT fk_users_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id);

ALTER TABLE patients
    DROP INDEX patient_number,
    MODIFY clinic_id BIGINT UNSIGNED NOT NULL,
    ADD UNIQUE KEY uq_patients_clinic_id (clinic_id, id),
    ADD UNIQUE KEY uq_patients_clinic_number (clinic_id, patient_number),
    ADD CONSTRAINT fk_patients_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id);

ALTER TABLE visits
    DROP FOREIGN KEY fk_visits_patient;

ALTER TABLE visits
    DROP INDEX fk_visits_patient,
    DROP INDEX uq_daily_queue,
    DROP INDEX idx_visit_queue,
    MODIFY clinic_id BIGINT UNSIGNED NOT NULL,
    ADD UNIQUE KEY uq_daily_queue (clinic_id, visit_date, queue_number),
    ADD INDEX idx_visit_queue (clinic_id, visit_date, status, queue_number),
    ADD CONSTRAINT fk_visits_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id),
    ADD CONSTRAINT fk_visits_patient FOREIGN KEY (clinic_id, patient_id) REFERENCES patients(clinic_id, id);

ALTER TABLE medicines
    DROP INDEX name,
    MODIFY clinic_id BIGINT UNSIGNED NOT NULL,
    ADD UNIQUE KEY uq_medicines_clinic_name (clinic_id, name),
    ADD UNIQUE KEY uq_medicines_clinic_id (clinic_id, id),
    ADD CONSTRAINT fk_medicines_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id);

ALTER TABLE queue_counters
    DROP PRIMARY KEY,
    MODIFY clinic_id BIGINT UNSIGNED NOT NULL,
    ADD PRIMARY KEY (clinic_id, visit_date),
    ADD CONSTRAINT fk_queue_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id);
