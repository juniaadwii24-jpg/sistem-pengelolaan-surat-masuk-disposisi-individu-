<!--
    View: Detail Surat & Riwayat Disposisi
    File ini HARUS bernama: application/views/pengelolaan/@incoming_lettersdetail.php
    (prefix "@" wajib). Dimuat lewat
    $this->template->load('main_template', 'pengelolaan/@incoming_lettersdetail', $data)
    di Incoming_lettersController::detail() — BUKAN $this->load->view() biasa.

    [FIX PENTING - LAYOUT/SIDEBAR RUSAK]
    Sama seperti @incoming_letters.php: versi sebelumnya menulis ulang
    <!DOCTYPE html>, <html>, <head>, <body> dan me-load ulang Bootstrap CSS/JS
    & jQuery dari CDN, padahal main_template SUDAH menyediakan semua itu
    (termasuk sidebar/navbar). Nested <html> + double jQuery/Bootstrap JS
    inilah penyebab tombol toggle sidebar tidak berfungsi dan sidebar
    menutupi konten tabel. Sekarang file ini hanya berisi fragment konten.

    PENYESUAIAN dengan modul Disposisi (perubahan utama dibanding versi awal):
    1. Semua id (surat, penerima, disposisi) memakai kolom "id" (bukan lagi
       id_incoming_letters / id_recipients / id_dispositions).
    2. $dispositions berupa ARRAY OF OBJECT (hasil Disposition_model::
       getByLetterId() yang memakai ->result()), diakses dengan "->".
    3. AJAX "Buat Disposisi Baru" & "Ubah Status" diarahkan ke endpoint yang
       sudah dibuat di DispositionsController:
         - simpan disposisi baru  -> pengelolaan/DispositionsController/save
         - ubah status saja       -> pengelolaan/DispositionsController/updateStatusOnly
    4. DispositionsController membaca body request sebagai JSON, jadi kedua
       AJAX call di bawah pakai JSON.stringify + contentType 'application/json'.
    5. Field "status" ditambahkan sebagai hidden input default "Pending" pada
       form tambah disposisi.
    6. Link "Kembali ke Daftar Surat" diarahkan ke
       base_url('pengelolaan/Incoming_lettersController').
-->

