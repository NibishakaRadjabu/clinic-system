ALTER TABLE consultations
    ADD COLUMN lab_reviewed_at DATETIME(6) NULL AFTER final_assessment;
