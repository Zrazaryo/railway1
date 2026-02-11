-- Migration: add deleted_at column to documents
ALTER TABLE documents
ADD COLUMN deleted_at DATETIME NULL AFTER status;
