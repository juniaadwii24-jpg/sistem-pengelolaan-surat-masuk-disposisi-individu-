<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model untuk tabel "incoming_letters".
 *
 * PENYESUAIAN dengan modul Disposisi:
 * - Primary key disamakan menjadi kolom "id" (sebelumnya "id_incoming_letters").
 *   Alasan: Disposition_model (modul disposisi) sudah lebih dulu jadi dan
 *   melakukan JOIN dengan asumsi "il.id = d.letter_id". Supaya kedua modul
 *   bisa langsung nyambung tanpa mengubah Disposition_model, kolom PK di sini
 *   yang disesuaikan mengikuti Disposition_model.
 * - Ditambahkan getById() (mengembalikan OBJECT lewat ->row()) khusus untuk
 *   dipakai DispositionsController::save(), karena di sana kodenya mengakses
 *   hasilnya dengan tanda panah ($letter->status), bukan sebagai array.
 *   get_by_id() (versi array, dipakai halaman detail surat) TETAP dipertahankan
 *   supaya tidak mengubah alur yang sudah jalan di Incoming_letters::detail().
 * - Ditambahkan getSelectOptions(), dipanggil oleh
 *   DispositionsController::getSelectSurat() untuk mengisi dropdown "Surat Masuk"
 *   pada form disposisi. Sebelumnya method ini tidak ada sama sekali.
 * - Ditambahkan delete(), dipakai oleh Incoming_letters::delete() untuk
 *   menghapus surat dari halaman list.
 */
class Incoming_letter_model extends CI_Model
{
    /**
     * Mengambil daftar surat, dengan filter opsional berdasarkan nomor surat
     * (LIKE) dan/atau status (WHERE persis). Dipakai oleh Incoming_letters::index()
     * untuk mengisi tabel di halaman list beserta fitur pencarian/filternya.
     *
     * @param string|null $search_number kata kunci pencarian nomor surat
     * @param string|null $status_filter status surat yang ingin ditampilkan saja
     * @return array daftar surat dalam bentuk array asosiatif (result_array)
     */
    public function get_all($search_number = null, $status_filter = null)
    {
        if ($search_number) {
            $this->db->like('letter_number', $search_number);
        }
        if ($status_filter) {
            $this->db->where('status', $status_filter);
        }
        return $this->db->get('incoming_letters')->result_array();
    }

    /**
     * Ambil 1 surat berdasarkan id, dalam bentuk ARRAY asosiatif.
     * Dipakai oleh Incoming_letters::detail() untuk mengisi bagian
     * "Detail Surat" di halaman detail (view mengaksesnya sebagai $surat['...']).
     *
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        return $this->db->get_where('incoming_letters', array('id' => $id))->row_array();
    }

    /**
     * [BARU] Ambil 1 surat berdasarkan id, dalam bentuk OBJECT.
     * Method terpisah dari get_by_id() karena DispositionsController::save()
     * butuh bentuk object ($letter->status), bukan array.
     *
     * @param int $id
     * @return object|null
     */
    public function getById($id)
    {
        return $this->db->get_where('incoming_letters', array('id' => $id))->row();
    }

    /**
     * [BARU] Daftar surat untuk dropdown "Surat Masuk" pada form disposisi
     * (dipanggil dari DispositionsController::getSelectSurat()).
     * Hanya mengambil kolom yang dibutuhkan (id, letter_number, subject)
     * supaya query ringan, diurutkan dari surat terbaru.
     *
     * @return array array of object {id, letter_number, subject}
     */
    public function getSelectOptions()
    {
        $this->db->select('id, letter_number, subject');
        $this->db->order_by('letter_date', 'DESC');
        return $this->db->get('incoming_letters')->result();
    }

    /**
     * Menyimpan surat baru. Tidak berubah dari versi asli.
     * @param array $data
     * @return bool
     */
    public function insert($data)
    {
        return $this->db->insert('incoming_letters', $data);
    }

    /**
     * Update data surat (dipakai untuk edit detail surat, jika ada fiturnya
     * di halaman lain). Kondisi where disesuaikan ke kolom "id".
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('incoming_letters', $data);
    }

    /**
     * Update kolom status saja. Dipanggil DispositionsController (save & update)
     * untuk menaikkan status surat mengikuti business rule poin 7
     * (Received -> Processing saat ada disposisi baru,
     *  -> Completed saat semua disposisi surat tsb sudah Completed).
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function update_status($id, $status)
    {
        $this->db->where('id', $id);
        return $this->db->update('incoming_letters', array('status' => $status));
    }

    /**
     * [BARU] Menghapus 1 surat berdasarkan id.
     * Dipanggil oleh Incoming_letters::delete() SETELAH seluruh disposisi
     * terkait surat ini sudah dihapus terlebih dahulu (lihat controller),
     * supaya tidak menyisakan data disposisi "yatim" yang menunjuk ke
     * letter_id yang sudah tidak ada.
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete('incoming_letters');
    }
}