<div class="container mt-4 mb-5">
    <a href="<?= base_url('pengelolaan/Incoming_lettersController') ?>" class="btn btn-secondary btn-sm mb-3"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Surat</a>

    <!-- ==================== Detail Surat ==================== -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Detail Surat Masuk: <?= html_escape($surat['letter_number']) ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Pengirim:</strong> <?= html_escape($surat['sender']) ?></p>
                    <p><strong>Perihal:</strong> <?= html_escape($surat['subject']) ?></p>
                    <p><strong>Keterangan:</strong> <?= html_escape($surat['description'] ?: '-') ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Tanggal Surat:</strong> <?= $surat['letter_date'] ?></p>
                    <p><strong>Tanggal Diterima:</strong> <?= $surat['received_date'] ?></p>
                    <p><strong>Status Surat:</strong>
                        <span class="badge badge-<?= $surat['status'] == 'Completed' ? 'success' : ($surat['status'] == 'Processing' ? 'warning' : 'info') ?>">
                            <?= $surat['status'] ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- ==================== Form Disposisi Baru ==================== -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Buat Disposisi Baru</h5>
                </div>
                <div class="card-body">
                    <form id="formAddDisposition">
                        <!-- [DIUBAH] id_incoming_letters -> id -->
                        <input type="hidden" name="letter_id" value="<?= $surat['id'] ?>">
                        <!-- [BARU] status wajib diisi menurut validasi DispositionsController::_validate().
                             Disposisi baru selalu dimulai dari status "Pending". -->
                        <input type="hidden" name="status" value="Pending">

                        <div class="form-group">
                            <label>Pilih Penerima</label>
                            <select name="recipient_id" class="form-control" required>
                                <option value="">-- Pilih Penerima --</option>
                                <?php foreach ($recipients as $r): ?>
                                    <!-- [DIUBAH] id_recipients -> id -->
                                    <option value="<?= $r['id'] ?>"><?= html_escape($r['name']) ?> (<?= html_escape($r['position']) ?> - <?= html_escape($r['department']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tanggal Disposisi</label>
                            <input type="date" name="disposition_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Instruksi</label>
                            <textarea name="instruction" class="form-control" rows="3" placeholder="Instruksi disposisi..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Catatan Opsional</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success btn-block"><i class="fas fa-paper-plane"></i> Simpan Disposisi</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ==================== Riwayat Disposisi ==================== -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Riwayat Disposisi Surat</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Penerima</th>
                                    <th>Instruksi</th>
                                    <th>Tgl</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- [DIUBAH] $dispositions kini array of OBJECT (bukan array asosiatif),
                                     karena berasal dari Disposition_model::getByLetterId() yang
                                     memakai ->result(). Semua akses memakai tanda panah (->) -->
                                <?php if (!empty($dispositions)): foreach ($dispositions as $d): ?>
                                <tr>
                                    <td>
                                        <strong><?= html_escape($d->recipient_name) ?></strong><br>
                                        <small class="text-muted"><?= html_escape($d->department) ?></small>
                                    </td>
                                    <td>
                                        <?= html_escape($d->instruction) ?>
                                        <?php if ($d->notes): ?><br><small class="text-info">Note: <?= html_escape($d->notes) ?></small><?php endif; ?>
                                    </td>
                                    <td><?= $d->disposition_date ?></td>
                                    <td>
                                        <!-- [DIUBAH] id_dispositions -> id -->
                                        <select class="form-control form-control-sm select-status" data-id="<?= $d->id ?>">
                                            <option value="Pending" <?= $d->status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="In Progress" <?= $d->status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                            <option value="Completed" <?= $d->status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                        </select>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="4" class="text-center text-muted">Belum ada riwayat disposisi untuk surat ini.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!--
    [FIX] Tidak ada lagi <script src="...jquery..."> di sini. jQuery
    dianggap sudah tersedia global dari main_template. Jangan load ulang
    di tiap view — cukup sekali saja di main_template.
-->
<script>
// ==================== Submit Disposisi Baru (AJAX, format JSON) ====================
// Kirim ke endpoint DispositionsController::save yang sudah ada, dengan body
// JSON, karena controller membaca lewat json_decode(file_get_contents('php://input')).
$('#formAddDisposition').on('submit', function(e) {
    e.preventDefault();

    // Ubah data form (array of {name, value}) menjadi 1 object polos
    // supaya bisa di-JSON.stringify sesuai format yang diharapkan server.
    var formArray = $(this).serializeArray();
    var payload = {};
    formArray.forEach(function (field) {
        payload[field.name] = field.value;
    });

    $.ajax({
        url: '<?= base_url("pengelolaan/DispositionsController/save") ?>',
        type: 'POST',
        data: JSON.stringify(payload),
        contentType: 'application/json',
        dataType: 'json',
        success: function(res) {
            if (res.result) {
                alert('Disposisi berhasil disimpan.');
                location.reload();
            } else {
                alert('Gagal menyimpan disposisi: ' + (res.message || 'Terjadi kesalahan.'));
            }
        }
    });
});

// ==================== Ubah Status Disposisi (AJAX, format JSON) ====================
// Kirim ke DispositionsController::updateStatusOnly yang hanya butuh {id, status} —
// letter_id tidak perlu dikirim karena controller itu sendiri yang mencari
// letter_id terkait lewat Disposition_model::getDataId().
$('.select-status').on('change', function() {
    var dispId = $(this).data('id');
    var status = $(this).val();

    $.ajax({
        url: '<?= base_url("pengelolaan/DispositionsController/updateStatusOnly") ?>',
        type: 'POST',
        data: JSON.stringify({ id: dispId, status: status }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(res) {
            if (res.result) {
                alert('Status disposisi berhasil diperbarui.');
                location.reload();
            } else {
                alert('Gagal memperbarui status.');
            }
        }
    });
});
</script>