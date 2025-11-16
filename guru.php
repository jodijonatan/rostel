<?php
include 'koneksi.php';

// ----------- PHP LOGIC -----------

// ----------- TAMBAH GURU -----------
if (isset($_POST['tambah'])) {
  $nama = $_POST['nama'];
  // Pastikan kunci 'mapel' ada, jika tidak, default ke array kosong
  $mapel_ids = $_POST['mapel'] ?? [];

  if (empty($mapel_ids)) {
    $error = "Pilih minimal satu mata pelajaran untuk guru!";
  } else {
    $stmt = $conn->prepare("INSERT INTO guru (nama) VALUES (?)");
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $id_guru_baru = $stmt->insert_id;

    foreach ($mapel_ids as $id_mapel) {
      // Menggunakan prepared statement untuk keamanan
      $stmt_mapel = $conn->prepare("INSERT INTO guru_mapel (id_guru, id_mapel) VALUES (?, ?)");
      $stmt_mapel->bind_param("ii", $id_guru_baru, $id_mapel);
      $stmt_mapel->execute();
    }

    header("Location: guru.php");
    exit;
  }
}

// ----------- HAPUS GURU -----------
if (isset($_GET['hapus'])) {
  $id = $_GET['hapus'];
  // Hapus dari guru_mapel dulu
  $conn->query("DELETE FROM guru_mapel WHERE id_guru=$id");
  $conn->query("DELETE FROM guru WHERE id_guru=$id");
  header("Location: guru.php");
  exit;
}

// ----------- AMBIL DATA EDIT -----------
$editMode = false;
$editMapel = []; // Array ID mapel yang sudah terpilih
$editMapelNames = "Pilih Mapel"; // Text untuk ditampilkan di tombol

