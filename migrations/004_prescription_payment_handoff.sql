ALTER TABLE prescriptions
    MODIFY COLUMN status ENUM('Pending','Prepared','Partially Dispensed','Dispensed') NOT NULL DEFAULT 'Pending';

ALTER TABLE prescriptions
    ADD COLUMN prepared_by BIGINT UNSIGNED NULL AFTER status,
    ADD COLUMN prepared_at DATETIME NULL AFTER prepared_by,
    ADD COLUMN dispensed_by BIGINT UNSIGNED NULL AFTER prepared_at,
    ADD COLUMN dispensed_at DATETIME NULL AFTER dispensed_by,
    ADD CONSTRAINT fk_prescription_preparer FOREIGN KEY (prepared_by) REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_prescription_dispenser FOREIGN KEY (dispensed_by) REFERENCES users(id) ON DELETE SET NULL;
