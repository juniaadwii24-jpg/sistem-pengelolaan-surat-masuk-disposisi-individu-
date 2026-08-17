<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller untuk modul "Master Penerima Disposisi" (tabel recipients).
 *
 * PERBAIKAN dari versi sebelumnya (class "Recipients"):
 * - View recipients sekarang dibangun pakai Knockout + DataTables server-side
 *   (sama seperti pola DataPelanggan / Dispositions), dan memanggil endpoint:
 *     RecipientsController/getData
 *     RecipientsController/getDataSelect
 *     RecipientsController/save
 *     RecipientsController/update
 *     RecipientsController/delete
 *   Controller versi lama class-nya "Recipients" (bukan "RecipientsController")
 *   dan method-nya get_by_id($id)/store()/update($id)/delete($id) - TIDAK ADA
 *   yang cocok dengan endpoint di atas, jadi semua AJAX dari view 404 dan
 *   tabel/module kelihatan tidak muncul/kosong. Class di sini diganti jadi
 *   "RecipientsController" dengan method getData/getDataSelect/save/update/
 *   delete, mengikuti pola yang sama dengan DispositionsController.
 * - $this->load->model('Recipients_model', 'model') di versi lama memanggil
 *   file model yang SUDAH TIDAK ADA (sudah diganti jadi Recipient_model,
 *   singular, di application/models/pengelolaan/Recipient_model.php, untuk
 *   kompatibel dengan Incoming_lettersController). Diganti jadi:
 *     $this->load->model('pengelolaan/Recipient_model', 'model')
 * - Render halaman disamakan dengan modul lain (Incoming_lettersController,
 *   DispositionsController) yaitu lewat $this->template->load(), bukan
 *   $this->load->view() langsung, supaya module ini muncul di dalam layout
 *   admin panel yang sama (sidebar, header, dst), bukan halaman polos.
 * - Body request dibaca lewat php://input (JSON), karena view Knockout
 *   mengirim data via JSON.stringify() + contentType application/json,
 *   BUKAN $(this).serialize() seperti view versi lama.
 */
class RecipientsController extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pengelolaan/Recipient_model', 'model');
        $this->load->database();
        $this->load->library('template');
    }

    /**
     * Halaman utama Master Penerima Disposisi.
     * Data ditampilkan lewat DataTables AJAX (getData()), bukan dikirim
     * langsung dari sini, jadi cukup render view-nya saja.
     */
    public function index()
    {
        $data['title'] = 'Master Penerima Disposisi';
        $this->template->load('main_template', 'pengelolaan/@recipients', $data);
    }

    /**
     * Endpoint untuk mengisi tabel di tab "Master Penerima".
     * Dipanggil DataTables server-side (POST).
     */
    function getData()
    {
        $data = [
            'start'       => $this->input->post('start'),
            'length'      => $this->input->post('length'),
            'filtervalue' => $this->input->post('filtervalue'),
            'filtertext'  => $this->input->post('filtertext'),
        ];
        $res = $this->model->getDataAll($data);
        echo json_encode($res);
    }

    /**
     * Endpoint untuk ambil 1 data penerima berdasarkan id (mode edit).
     * Dipanggil recipient.selectdata() di view.
     */
    function getDataSelect()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $res = $this->model->getDataId($data['id']);
        echo json_encode($res);
    }

    /**
     * Endpoint untuk menyimpan penerima BARU.
     */
    function save()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $error = $this->_validate($data);
        if ($error) {
            echo json_encode(['result' => false, 'message' => $error]);
            return;
        }

        $insertId = $this->model->insert($data);
        echo json_encode(['result' => (bool) $insertId]);
    }

    

    /**
     * Endpoint untuk mengupdate data penerima yang sudah ada.
     */
    function update()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $error = $this->_validate($data);
        if ($error) {
            echo json_encode(['result' => false, 'message' => $error]);
            return;
        }

        $updated = $this->model->update($data['id'], $data);
        echo json_encode(['result' => (bool) $updated]);
    }

    /**
     * Endpoint untuk menghapus 1 data penerima berdasarkan id.
     * Menolak hapus jika penerima masih dipakai di tabel dispositions
     * (constraint fk_disposition_recipient ... ON DELETE RESTRICT).
     */
    function delete()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];

        if ($this->model->isUsedInDisposition($id)) {
            echo json_encode([
                'result'  => false,
                'message' => 'Penerima ini masih memiliki riwayat disposisi dan tidak bisa dihapus.',
            ]);
            return;
        }

        $deleted = $this->model->delete($id);
        echo json_encode([
            'result'  => (bool) $deleted,
            'message' => $deleted ? 'Data berhasil dihapus.' : 'Gagal menghapus data.',
        ]);
    }

    /**
     * Validasi input dasar sebelum insert/update.
     * @param array $data
     * @return string|null pesan error, atau null jika lolos
     */
    private function _validate($data)
    {
        if (empty($data['name'])) {
            return 'Nama wajib diisi.';
        }
        if (empty($data['position'])) {
            return 'Jabatan wajib diisi.';
        }
        if (empty($data['department'])) {
            return 'Departemen wajib diisi.';
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Email tidak valid.';
        }
        return null;
    }
}