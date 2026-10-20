ALTER TABLE payments
    MODIFY COLUMN payment_method ENUM('Cash','Card','Insurance','Mobile Money','Airtel Money','Other') NOT NULL;

UPDATE payments
SET payment_method='Airtel Money'
WHERE payment_method='Mobile Money';

ALTER TABLE payments
    MODIFY COLUMN payment_method ENUM('Cash','Card','Insurance','Airtel Money','Other') NOT NULL;
