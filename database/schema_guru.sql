-- Skema Tabel Guru untuk Kelompok 4 (Modul Guru)
-- Sesuai ERD Sistem Informasi Sederhana SMK Malaka
-- Atribut: KODE_GURU (PK), NAMA, FOTOGURU

CREATE TABLE IF NOT EXISTS guru (
  kode_guru VARCHAR(20) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  fotoguru MEDIUMBLOB NULL,
  PRIMARY KEY (kode_guru)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
