-- Review AI moderation for perfume_ecommerce
-- Existing customer reviews remain published. New and edited reviews wait for moderation.
ALTER TABLE reviews
  ADD COLUMN moderation_status ENUM('pending','approved','flagged','rejected') NOT NULL DEFAULT 'approved',
  ADD COLUMN moderation_reason VARCHAR(255) NULL,
  ADD COLUMN moderated_at DATETIME NULL;

UPDATE reviews
SET moderation_status = CASE WHEN is_active = 1 THEN 'approved' ELSE 'rejected' END,
    moderated_at = CASE WHEN is_active = 1 THEN CURRENT_TIMESTAMP ELSE NULL END;

ALTER TABLE reviews
  ALTER COLUMN moderation_status SET DEFAULT 'approved';