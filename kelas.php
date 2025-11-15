<?php
include 'koneksi.php';
$pageTitle = "Manajemen Kelas";
$pageLocation = "Kelas";
include 'layout.php'; // Sidebar + header

// =============================
//  TAMBAH KELAS
// =============================
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

// =============================
//  HAPUS KELAS
// =============================
if (isset($_GET['hapus'])) {
  $id = $_GET['hapus'];
  $conn->query("DELETE FROM kelas WHERE id_kelas=$id");
  header("Location: kelas.php");
  exit;
}

// =============================
//  EDIT MODE
// =============================
$editMode = false;
if (isset($_GET['edit'])) {
  $editMode = true;
  $id_edit = $_GET['edit'];
  $editData = $conn->query("SELECT * FROM kelas WHERE id_kelas=$id_edit")->fetch_assoc();
}
?>

<div class="container-fluid">
  <h2>Manajemen Kelas</h2>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <!-- ================= FORM TAMBAH / EDIT ================= -->
  <form method="post" class="mb-4">
    <div class="row g-2">
      <div class="col-md-5">
        <input type="text" name="nama_kelas" class="form-control"
          placeholder="Nama Kelas"
          value="<?= $editMode ? htmlspecialchars($editData['nama_kelas']) : '' ?>" required>
      </div>

      <div class="col-md-5">
        <select name="angkatan" class="form-control" required>
          <option value="">Pilih Angkatan</option>
          <option value="X" <?= $editMode && $editData['angkatan'] == 'X' ? 'selected' : '' ?>>X</option>
          <option value="XI" <?= $editMode && $editData['angkatan'] == 'XI' ? 'selected' : '' ?>>XI</option>
          <option value="XII" <?= $editMode && $editData['angkatan'] == 'XII' ? 'selected' : '' ?>>XII</option>
        </select>
      </div>

      <div class="col-md-2">
        <?php if ($editMode): ?>
          <input type="hidden" name="id_kelas" value="<?= $editData['id_kelas'] ?>">
          <button type="submit" name="update" class="btn btn-success w-100">Update</button>
          <a href="kelas.php" class="btn btn-secondary w-100 mt-2">Batal</a>
        <?php else: ?>
          <button type="submit" name="tambah" class="btn btn-primary w-100">Tambah</button>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <!-- ================= SEARCH BAR (LIVE SEARCH) ================= -->
  <div class="row mb-3 g-2">
    <div class="col-md-4">
      <input type="text" id="searchInput" class="form-control" placeholder="Cari kelas...">
    </div>

    <div class="col-md-3">
      <select id="filterAngkatan" class="form-control">
        <option value="">Filter Angkatan</option>
        <option value="X">X</option>
        <option value="XI">XI</option>
        <option value="XII">XII</option>
      </select>
    </div>
  </div>

  <!-- ================= TABLE ================= -->
  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead class="table-primary">
        <tr>
          <th>ID</th>
          <th>Nama Kelas</th>
          <th>Angkatan</th>
          <th>Aksi</th>
        </tr>
      </thead>

      <tbody id="kelasTable">
        <?php
        $result = $conn->query("SELECT * FROM kelas ORDER BY id_kelas DESC");
        while ($kelas = $result->fetch_assoc()):
        ?>
          <tr>
            <td><?= $kelas['id_kelas'] ?></td>
            <td><?= htmlspecialchars($kelas['nama_kelas']) ?></td>
            <td><?= htmlspecialchars($kelas['angkatan']) ?></td>
            <td>
              <a href="kelas.php?edit=<?= $kelas['id_kelas'] ?>" class="btn btn-warning btn-sm">Edit</a>
              <a href="kelas.php?hapus=<?= $kelas['id_kelas'] ?>"
                onclick="return confirm('Yakin ingin menghapus kelas ini?')"
                class="btn btn-danger btn-sm">Hapus</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

</div>

<script>
  // ====================== LIVE SEARCH ======================
  function loadKelas() {
    const keyword = document.getElementById("searchInput").value;
    const angkatan = document.getElementById("filterAngkatan").value;

    fetch("kelas_search.php?search=" + keyword + "&angkatan=" + angkatan)
      .then(res => res.text())
      .then(data => {
        document.getElementById("kelasTable").innerHTML = data;
      });
  }

  document.getElementById("searchInput").addEventListener("keyup", loadKelas);
  document.getElementById("filterAngkatan").addEventListener("change", loadKelas);
</script>

</body>

</html>