<?php
include 'koneksi.php';

// ======== CEK INPUT ========
if (!isset($_GET['id_guru']) || empty($_GET['id_guru'])) {
  // Respons yang lebih user-friendly
  echo '<div class="container-fluid mt-5"><div class="alert alert-danger">ID Guru tidak valid! Kembali ke halaman Guru.</div><a href="guru.php" class="btn btn-primary">Kembali</a></div>';
  include 'footer.php';
  exit;
}

$id_guru = intval($_GET['id_guru']);

// Ambil info guru
$guru = $conn->query("SELECT * FROM guru WHERE id_guru=$id_guru")->fetch_assoc();
if (!$guru) {
  echo '<div class="container-fluid mt-5"><div class="alert alert-danger">Guru tidak ditemukan!</div><a href="guru.php" class="btn btn-primary">Kembali</a></div>';
  include 'footer.php';
  exit;
}

// ================== PROSES SIMPAN ==================
if (isset($_POST['simpan'])) {
  // Hapus data unavailable lama
  $conn->query("DELETE FROM guru_unavailable WHERE id_guru=$id_guru");

  // Menggunakan array untuk menghindari query berulang
  $hariTerpilih = $_POST['hari'] ?? [];

  if (!empty($hariTerpilih)) {

    // Siapkan prepared statement untuk insert
    $stmt_insert = $conn->prepare("
        INSERT INTO guru_unavailable (id_guru, hari, full_day, jam_mulai, jam_selesai)
        VALUES (?, ?, ?, ?, ?)
    ");

    // Siapkan prepared statement untuk delete roster full day
    $stmt_del_full = $conn->prepare("DELETE FROM roster WHERE id_guru = ? AND hari = ?");

    // Siapkan prepared statement untuk delete roster partial
    $stmt_del_partial = $conn->prepare("
        DELETE FROM roster
        WHERE id_guru = ?
          AND hari = ?
          AND NOT (jam_selesai <= ? OR jam_mulai >= ?)
    ");

    foreach ($hariTerpilih as $hari) {

      $is_full = isset($_POST['full_day'][$hari]) ? 1 : 0;
      $mulai = $_POST['jam_mulai'][$hari] ?? NULL;
      $selesai = $_POST['jam_selesai'][$hari] ?? NULL;

      // Logika Insert Unavailable
      if ($is_full || empty($mulai) || empty($selesai)) {
        // FULL DAY atau jam tidak lengkap → simpan sebagai Full Day (1)
        $stmt_insert->bind_param("issss", $id_guru, $hari, $is_full, $mulai, $selesai); // Tetap bind jam, DB akan handle NULL
        $stmt_insert->execute();

        // Logika Hapus Roster FULL DAY
        $stmt_del_full->bind_param("is", $id_guru, $hari);
        $stmt_del_full->execute();
      } else {
        // PARTIAL DAY
        $stmt_insert->bind_param("issss", $id_guru, $hari, $is_full, $mulai, $selesai);
        $stmt_insert->execute();

        // Logika Hapus Roster PARTIAL DAY (Bentrok)
        $stmt_del_partial->bind_param("isss", $id_guru, $hari, $mulai, $selesai);
        $stmt_del_partial->execute();
      }
    }

    // Tutup statements
    $stmt_insert->close();
    $stmt_del_full->close();
    $stmt_del_partial->close();
  } else {
    // Jika tidak ada hari yang terpilih, seluruh data unavailable sudah dihapus di awal.
  }

  header("Location: guru.php?status=unavailable_saved"); // Redirect ke guru.php dengan status
  exit;
}

// ================== LOAD DATA ==================
$unavailable = [];
$res = $conn->query("SELECT * FROM guru_unavailable WHERE id_guru=$id_guru");
while ($row = $res->fetch_assoc()) {
  $unavailable[$row['hari']] = $row;
}

$hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

// ======== INCLUDE LAYOUT ========
$pageTitle = "Atur Ketidaktersediaan Guru";
$pageLocation = "Guru";
include 'layout.php';
?>

<style>
  /* Styling Card Hari */
  .day-card {
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #e0e0e0;
  }

  .day-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
  }

  .card-header-toggle {
    cursor: pointer;
    background-color: #f7f7f7;
    border-radius: 12px 12px 0 0 !important;
    padding: 12px 20px;
    border-bottom: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  /* Styling Status Visual */
  .status-indicator {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: bold;
    color: white;
    transition: background-color 0.3s;
  }

  .status-available {
    background-color: #28a745;
    /* Green */
  }

  .status-unavailable {
    background-color: #ffc107;
    /* Yellow/Warning */
  }

  .status-full {
    background-color: #dc3545;
    /* Red/Danger */
  }

  .card-body {
    padding-top: 5px;
  }

  /* Menyembunyikan checkbox utama agar klik header yang berfungsi */
  .hidden-checkbox {
    display: none;
  }

  /* Transisi untuk bagian jam */
  .jamSection {
    transition: all 0.3s ease-in-out;
  }
</style>

<div class="container-fluid">

  <h3 class="mt-3"><i class="fas fa-calendar-times me-2 text-danger"></i>Atur Ketidaktersediaan: <span class="fw-bold"><?= htmlspecialchars($guru['nama']) ?></span></h3>
  <p class="text-muted">Tentukan kapan guru tidak dapat mengajar. Setiap perubahan akan menghapus roster yang bentrok.</p>
  <hr>

  <form method="post" class="row">

    <?php foreach ($hariList as $hari):
      $data = $unavailable[$hari] ?? null;
      $is_unavailable = $data ? true : false;
      $is_full_day = $data && $data['full_day'];

      // Tentukan class status
      $status_class = $is_full_day ? 'status-full' : ($is_unavailable ? 'status-unavailable' : 'status-available');
      $status_text = $is_full_day ? 'FULL DAY' : ($is_unavailable ? 'PARTIAL' : 'TERSEDIA');

      // Tentukan class card
      $card_class = $is_unavailable ? 'border-warning' : 'border-light';
    ?>

      <div class="col-md-6 col-lg-4 mb-4">
        <div class="card day-card <?= $card_class ?>" data-day="<?= $hari ?>">

          <div class="card-header-toggle" data-bs-toggle="collapse" data-bs-target="#collapse<?= $hari ?>">
            <div class="d-flex align-items-center">
              <input type="checkbox" name="hari[]" value="<?= $hari ?>"
                class="hidden-checkbox day-checkbox"
                id="chk<?= $hari ?>" <?= $is_unavailable ? "checked" : "" ?>>

              <i class="fas fa-calendar-day me-2 text-primary"></i>
              <strong class="h5 mb-0 me-3"><?= $hari ?></strong>
            </div>
            <span class="status-indicator <?= $status_class ?> statusText"><?= $status_text ?></span>
          </div>

          <div id="collapse<?= $hari ?>" class="collapse <?= $is_unavailable ? 'show' : '' ?>">
            <div class="card-body">

              <div class="form-check form-switch mb-3">
                <input class="form-check-input fullDayCheck" type="checkbox" role="switch"
                  name="full_day[<?= $hari ?>]" id="fullDaySwitch<?= $hari ?>"
                  data-day-target="<?= $hari ?>"
                  <?= $is_full_day ? "checked" : "" ?>>
                <label class="form-check-label fw-bold" for="fullDaySwitch<?= $hari ?>">
                  <i class="fas fa-ban me-1 text-danger"></i> Tidak Tersedia Sepanjang Hari (Full Day)
                </label>
              </div>

              <div class="row g-2 jamSection" id="jamSection<?= $hari ?>"
                style="<?= ($is_full_day ? 'display:none;' : '') ?>">

                <div class="col-md-6">
                  <label class="form-label small">Jam Mulai</label>
                  <input type="time" class="form-control"
                    name="jam_mulai[<?= $hari ?>]"
                    value="<?= $data && !$data['full_day'] ? htmlspecialchars($data['jam_mulai']) : '07:30' ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Jam Selesai</label>
                  <input type="time" class="form-control"
                    name="jam_selesai[<?= $hari ?>]"
                    value="<?= $data && !$data['full_day'] ? htmlspecialchars($data['jam_selesai']) : '15:30' ?>" required>
                </div>
                <div class="col-12 mt-3">
                  <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Jadwal yang bentrok dalam rentang jam ini akan dihapus.</small>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

    <?php endforeach; ?>

    <div class="col-12 mt-3">
      <button type="submit" class="btn btn-primary btn-lg" name="simpan">
        <i class="fas fa-save me-2"></i> Simpan Pengaturan Ketidaktersediaan
      </button>
      <a href="guru.php" class="btn btn-secondary btn-lg">
        <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Guru
      </a>
    </div>
  </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {

    // --- LOGIKA UTAMA: FULL DAY CHECKBOX ---
    document.querySelectorAll('.fullDayCheck').forEach(chk => {
      chk.addEventListener('change', function() {
        const hari = this.dataset.dayTarget;
        const jamSection = document.getElementById(`jamSection${hari}`);
        const statusText = this.closest('.day-card').querySelector('.statusText');

        if (this.checked) {
          jamSection.style.display = 'none';
          statusText.textContent = 'FULL DAY';
          statusText.className = 'status-indicator status-full statusText';
        } else {
          jamSection.style.display = 'flex';
          statusText.textContent = 'PARTIAL';
          statusText.className = 'status-indicator status-unavailable statusText';
        }

        document.getElementById(`chk${hari}`).checked = true;
      });
    });

    // --- LOGIKA KARTU: TOGGLE KETERSEDIAAN HARI ---
    document.querySelectorAll('.card-header-toggle').forEach(header => {
      header.addEventListener('click', function(e) {
        if (e.target.closest('.form-check')) return;

        const card = this.closest('.card');
        const checkbox = card.querySelector('.day-checkbox');
        const statusText = card.querySelector('.statusText');
        const jamSection = card.querySelector('.jamSection');
        const fullDayCheck = card.querySelector('.fullDayCheck');

        // Dapatkan elemen collapse
        const collapseEl = document.getElementById(this.dataset.bsTarget.substring(1));
        // Buat atau dapatkan instance Collapse
        const collapse = new bootstrap.Collapse(collapseEl, {
          toggle: false
        });

        // Toggle checkbox Hari secara manual
        const isChecked = !checkbox.checked;
        checkbox.checked = isChecked;

        if (isChecked) {
          // Jika diaktifkan (Unavailable)
          const isFull = fullDayCheck.checked;

          if (isFull) {
            statusText.textContent = 'FULL DAY';
            statusText.className = 'status-indicator status-full statusText';
            jamSection.style.display = 'none';
          } else {
            statusText.textContent = 'PARTIAL';
            statusText.className = 'status-indicator status-unavailable statusText';
            jamSection.style.display = 'flex';
          }

          // --- PERBAIKAN: BUKA COLLAPSE ---
          collapse.show();
          card.classList.add('border-warning');

        } else {
          // Jika dinonaktifkan (Tersedia)
          statusText.textContent = 'TERSEDIA';
          statusText.className = 'status-indicator status-available statusText';
          jamSection.style.display = 'none';
          card.classList.remove('border-warning');

          // --- PERBAIKAN: TUTUP COLLAPSE ---
          collapse.hide();
        }
      });
    });

    // --- Logika untuk memastikan jam section tersembunyi/terlihat saat load ---
    document.querySelectorAll('.day-card').forEach(card => {
      const dayCheckbox = card.querySelector('.day-checkbox');
      const fullDayCheck = card.querySelector('.fullDayCheck');
      const jamSection = card.querySelector('.jamSection');

      if (!dayCheckbox.checked || fullDayCheck.checked) {
        jamSection.style.display = 'none';
      } else {
        jamSection.style.display = 'flex';
      }
    });

  });
</script>