if (isset($_GET['edit'])) {
  $editMode = true;
  $id_edit = $_GET['edit'];
  $editData = $conn->query("SELECT * FROM guru WHERE id_guru=$id_edit")->fetch_assoc();

  $res = $conn->query("SELECT gm.id_mapel, m.nama 
                       FROM guru_mapel gm
                       JOIN mata_pelajaran m ON gm.id_mapel = m.id_mapel
                       WHERE gm.id_guru=$id_edit");

  $tempNames = [];
  while ($row = $res->fetch_assoc()) {
    $editMapel[] = $row['id_mapel'];
    $tempNames[] = $row['nama'];
  }

  // Update teks tombol
  if (!empty($tempNames)) {
    $editMapelNames = htmlspecialchars(implode(', ', $tempNames));
  }
}

// ----------- UPDATE GURU -----------
if (isset($_POST['update'])) {
  $id = $_POST['id_guru'];
  $nama = $_POST['nama'];
  $mapel_ids = $_POST['mapel'] ?? [];

  if (empty($mapel_ids)) {
    $error = "Pilih minimal satu mapel untuk guru!";
  } else {
    $stmt = $conn->prepare("UPDATE guru SET nama=? WHERE id_guru=?");
    $stmt->bind_param("si", $nama, $id);
    $stmt->execute();

    $conn->query("DELETE FROM guru_mapel WHERE id_guru=$id");
    foreach ($mapel_ids as $id_mapel) {
      // Menggunakan prepared statement untuk keamanan
      $stmt_mapel = $conn->prepare("INSERT INTO guru_mapel (id_guru, id_mapel) VALUES (?, ?)");
      $stmt_mapel->bind_param("ii", $id, $id_mapel);
      $stmt_mapel->execute();
    }

    header("Location: guru.php");
    exit;
  }
}

// ----------- AMBIL DATA GURU -----------
$result = $conn->query("
  SELECT g.id_guru, g.nama, GROUP_CONCAT(m.nama SEPARATOR ', ') AS mapel
  FROM guru g
  LEFT JOIN guru_mapel gm ON g.id_guru = gm.id_guru
  LEFT JOIN mata_pelajaran m ON gm.id_mapel = m.id_mapel
  GROUP BY g.id_guru
  ORDER BY g.id_guru ASC
");

// ----------- AMBIL LIST MAPEL -----------
// Reset pointer mapelList setelah digunakan di blok edit
$mapelList = $conn->query("SELECT * FROM mata_pelajaran ORDER BY nama ASC");

// ----------- CEK KETIDAKTERSEDIAAN -----------
$cekUnavailable = $conn->query("SELECT id_guru, COUNT(*) AS total FROM guru_unavailable GROUP BY id_guru");
$dataUnavailable = [];
while ($u = $cekUnavailable->fetch_assoc()) {
  $dataUnavailable[$u['id_guru']] = $u['total'];
}

// ======= SETELAH SEMUA LOGIKA, BARU INCLUDE LAYOUT ======
$pageTitle = "Manajemen Guru";
$pageLocation = "Guru";
include 'layout.php';
?>

<style>
  /* === Styling Tambahan untuk Halaman Guru === */
  .card-form {
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    /* Shadow yang lebih dalam */
    border-radius: 12px;
    border: none;
  }

  /* Styling Tabel Minimalis */
  .table-modern {
    border-radius: 8px;
    overflow: hidden;
    /* Penting untuk menjaga border-radius tabel */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    background-color: white;
  }

  .table-modern thead th {
    background-color: #34495e;
    /* Warna kepala tabel yang lebih gelap/profesional */
    color: white;
    border: none;
    font-weight: 600;
  }

  .table-modern tbody tr {
    transition: background-color 0.2s;
  }

  .table-modern tbody tr:hover {
    background-color: #f8f9fa;
  }

  .table-modern td,
  .table-modern th {
    border-color: #e9ecef;
    /* Garis pemisah yang lebih halus */
    padding: 12px 15px;
  }

  /* Styling untuk tombol di modal */
  .modal-body .form-check {
    padding: 8px 15px;
    margin-bottom: 5px;
    border-radius: 5px;
    transition: background-color 0.2s;
  }

  .modal-body .form-check:hover {
    background-color: #f1f1f1;
  }
</style>

<div class="container-fluid">
  <h2 class="mb-4"><i class="fas fa-chalkboard-teacher me-2"></i>Manajemen Guru</h2>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= $error ?></div>
  <?php endif; ?>

  <div class="card card-form mb-4">
    <div class="card-header bg-white pt-3 pb-2 border-bottom-0">
      <h5 class="card-title mb-0 fw-bold"><?= $editMode ? "Edit Guru: " . htmlspecialchars($editData['nama']) : "Tambah Guru Baru" ?></h5>
    </div>
    <div class="card-body pt-2">
      <form method="post">
        <div class="row g-3 align-items-center">
          <div class="col-md-5">
            <label for="namaGuru" class="form-label visually-hidden">Nama Guru</label>
            <input type="text" name="nama" id="namaGuru" placeholder="Nama Guru" class="form-control"
              value="<?= $editMode ? htmlspecialchars($editData['nama']) : '' ?>" required>
          </div>

          <div class="col-md-5">
            <label class="form-label visually-hidden">Pilih Mapel</label>
            <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#mapelModal" id="mapelButtonText">
              <i class="fas fa-book me-2"></i><?= $editMapelNames ?>
            </button>
          </div>

          <div class="col-md-2 d-flex flex-column">
            <?php if ($editMode): ?>
              <input type="hidden" name="id_guru" value="<?= $editData['id_guru'] ?>">
              <button type="submit" name="update" class="btn btn-success w-100 mb-2">
                <i class="fas fa-save me-1"></i> Update
              </button>
              <a href="guru.php" class="btn btn-secondary w-100">
                <i class="fas fa-times me-1"></i> Batal
              </a>
            <?php else: ?>
              <button type="submit" name="tambah" class="btn btn-primary w-100">
                <i class="fas fa-plus me-1"></i> Tambah
              </button>
            <?php endif; ?>
          </div>
        </div>
        <div id="mapelInputs">
          <?php if ($editMode): ?>
            <?php foreach ($editMapel as $id_mapel): ?>
              <input type="hidden" name="mapel[]" value="<?= $id_mapel ?>">
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="row mb-3 align-items-center">
    <div class="col-lg-3 col-md-5 mb-2 mb-md-0">
      <input type="text" id="searchGuru" class="form-control" placeholder="Cari nama guru...">
    </div>

    <div class="col-lg-2 col-md-4">
      <select id="sortSelect" class="form-select">
        <option value="asc">Nama (A-Z)</option>
        <option value="desc">Nama (Z-A)</option>
      </select>
    </div>
  </div>

  <div class="modal fade" id="mapelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-light">
          <h5 class="modal-title"><i class="fas fa-tasks me-2"></i>Pilih Mata Pelajaran</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <?php
          // Reset pointer mapelList agar dapat diulang di modal
          $mapelList->data_seek(0);
          while ($mapel = $mapelList->fetch_assoc()): ?>
            <div class="form-check">
              <input class="form-check-input mapel-checkbox" type="checkbox"
                value="<?= $mapel['id_mapel'] ?>"
                id="mapel<?= $mapel['id_mapel'] ?>"
                <?= $editMode && in_array($mapel['id_mapel'], $editMapel) ? 'checked' : '' ?>>

              <label class="form-check-label" for="mapel<?= $mapel['id_mapel'] ?>">
                <?= htmlspecialchars($mapel['nama']) ?>
              </label>
            </div>
          <?php endwhile; ?>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-primary" id="saveMapelBtn" data-bs-dismiss="modal">
            <i class="fas fa-check me-1"></i> Simpan Pilihan
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="table-responsive mt-4">
    <table class="table table-hover table-modern" id="guruTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nama</th>
          <th>Mata Pelajaran</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>

      <tbody>
        <?php while ($guru = $result->fetch_assoc()): ?>
          <?php
          $punya = isset($dataUnavailable[$guru['id_guru']]);
          $label = $punya ? "Edit Ketidaktersediaan" : "Atur Ketidaktersediaan";
          $btnColor = $punya ? "btn-warning" : "btn-info";
          ?>
          <tr>
            <td><?= $guru['id_guru'] ?></td>
            <td class="namaGuru fw-bold"><?= htmlspecialchars($guru['nama']) ?></td>
            <td><?= $guru['mapel'] ?: '<span class="text-danger small">Belum diatur</span>' ?></td>

            <td class="text-center text-nowrap">
              <a href="guru.php?edit=<?= $guru['id_guru'] ?>" class="btn btn-sm btn-outline-warning me-1" title="Edit">
                <i class="fas fa-edit"></i>
              </a>

              <a href="guru.php?hapus=<?= $guru['id_guru'] ?>"
                onclick="return confirm('Yakin ingin menghapus guru <?= htmlspecialchars($guru['nama']) ?>?')"
                class="btn btn-sm btn-outline-danger me-1" title="Hapus">
                <i class="fas fa-trash-alt"></i>
              </a>

              <a href="guru_unavailable.php?id_guru=<?= $guru['id_guru'] ?>"
                class="btn <?= $btnColor ?> btn-sm text-white" title="<?= $label ?>">
                <i class="fas fa-clock me-1"></i> <?= $label ?>
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php echo "</div>"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // ========== SIMPAN MAPEL DARI MODAL + UPDATE TEXT TOMBOL ==========
  document.getElementById("saveMapelBtn").addEventListener("click", function() {
    const selected = Array.from(document.querySelectorAll(".mapel-checkbox:checked"));
    const names = selected.map(el => el.nextElementSibling.textContent.trim());
    const ids = selected.map(el => el.value);

    // 1. Update teks pada tombol Pilih Mapel
    document.getElementById("mapelButtonText").innerHTML =
      (names.length ? `<i class="fas fa-book me-2"></i>` : `<i class="fas fa-book me-2"></i>`) + (names.length ? names.join(", ") : "Pilih Mapel");

    // 2. Update hidden input untuk dikirim ke server
    const container = document.getElementById("mapelInputs");
    container.innerHTML = "";
    ids.forEach(id => {
      const input = document.createElement("input");
      input.type = "hidden";
      input.name = "mapel[]";
      input.value = id;
      container.appendChild(input);
    });
  });

  // ========== LIVE SEARCH ==========
  document.getElementById("searchGuru").addEventListener("keyup", function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll("#guruTable tbody tr");

    rows.forEach(row => {
      let namaGuru = row.querySelector(".namaGuru").textContent.toLowerCase();
      row.style.display = namaGuru.includes(filter) ? "" : "none";
    });
  });

  // ========== SORTING A-Z & Z-A ==========
  document.getElementById("sortSelect").addEventListener("change", function() {
    let mode = this.value; // asc / desc
    let table = document.getElementById("guruTable");
    let rows = Array.from(table.querySelectorAll("tbody tr"));

    rows.sort((a, b) => {
      let A = a.querySelector(".namaGuru").textContent.toLowerCase();
      let B = b.querySelector(".namaGuru").textContent.toLowerCase();
      return mode === "asc" ? A.localeCompare(B) : B.localeCompare(A);
    });

    rows.forEach(r => table.querySelector("tbody").appendChild(r));
  });
</script>

</body>

</html>