<?php
include 'koneksi.php';
$pageTitle = "Manajemen Kelas";
$pageLocation = "Kelas";
include 'layout.php'; // Layout dengan sidebar + header

// Tambah kelas
if (isset($_POST['tambah'])) {
  $nama_kelas = $_POST['nama_kelas'];
  $angkatan = $_POST['angkatan'];

  // Validasi agar kelas + angkatan tidak double
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

// Hapus kelas
if (isset($_GET['hapus'])) {
  $id = $_GET['hapus'];
  $conn->query("DELETE FROM kelas WHERE id_kelas=$id");
  header("Location: kelas.php");
  exit;
}

// Ambil data untuk edit
$editMode = false;
if (isset($_GET['edit'])) {
  $editMode = true;
  $id_edit = $_GET['edit'];
  $editData = $conn->query("SELECT * FROM kelas WHERE id_kelas=$id_edit")->fetch_assoc();
}

// Update kelas
if (isset($_POST['update'])) {
  $id = $_POST['id_kelas'];
  $nama_kelas = $_POST['nama_kelas'];
  $angkatan = $_POST['angkatan'];

  // Cek duplikat kelas selain dirinya sendiri
  $cek = $conn->query("SELECT * FROM kelas WHERE nama_kelas='$nama_kelas' AND angkatan='$angkatan' AND id_kelas!='$id'");
  if ($cek->num_rows > 0) {
    $error = "Kelas dengan nama dan angkatan ini sudah ada!";
  } else {
    $stmt = $conn->prepare("UPDATE kelas SET nama_kelas=?, angkatan=? WHERE id_kelas=?");
    $stmt->bind_param("ssi", $nama_kelas, $angkatan, $id);
    $stmt->execute();
    header("Location: kelas.php");
    exit;
  }
}

// Ambil data kelas untuk tabel
$result = $conn->query("SELECT * FROM kelas");
?>

<div class="container-fluid">
  <h2>Manajemen Kelas</h2>

  <!-- Alert error -->
  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <!-- Form Tambah / Edit Kelas -->
  <form method="post" class="mb-4">
    <div class="row g-2">
      <div class="col-md-5">
        <input type="text" name="nama_kelas" placeholder="Nama Kelas" class="form-control"
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
          <button type="submit" name="tambah" class="btn btn-primary w-100">Tambah Kelas</button>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <!-- Tabel Kelas -->
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
      <tbody>
        <?php while ($kelas = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $kelas['id_kelas'] ?></td>
            <td><?= htmlspecialchars($kelas['nama_kelas']) ?></td>
            <td><?= htmlspecialchars($kelas['angkatan']) ?></td>
            <td>
              <a href="kelas.php?edit=<?= $kelas['id_kelas'] ?>" class="btn btn-warning btn-sm">Edit</a>
              <a href="kelas.php?hapus=<?= $kelas['id_kelas'] ?>"
                class="btn btn-danger btn-sm"
                onclick="return confirm('Yakin ingin menghapus kelas ini?')">Hapus</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
// Tutup main-content dan layout
echo "</div>";
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>