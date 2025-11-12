<?php
include 'koneksi.php';
$pageTitle = "Manajemen Mata Pelajaran";
$pageLocation = "Mata Pelajaran";
include 'layout.php'; // Layout dengan sidebar + header

// Tambah mata pelajaran
if (isset($_POST['tambah'])) {
  $nama = $_POST['nama'];

  // Validasi agar mata pelajaran tidak duplikat
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

// Hapus mata pelajaran
if (isset($_GET['hapus'])) {
  $id = $_GET['hapus'];
  $conn->query("DELETE FROM mata_pelajaran WHERE id_mapel=$id");
  header("Location: mapel.php");
  exit;
}

// Ambil data untuk edit
$editMode = false;
if (isset($_GET['edit'])) {
  $editMode = true;
  $id_edit = $_GET['edit'];
  $editData = $conn->query("SELECT * FROM mata_pelajaran WHERE id_mapel=$id_edit")->fetch_assoc();
}

// Update mata pelajaran
if (isset($_POST['update'])) {
  $id = $_POST['id_mapel'];
  $nama = $_POST['nama'];

  // Cek duplikat selain dirinya sendiri
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

// Ambil semua data mapel
$result = $conn->query("SELECT * FROM mata_pelajaran");
?>

<div class="container-fluid">
  <h2>Manajemen Mata Pelajaran</h2>

  <!-- Alert error -->
  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <!-- Form tambah / edit mata pelajaran -->
  <form method="post" class="mb-4">
    <div class="row g-2">
      <div class="col-md-10">
        <input type="text" name="nama" placeholder="Nama Mata Pelajaran" class="form-control"
          value="<?= $editMode ? htmlspecialchars($editData['nama']) : '' ?>" required>
      </div>
      <div class="col-md-2">
        <?php if ($editMode): ?>
          <input type="hidden" name="id_mapel" value="<?= $editData['id_mapel'] ?>">
          <button type="submit" name="update" class="btn btn-success w-100">Update</button>
          <a href="mapel.php" class="btn btn-secondary w-100 mt-2">Batal</a>
        <?php else: ?>
          <button type="submit" name="tambah" class="btn btn-primary w-100">Tambah Mapel</button>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <!-- Tabel data mata pelajaran -->
  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead class="table-primary">
        <tr>
          <th>ID</th>
          <th>Nama Mata Pelajaran</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($mapel = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $mapel['id_mapel'] ?></td>
            <td><?= htmlspecialchars($mapel['nama']) ?></td>
            <td>
              <a href="mapel.php?edit=<?= $mapel['id_mapel'] ?>" class="btn btn-warning btn-sm">Edit</a>
              <a href="mapel.php?hapus=<?= $mapel['id_mapel'] ?>"
                class="btn btn-danger btn-sm"
                onclick="return confirm('Yakin ingin menghapus mata pelajaran ini?')">Hapus</a>
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