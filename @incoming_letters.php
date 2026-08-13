<!--
    View: Daftar Surat Masuk (halaman list, tab "Master Surat Masuk")
    File ini HARUS bernama: application/views/pengelolaan/@incoming_letters.php
    (prefix "@" wajib, mengikuti pola view disposisi: pengelolaan/@dispositions.php)
    Dimuat lewat $this->template->load('main_template', 'pengelolaan/@incoming_letters', $data)
    di Incoming_lettersController::index()

    [FIX PENTING - LAYOUT/SIDEBAR RUSAK]
    Versi sebelumnya menulis ulang <!DOCTYPE html>, <html>, <head>, <body>
    serta me-load ulang Bootstrap CSS/JS & jQuery dari CDN. Karena file ini
    dimuat lewat $this->template->load(...) ke dalam main_template yang
    SUDAH punya <html>/<head>/<body>/sidebar/navbar/Bootstrap/jQuery sendiri,
    hasilnya jadi nested <html> di dalam <html> dan Bootstrap/jQuery ke-load
    dua kali. Itu yang bikin tombol toggle sidebar tidak berfungsi dan
    sidebar menutupi konten. Sekarang file ini HANYA berisi fragment konten
    (tanpa doctype/head/body, tanpa CDN link, tanpa <script src> jQuery/Bootstrap).
    Jika butuh jQuery/Bootstrap, pastikan sudah tersedia global dari main_template.

    PENYESUAIAN dengan modul Disposisi:
    - Semua referensi ke $l['id_incoming_letters'] diganti jadi $l['id'],
      karena Incoming_letter_model sekarang memakai PK "id".
    - Data $letters, $search_number, $selected_status dikirim oleh
      Incoming_lettersController::index().
    - Semua base_url('incoming_letters/...') diubah jadi
      base_url('pengelolaan/Incoming_lettersController/...').
-->

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Master Surat Masuk</h3>
        <div>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <!-- Membuka modal tambah surat (lihat fungsi openModalAdd() di bawah) -->
            <button class="btn btn-primary btn-sm" onclick="openModalAdd()"><i class="fas fa-plus"></i> Tambah Surat</button>
        </div>
    </div>

    <!-- ==================== Filter & Search ====================
         Form GET biasa (bukan AJAX): submit akan reload halaman dengan
         query string ?search_number=...&status=..., ditangani oleh
         Incoming_letters::index(). -->
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <form method="GET" action="<?= base_url('pengelolaan/Incoming_lettersController') ?>" class="form-inline">
                <input type="text" name="search_number" class="form-control mr-2" placeholder="Cari No. Surat..." value="<?= html_escape($search_number) ?>">
                <select name="status" class="form-control mr-2">
                    <option value="">-- Semua Status --</option>
                    <option value="Received" <?= $selected_status === 'Received' ? 'selected' : '' ?>>Received</option>
                    <option value="Processing" <?= $selected_status === 'Processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="Completed" <?= $selected_status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Archived" <?= $selected_status === 'Archived' ? 'selected' : '' ?>>Archived</option>
                </select>
                <button type="submit" class="btn btn-info"><i class="fas fa-search"></i> Cari</button>
                <a href="<?= base_url('pengelolaan/Incoming_lettersController') ?>" class="btn btn-light ml-2">Reset</a>
            </form>
        </div>
    </div>

    <!-- ==================== Tabel Daftar Surat ==================== -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>No</th>
                            <th>No. Surat</th>
                            <th>Tgl Surat</th>
                            <th>Tgl Diterima</th>
                            <th>Pengirim</th>
                            <th>Perihal</th>
                            <th>Status</th>
                            <th width="160">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($letters)): $no = 1; foreach ($letters as $l): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= html_escape($l['letter_number']) ?></strong></td>
                            <td><?= $l['letter_date'] ?></td>
                            <td><?= $l['received_date'] ?></td>
                            <td><?= html_escape($l['sender']) ?></td>
                            <td><?= html_escape($l['subject']) ?></td>
                            <td>
                                <span class="badge badge-<?= $l['status'] == 'Completed' ? 'success' : ($l['status'] == 'Processing' ? 'warning' : 'info') ?>">
                                    <?= $l['status'] ?>
                                </span>
                            </td>
                            <td>
                                <!-- [DIUBAH] id_incoming_letters -> id, mengikuti PK baru -->
                                <a href="<?= base_url('pengelolaan/Incoming_lettersController/detail/' . $l['id']) ?>" class="btn btn-info btn-sm" title="Detail & Disposisi"><i class="fas fa-eye"></i></a>
                                <button class="btn btn-danger btn-sm" onclick="deleteLetter(<?= $l['id'] ?>)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="8" class="text-center">Surat tidak ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==================== Modal Form Tambah Surat ==================== -->
<div class="modal fade" id="modalLetter" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formLetter">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Surat Masuk</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Nomor Surat</label>
                            <input type="text" name="letter_number" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Pengirim</label>
                            <input type="text" name="sender" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Tanggal Surat</label>
                            <input type="date" name="letter_date" id="letter_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Tanggal Diterima</label>
                            <input type="date" name="received_date" id="received_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Perihal</label>
                        <input type="text" name="subject" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan / Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!--
    [FIX] Tidak ada lagi <script src="...jquery..."> / <script src="...bootstrap...">
    di sini. jQuery & Bootstrap JS dianggap SUDAH tersedia secara global dari
    main_template (karena sidebar toggle & modal Bootstrap di main_template
    juga butuh itu). Kalau ternyata main_template BELUM meng-include jQuery/
    Bootstrap JS, itu harus ditambahkan SEKALI SAJA di main_template, bukan
    di tiap-tiap view seperti ini.
-->
<script>
// Fungsi Buka Modal Tambah Surat
function openModalAdd() {
    $('#formLetter')[0].reset();
    $('#modalLetter').modal('show');
}

// Tangani Submit Form
$(document).ready(function() {
    $('#formLetter').on('submit', function(e) {
        e.preventDefault(); // Mencegah reload halaman
        
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: '<?= base_url("pengelolaan/Incoming_lettersController/store") ?>',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                submitBtn.prop('disabled', false).text('Simpan');
                
                if (res.status === 'success') {
                    alert(res.message || 'Data berhasil disimpan!');
                    $('#modalLetter').modal('hide');
                    location.reload();
                } else {
                    var err = res.errors ? Object.values(res.errors).join("\n") : (res.message || 'Terjadi kesalahan.');
                    alert('Gagal menyimpan:\n' + err);
                }
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false).text('Simpan');
                console.error("XHR Response:", xhr.responseText);
                alert('Terjadi kesalahan pada server (Error status: ' + xhr.status + '). Cek Console/Network F12.');
            }
        });
    });
});

// Fungsi Hapus Surat
function deleteLetter(id) {
    if (confirm('Hapus surat ini beserta riwayat disposisinya?')) {
        $.getJSON('<?= base_url("pengelolaan/Incoming_lettersController/delete/") ?>' + id, function(res) {
            alert(res.message);
            if (res.status === 'success') location.reload();
        });
    }
}
</script>