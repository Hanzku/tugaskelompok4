-- Skema Lengkap Sistem Informasi Sederhana SMK Malaka
-- Kelompok 4: Modul Guru & Relasi Diampu
-- Sesuai Diagram Entitas Relasi (ERD)

CREATE DATABASE IF NOT EXISTS sia_malaka_kelompok4
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sia_malaka_kelompok4;

-- 1. Entitas Murid (dari ERD: NIS, NAMA, KELAS, FOTO)
CREATE TABLE IF NOT EXISTS murid (
  nis VARCHAR(20) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  kelas VARCHAR(30) NOT NULL,
  foto MEDIUMBLOB NULL,
  PRIMARY KEY (nis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Entitas Pelajaran (dari ERD: KD_PELAJARAN, NAMA_PELAJARAN, BOBOT)
CREATE TABLE IF NOT EXISTS pelajaran (
  kd_pelajaran VARCHAR(20) NOT NULL,
  nama_pelajaran VARCHAR(100) NOT NULL,
  bobot DECIMAL(5,2) NOT NULL,
  PRIMARY KEY (kd_pelajaran),
  CONSTRAINT chk_pelajaran_bobot CHECK (bobot BETWEEN 1 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Entitas Guru (Kelompok 4 - Fokus Utama: KODE_GURU, NAMA, FOTOGURU)
CREATE TABLE IF NOT EXISTS guru (
  kode_guru VARCHAR(20) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  fotoguru MEDIUMBLOB NULL,
  PRIMARY KEY (kode_guru)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Relasi Mempelajari (dari ERD: NOMORNILAI, NIS, KD_PELAJARAN, NILAI)
CREATE TABLE IF NOT EXISTS mempelajari (
  nomornilai BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nis VARCHAR(20) NOT NULL,
  kd_pelajaran VARCHAR(20) NOT NULL,
  nilai DECIMAL(5,2) NOT NULL,
  PRIMARY KEY (nomornilai),
  CONSTRAINT uq_mempelajari_siswa_pelajaran UNIQUE (nis, kd_pelajaran),
  CONSTRAINT chk_mempelajari_nilai CHECK (nilai BETWEEN 0 AND 100),
  CONSTRAINT fk_mempelajari_murid FOREIGN KEY (nis)
    REFERENCES murid (nis) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mempelajari_pelajaran FOREIGN KEY (kd_pelajaran)
    REFERENCES pelajaran (kd_pelajaran) ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_mempelajari_pelajaran (kd_pelajaran)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Relasi Diampu (dari ERD: KODE_GURU, KD_PELAJARAN, HARI)
CREATE TABLE IF NOT EXISTS diampu (
  kode_guru VARCHAR(20) NOT NULL,
  kd_pelajaran VARCHAR(20) NOT NULL,
  hari VARCHAR(20) NOT NULL,
  PRIMARY KEY (kode_guru, kd_pelajaran, hari),
  CONSTRAINT fk_diampu_guru FOREIGN KEY (kode_guru)
    REFERENCES guru (kode_guru) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_diampu_pelajaran FOREIGN KEY (kd_pelajaran)
    REFERENCES pelajaran (kd_pelajaran) ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_diampu_pelajaran (kd_pelajaran)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
