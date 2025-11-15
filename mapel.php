<?php
include 'koneksi.php';
$pageTitle = "Manajemen Mata Pelajaran";
$pageLocation = "Mata Pelajaran";
// Memastikan layout.php yang modern (dengan link Font Awesome) di-include
include 'layout.php';

// Tambah mata pelajaran
if (isset($_POST['tambah'])) {
  $nama = $_POST['nama'];
  $cek = $conn->query("SELECT * FROM mata_pelajaran WHERE nama='$nama'");
  if ($cek->num_rows > 0) {
    $error = "Mata pelajaran ini sudah ada!";
  } else {
    $stmt = $conn->prepare("INSERT INTO mata_pelajaran (nama) VALUES (?)");
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    header("Location: mapel.php");
    exit;
  }
}

// Hapus mapel
if (isset($_GET['hapus'])) {
  $id = $_GET['hapus'];
  $conn->query("DELETE FROM mata_pelajaran WHERE id_mapel=$id");
  header("Location: mapel.php");
  exit;
}

// Edit mode
$editMode = false;
if (isset($_GET['edit'])) {
  $editMode = true;
  $id_edit = $_GET['edit'];
  $editData = $conn->query("SELECT * FROM mata_pelajaran WHERE id_mapel=$id_edit")->fetch_assoc();
}

// Update mapel
if (isset($_POST['update'])) {
  $id = $_POST['id_mapel'];
  $nama = $_POST['nama'];

  $cek = $conn->query("SELECT * FROM mata_pelajaran WHERE nama='$nama' AND id_mapel!='$id'");
  if ($cek->num_rows > 0) {
    $error = "Nama mata pelajaran sudah digunakan!";
  } else {
    $stmt = $conn->prepare("UPDATE mata_pelajaran SET nama=? WHERE id_mapel=?");
    $stmt->bind_param("si", $nama, $id);
    $stmt->execute();
    header("Location: mapel.php");
    exit;
  }
}

// Ambil semua mapel
$result = $conn->query("SELECT * FROM mata_pelajaran ORDER BY nama ASC");
?>

<style>
  /* === Styling Tambahan untuk Halaman Mapel (Sama seperti Guru/Kelas) === */
  .card-form {
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    border: none;
  }

  /* Styling Tabel Minimalis */
  .table-modern {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    background-color: white;
  }

  .table-modern thead th {
    background-color: #34495e;
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
    padding: 12px 15px;
  }
</style>

<div class="container-fluid">
  <h2 class="mb-4"><i class="fas fa-book-open me-2"></i>Manajemen Mata Pelajaran</h2>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= $error ?></div>
  <?php endif; ?>

  <div class="card card-form mb-4">
    <div class="card-header bg-white pt-3 pb-2 border-bottom-0">
      <h5 class="card-title mb-0 fw-bold">
        <?= $editMode ? "Edit Mata Pelajaran: " . htmlspecialchars($editData['nama']) : "Tambah Mata Pelajaran Baru" ?>
      </h5>
    </div>
    <div class="card-body pt-2">
      <form method="post">
        <div class="row g-3 align-items-center">

          <div class="col-md-8 col-lg-9">
            <label for="namaMapel" class="form-label visually-hidden">Nama Mata Pelajaran</label>
            <input type="text" name="nama" id="namaMapel" placeholder="Contoh: Matematika, Fisika, Sejarah"
              class="form-control"
              value="<?= $editMode ? htmlspecialchars($editData['nama']) : '' ?>" required>
          </div>

          <div class="col-md-4 col-lg-3">
            <?php if ($editMode): ?>
              <input type="hidden" name="id_mapel" value="<?= $editData['id_mapel'] ?>">
              <div class="d-flex gap-2">
                <button type="submit" name="update" class="btn btn-success flex-fill">
                  <i class="fas fa-save me-1"></i> Update
                </button>
                <a href="mapel.php" class="btn btn-secondary flex-fill">
                  <i class="fas fa-times me-1"></i> Batal
                </a>
              </div>
            <?php else: ?>
              <button type="submit" name="tambah" class="btn btn-primary w-100">
                <i class="fas fa-plus me-1"></i> Tambah Mapel
              </button>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="row mb-3">
    <div class="col-md-4">
      <input type="text" id="searchInput" class="form-control"
        placeholder="Cari mata pelajaran..." onkeyup="filterTable()">
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover table-modern" id="mapelTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nama Mata Pelajaran</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($mapel = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $mapel['id_mapel'] ?></td>
            <td class="namaMapel fw-bold"><?= htmlspecialchars($mapel['nama']) ?></td>
            <td class="text-center text-nowrap">
              <a href="mapel.php?edit=<?= $mapel['id_mapel'] ?>" class="btn btn-sm btn-outline-warning me-1" title="Edit">
                <i class="fas fa-edit"></i> Edit
              </a>
              <a href="mapel.php?hapus=<?= $mapel['id_mapel'] ?>"
                onclick="return confirm('Yakin ingin menghapus mata pelajaran <?= htmlspecialchars($mapel['nama']) ?>?')"
                class="btn btn-sm btn-outline-danger" title="Hapus">
                <i class="fas fa-trash-alt"></i> Hapus
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
  // === Live Search Filtering ===
  function filterTable() {
    let filter = document.getElementById("searchInput").value.toLowerCase();
    let rows = document.querySelectorAll("#mapelTable tbody tr");

    rows.forEach(row => {
      // Kolom index 1 berisi Nama Mata Pelajaran
      let nama = row.cells[1].textContent.toLowerCase();
      row.style.display = nama.includes(filter) ? "" : "none";
    });
  }
</script>

</body>

</html>