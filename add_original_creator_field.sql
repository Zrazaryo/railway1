-- Add field to track original document creator
ALTER TABLE documents ADD COLUMN original_created_by INT NULL AFTER created_by;
ALTER TABLE documents ADD COLUMN original_created_at TIMESTAMP NULL AFTER original_created_by;

-- Add index for better performance
ALTER TABLE documents ADD INDEX idx_original_created_by (original_created_by);

-- Add foreign key constraint (optional)
-- ALTER TABLE documents ADD CONSTRAINT fk_original_created_by FOREIGN KEY (original_created_by) REFERENCES users(id) ON DELETE SET NULL;