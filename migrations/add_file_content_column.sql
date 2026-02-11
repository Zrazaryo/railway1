-- Simpan konten file di DB untuk environment read-only (Vercel)
-- Jalankan sekali di database Railway. Jika kolom file_content sudah ada, abaikan error.
ALTER TABLE document_files ADD COLUMN file_content LONGBLOB NULL AFTER file_type;
