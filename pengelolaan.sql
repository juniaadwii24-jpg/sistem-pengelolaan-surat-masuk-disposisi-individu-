DROP TABLE IF EXISTS dispositions;
DROP TABLE IF EXISTS incoming_letters;
DROP TABLE IF EXISTS recipients;

CREATE TABLE recipients (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    position    VARCHAR(100),
    department  VARCHAR(100),
    email       VARCHAR(150),
    created_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE incoming_letters (
    id             SERIAL PRIMARY KEY,
    letter_number  VARCHAR(50) NOT NULL UNIQUE,
    letter_date    DATE NOT NULL,
    received_date  DATE NOT NULL,
    sender         VARCHAR(150) NOT NULL,
    subject        VARCHAR(255) NOT NULL,
    description    TEXT,
    status         VARCHAR(20) NOT NULL DEFAULT 'Received',
    created_at     TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_received_after_letter_date
        CHECK (received_date >= letter_date),
    CONSTRAINT chk_letter_status
        CHECK (status IN ('Received', 'Processing', 'Completed', 'Archived'))
);

CREATE TABLE dispositions (
    id                SERIAL PRIMARY KEY,
    letter_id         INTEGER NOT NULL,
    recipient_id      INTEGER NOT NULL,
    instruction       TEXT NOT NULL,
    disposition_date  DATE NOT NULL,
    status            VARCHAR(20) NOT NULL DEFAULT 'Pending',
    notes             TEXT,
    created_at        TIMESTAMP NOT NULL DEFAULT NOW(),

    CONSTRAINT fk_disposition_letter
        FOREIGN KEY (letter_id) REFERENCES incoming_letters(id) ON DELETE CASCADE,
    CONSTRAINT fk_disposition_recipient
        FOREIGN KEY (recipient_id) REFERENCES recipients(id) ON DELETE RESTRICT,
    CONSTRAINT chk_disposition_status
        CHECK (status IN ('Pending', 'In Progress', 'Completed'))
);

-- index (setelah semua tabel ada)
CREATE INDEX idx_letters_status         ON incoming_letters(status);
CREATE INDEX idx_letters_number         ON incoming_letters(letter_number);
CREATE INDEX idx_dispositions_letter    ON dispositions(letter_id);
CREATE INDEX idx_dispositions_recipient ON dispositions(recipient_id);
CREATE INDEX idx_dispositions_status    ON dispositions(status);

--testing recipients--
INSERT INTO recipients (name, position, department, email) VALUES
('Budi Santoso', 'Kepala Bagian', 'Umum', 'budi.santoso@example.com'),
('Siti Aminah', 'Staff', 'Keuangan', 'siti.aminah@example.com'),
('Andi Wijaya', 'Manager', 'HRD', 'andi.wijaya@example.com');

--testing incoming_letters (harus sebelum dispositions)--
INSERT INTO incoming_letters (letter_number, letter_date, received_date, sender, subject, description, status) VALUES
('001/SM/VIII/2026', '2026-08-01', '2026-08-02', 'PT Maju Jaya', 'Permohonan Kerjasama', 'Surat penawaran kerjasama proyek IT', 'Received'),
('002/SM/VIII/2026', '2026-08-03', '2026-08-04', 'Dinas Komunikasi', 'Undangan Rapat Koordinasi', 'Undangan rapat koordinasi bulanan', 'Processing');

--testing dispositions (terakhir, karena referensi ke 2 tabel di atas)--
INSERT INTO dispositions (letter_id, recipient_id, instruction, disposition_date, status, notes) VALUES
(1, 1, 'Mohon ditindaklanjuti dan buat laporan singkat', '2026-08-05', 'Pending', NULL),
(2, 3, 'Tolong hadiri rapat dan sampaikan hasilnya', '2026-08-05', 'In Progress', 'Rapat dijadwalkan minggu depan');

SELECT
    il.letter_number, il.subject, il.status AS letter_status,
    d.instruction, d.disposition_date, d.status AS disposition_status,
    r.name AS recipient_name, r.department
FROM incoming_letters il
JOIN dispositions d ON d.letter_id = il.id
JOIN recipients r ON r.id = d.recipient_id
WHERE il.id = 1
ORDER BY d.disposition_date DESC;