<?php
include 'koneksi.php';

// ======== SETUP LAYOUT ========
$pageTitle = "Atur Ketidaktersediaan Guru";
$pageLocation = "Guru";
include 'layout.php';

// ======== CEK INPUT ========
if (!isset($_GET['id_guru']) || empty($_GET['id_guru'])) {
  die("ID Guru tidak valid!");
}

$id_guru = intval($_GET['id_guru']);

// Ambil info guru
$guru = $conn->query("SELECT * FROM guru WHERE id_guru=$id_guru")->fetch_assoc();
if (!$guru) die("Guru tidak ditemukan!");

// ================== PROSES SIMPAN ==================
if (isset($_POST['simpan'])) {

  // Hapus data unavailable lama
  $conn->query("DELETE FROM guru_unavailable WHERE id_guru=$id_guru");

  if (!empty($_POST['hari'])) {

    foreach ($_POST['hari'] as $hari) {

      $is_full = isset($_POST['full_day'][$hari]) ? 1 : 0;

      // Jika FULL DAY → jam NULL
      if ($is_full) {
        $conn->query("
          INSERT INTO guru_unavailable (id_guru, hari, full_day, jam_mulai, jam_selesai)
          VALUES ($id_guru, '$hari', 1, NULL, NULL)
        ");
      } else {

        $mulai = $_POST['jam_mulai'][$hari] ?? NULL;
        $selesai = $_POST['jam_selesai'][$hari] ?? NULL;

        // Jika jam kosong → tetap dianggap full day
        if (empty($mulai) || empty($selesai)) {
          $conn->query("
            INSERT INTO guru_unavailable (id_guru, hari, full_day, jam_mulai, jam_selesai)
            VALUES ($id_guru, '$hari', 1, NULL, NULL)
          ");
        } else {
          $conn->query("
            INSERT INTO guru_unavailable (id_guru, hari, full_day, jam_mulai, jam_selesai)
            VALUES ($id_guru, '$hari', 0, '$mulai', '$selesai')
          ");
        }
      }
    }

    // ============================
    // HAPUS roster yang bentrok
    // ============================
    foreach ($_POST['hari'] as $hari) {

      $is_full = isset($_POST['full_day'][$hari]) ? 1 : 0;

      if ($is_full) {
        $del = $conn->prepare("
          DELETE FROM roster
          WHERE id_guru = ? AND hari = ?
        ");
        $del->bind_param("is", $id_guru, $hari);
        $del->execute();
        $del->close();
      } else {
        $mulai = $_POST['jam_mulai'][$hari] ?? NULL;
        $selesai = $_POST['jam_selesai'][$hari] ?? NULL;

        if (!empty($mulai) && !empty($selesai)) {

          $del = $conn->prepare("
            DELETE FROM roster
            WHERE id_guru = ?
              AND hari = ?
              AND NOT (jam_selesai <= ? OR jam_mulai >= ?)
          ");
          $del->bind_param("isss", $id_guru, $hari, $mulai, $selesai);
          $del->execute();
          $del->close();
        } else {
          // Jam kosong → full day
          $del = $conn->prepare("
            DELETE FROM roster
            WHERE id_guru = ? AND hari = ?
          ");
          $del->bind_param("is", $id_guru, $hari);
          $del->execute();
          $del->close();
        }
      }
    }
  }

  header("Location: guru.php");
  exit;
}

// ================== LOAD DATA ==================
$unavailable = [];
$res = $conn->query("SELECT * FROM guru_unavailable WHERE id_guru=$id_guru");
while ($row = $res->fetch_assoc()) {
  $unavailable[$row['hari']] = $row;
}

$hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
?>

<div class="container-fluid">

  <h3 class="mt-3">Atur Ketidaktersediaan: <?= htmlspecialchars($guru['nama']) ?></h3>
  <hr>

  <form method="post">

    <?php foreach ($hariList as $hari):
      $data = $unavailable[$hari] ?? null;
      $checked = $data ? "checked" : "";
      $full = ($data && $data['full_day']) ? "checked" : "";
    ?>

      <div class="card mb-3">

        <div class="card-header">
          <input type="checkbox" name="hari[]" value="<?= $hari ?>" <?= $checked ?>>
          <strong><?= $hari ?></strong>
        </div>

        <div class="card-body">

          <div class="form-check mb-2">
            <input class="form-check-input fullDayCheck" type="checkbox"
              name="full_day[<?= $hari ?>]" <?= $full ?>>
            <label class="form-check-label">Full Day</label>
          </div>

          <div class="row g-2 jamSection" <?= ($full ? 'style="display:none;"' : '') ?>>
            <div class="col-md-6">
              <label>Jam Mulai</label>
              <input type="time" class="form-control"
                name="jam_mulai[<?= $hari ?>]"
                value="<?= $data && !$data['full_day'] ? $data['jam_mulai'] : '' ?>">
            </div>
            <div class="col-md-6">
              <label>Jam Selesai</label>
              <input type="time" class="form-control"
                name="jam_selesai[<?= $hari ?>]"
                value="<?= $data && !$data['full_day'] ? $data['jam_selesai'] : '' ?>">
            </div>
          </div>

        </div>
      </div>

    <?php endforeach; ?>

    <button class="btn btn-primary" name="simpan">Simpan</button>
    <a href="guru.php" class="btn btn-secondary">Kembali</a>
  </form>

</div>

<script>
  document.querySelectorAll('.fullDayCheck').forEach(chk => {
    chk.addEventListener('change', function() {
      const card = this.closest('.card');
      const jam = card.querySelector('.jamSection');
      jam.style.display = this.checked ? 'none' : 'block';
    });
  });
</script>