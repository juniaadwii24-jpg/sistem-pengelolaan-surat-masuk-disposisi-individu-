<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller untuk modul "Master Surat Masuk".
 */
class Incoming_lettersController extends CI_Controller
{
   public function __construct()
    {
        parent::__construct();

        // Load Model
        $this->load->model('pengelolaan/Incoming_letter_model');
        $this->load->model('pengelolaan/Recipient_model');
        $this->load->model('pengelolaan/Disposition_model', 'model');

        // Load Library (PENTING: tambahkan form_validation di sini)
        $this->load->library('template');
        $this->load->library('form_validation');
    }

    /**
     * Halaman daftar surat masuk (tab "Master Surat Masuk").
     */
    public function index()
    {
        $search_number   = $this->input->get('search_number');
        $selected_status = $this->input->get('status');

        $data['letters']         = $this->Incoming_letter_model->get_all($search_number, $selected_status);
        $data['search_number']   = $search_number ? $search_number : '';
        $data['selected_status'] = $selected_status ? $selected_status : '';
        $data['title']           = 'Master Surat Masuk';

        $this->template->load('main_template', 'pengelolaan/@incoming_letters', $data);
    }

    /**
     * Menyimpan surat masuk baru (dipanggil AJAX dari modal "Tambah Surat").
     */
    public function store()
    {
        // Validasi Form
        $this->form_validation->set_rules('letter_number', 'Nomor Surat', 'required|is_unique[incoming_letters.letter_number]');
        $this->form_validation->set_rules('letter_date', 'Tanggal Surat', 'required');
        $this->form_validation->set_rules('received_date', 'Tanggal Diterima', 'required|callback_check_dates');
        $this->form_validation->set_rules('sender', 'Pengirim', 'required');
        $this->form_validation->set_rules('subject', 'Perihal', 'required');

        if ($this->form_validation->run() == FALSE) {
            echo json_encode(['status' => 'error', 'errors' => $this->form_validation->error_array()]);
        } else {
            // Konversi tanggal ke format YYYY-MM-DD standar MySQL
            $letter_date   = date('Y-m-d', strtotime($this->input->post('letter_date')));
            $received_date = date('Y-m-d', strtotime($this->input->post('received_date')));

            $data = [
                'letter_number' => $this->input->post('letter_number'),
                'letter_date'   => $letter_date,
                'received_date' => $received_date,
                'sender'        => $this->input->post('sender'),
                'subject'       => $this->input->post('subject'),
                'description'   => $this->input->post('description'),
                'status'        => 'Received'
            ];
            
            $this->Incoming_letter_model->insert($data);
            echo json_encode(['status' => 'success', 'message' => 'Data surat masuk berhasil disimpan.']);
        }
    }

    /**
     * Custom Callback Validasi Tanggal Diterima >= Tanggal Surat.
     */
    public function check_dates()
    {
        $letter_date   = $this->input->post('letter_date');
        $received_date = $this->input->post('received_date');

        if ($letter_date && $received_date) {
            $tgl_surat    = DateTime::createFromFormat('Y-m-d', $letter_date) ?: DateTime::createFromFormat('d-m-Y', $letter_date);
            $tgl_diterima = DateTime::createFromFormat('Y-m-d', $received_date) ?: DateTime::createFromFormat('d-m-Y', $received_date);

            if ($tgl_surat && $tgl_diterima) {
                if ($tgl_diterima < $tgl_surat) {
                    $this->form_validation->set_message('check_dates', 'Tanggal diterima tidak boleh lebih awal dari tanggal surat.');
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    /**
     * Halaman Detail Surat + Riwayat Disposisi + Form buat disposisi baru.
     */
    public function detail($id)
    {
        $data['surat']        = $this->Incoming_letter_model->get_by_id($id);
        $data['recipients']   = $this->Recipient_model->get_all();
        $data['dispositions'] = $this->model->getByLetterId($id);
        $data['title']        = 'Detail Surat & Disposisi';

        $this->template->load('main_template', 'pengelolaan/@incoming_lettersdetail', $data);
    }

    /**
     * Menghapus surat masuk beserta seluruh riwayat disposisinya.
     */
    public function delete($id)
    {
        $relatedDispositions = $this->model->getByLetterId($id);
        foreach ($relatedDispositions as $disposition) {
            $this->model->deleteData($disposition->id);
        }

        $success = $this->Incoming_letter_model->delete($id);

        echo json_encode([
            'status'  => $success ? 'success' : 'error',
            'message' => $success
                ? 'Surat beserta riwayat disposisinya berhasil dihapus.'
                : 'Gagal menghapus surat.',
        ]);
    }
}