<?php
include 'koneksi.php';

//  TAMBAH KELAS
if (isset($_POST['tambah'])) {
  $nama_kelas = $_POST['nama_kelas'];
  $angkatan = $_POST['angkatan'];

  $cek = $conn->query("SELECT * FROM kelas WHERE nama_kelas='$nama_kelas' AND angkatan='$angkatan'");
  if ($cek->num_rows > 0) {
    $error = "Kelas ini sudah ada!";
  } else {
    $stmt = $conn->prepare("INSERT INTO kelas (nama_kelas, angkatan) VALUES (?, ?)");
    $stmt->bind_param("ss", $nama_kelas, $angkatan);
    $stmt->execute();
    header("Location: kelas.php");
    exit;
  }
}

//  HAPUS KELAS
if (isset($_GET['hapus'])) {
  $id = $_GET['hapus'];
  $conn->query("DELETE FROM kelas WHERE id_kelas=$id");
  header("Location: kelas.php");
  exit;
}

//  EDIT MODE
$editMode = false;
if (isset($_GET['edit'])) {
  $editMode = true;
  $id_edit = $_GET['edit'];
  $editData = $conn->query("SELECT * FROM kelas WHERE id_kelas=$id_edit")->fetch_assoc();
}

//  UPDATE KELAS (DITAMBAHKAN/DIPERBAIKI)
if (isset($_POST['update'])) {
  $id = $_POST['id_kelas'];
  $nama_kelas = $_POST['nama_kelas'];
  $angkatan = $_POST['angkatan'];

  // Cek duplikasi, kecuali ID kelas yang sedang diedit
  $cek = $conn->query("SELECT * FROM kelas WHERE nama_kelas='$nama_kelas' AND angkatan='$angkatan' AND id_kelas != $id");
  if ($cek->num_rows > 0) {
    $error = "Kelas ini sudah ada di angkatan lain!";
  } else {
    $stmt = $conn->prepare("UPDATE kelas SET nama_kelas=?, angkatan=? WHERE id_kelas=?");
    $stmt->bind_param("ssi", $nama_kelas, $angkatan, $id);
    $stmt->execute();
    header("Location: kelas.php");
    exit;
  }
}

//  AMBIL DATA KELAS
$result = $conn->query("SELECT * FROM kelas ORDER BY angkatan ASC, nama_kelas ASC");

// ======= INCLUDE LAYOUT ======
$pageTitle = "Manajemen Kelas";
$pageLocation = "Kelas";
include 'layout.php';
?>

<style>
  /* === Styling untuk Halaman Kelas === */
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
  <h2 class="mb-4"><i class="fas fa-school me-2"></i>Manajemen Kelas</h2>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= $error ?></div>
  <?php endif; ?>

  <div class="card card-form mb-4">
    <div class="card-header bg-white pt-3 pb-2 border-bottom-0">
      <h5 class="card-title mb-0 fw-bold">
        <?= $editMode ? "Edit Kelas: " . htmlspecialchars($editData['nama_kelas']) : "Tambah Kelas Baru" ?>
      </h5>
    </div>
    <div class="card-body pt-2">
      <form method="post">
        <div class="row g-3 align-items-center">

          <div class="col-md-4">
            <label for="namaKelas" class="form-label visually-hidden">Nama Kelas</label>
            <input type="text" name="nama_kelas" id="namaKelas" class="form-control"
              placeholder="Contoh: IPA 1 / IPS 2"
              value="<?= $editMode ? htmlspecialchars($editData['nama_kelas']) : '' ?>" required>
          </div>

          <div class="col-md-4">
            <label for="angkatanKelas" class="form-label visually-hidden">Pilih Angkatan</label>
            <select name="angkatan" id="angkatanKelas" class="form-select" required>
              <option value="">Pilih Angkatan</option>
              <option value="X" <?= $editMode && $editData['angkatan'] == 'X' ? 'selected' : '' ?>>Kelas X</option>
              <option value="XI" <?= $editMode && $editData['angkatan'] == 'XI' ? 'selected' : '' ?>>Kelas XI</option>
              <option value="XII" <?= $editMode && $editData['angkatan'] == 'XII' ? 'selected' : '' ?>>Kelas XII</option>
            </select>
          </div>

          <div class="col-md-4">
            <?php if ($editMode): ?>
              <input type="hidden" name="id_kelas" value="<?= $editData['id_kelas'] ?>">
              <div class="d-flex gap-2">
                <button type="submit" name="update" class="btn btn-success flex-fill">
                  <i class="fas fa-save me-1"></i> Update
                </button>
                <a href="kelas.php" class="btn btn-secondary flex-fill">
                  <i class="fas fa-times me-1"></i> Batal
                </a>
              </div>
            <?php else: ?>
              <button type="submit" name="tambah" class="btn btn-primary w-100">
                <i class="fas fa-plus me-1"></i> Tambah Kelas
              </button>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>


  <div class="row mb-3 g-2">
    <div class="col-md-4">
      <input type="text" id="searchInput" class="form-control" placeholder="Cari nama kelas..." onkeyup="loadKelas()">
    </div>

    <div class="col-md-3">
      <select id="filterAngkatan" class="form-select" onchange="loadKelas()">
        <option value="">Semua Angkatan</option>
        <option value="X">Kelas X</option>
        <option value="XI">Kelas XI</option>
        <option value="XII">Kelas XII</option>
      </select>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover table-modern" id="mainTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nama Kelas</th>
          <th>Angkatan</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>

      <tbody id="kelasTable">
        <?php
        // Menggunakan $result dari blok PHP di atas
        while ($kelas = $result->fetch_assoc()):
        ?>
          <tr>
            <td><?= $kelas['id_kelas'] ?></td>
            <td class="namaKelas fw-bold"><?= htmlspecialchars($kelas['nama_kelas']) ?></td>
            <td class="angkatanKelas"><?= htmlspecialchars($kelas['angkatan']) ?></td>
            <td class="text-center text-nowrap">
              <a href="kelas.php?edit=<?= $kelas['id_kelas'] ?>" class="btn btn-sm btn-outline-warning me-1" title="Edit">
                <i class="fas fa-edit"></i> Edit
              </a>
              <a href="kelas.php?hapus=<?= $kelas['id_kelas'] ?>"
                onclick="return confirm('Yakin ingin menghapus kelas <?= htmlspecialchars($kelas['nama_kelas']) ?>?')"
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
  function loadKelas() {
    const keyword = document.getElementById("searchInput").value.toLowerCase();
    const angkatanFilter = document.getElementById("filterAngkatan").value;
    const rows = document.querySelectorAll("#kelasTable tr");

    rows.forEach(row => {
      const namaKelas = row.querySelector(".namaKelas").textContent.toLowerCase();
      const angkatan = row.querySelector(".angkatanKelas").textContent;

      const keywordMatch = namaKelas.includes(keyword);
      const angkatanMatch = angkatanFilter === "" || angkatan === angkatanFilter;

      row.style.display = (keywordMatch && angkatanMatch) ? "" : "none";
    });
  }
</script>

</body>

</html>