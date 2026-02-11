-- Migration: Add 'superadmin' role to users table
-- This updates the ENUM column to include 'superadmin'

USE arsip_dokumen_imigrasi;

-- Alter the role column to include 'superadmin'
ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'admin', 'staff') NOT NULL DEFAULT 'staff';

