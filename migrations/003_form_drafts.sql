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
