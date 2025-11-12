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

// Ambil data kelas
$result = $conn->query("SELECT * FROM kelas");
?>

<div class="container-fluid">
  <h2>Manajemen Kelas</h2>

  <!-- Alert error -->
  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <!-- Form Tambah Kelas -->
  <form method="post" class="mb-4">
    <div class="row g-2">
      <div class="col-md-5">
        <input type="text" name="nama_kelas" placeholder="Nama Kelas" class="form-control" required>
      </div>
      <div class="col-md-5">
        <select name="angkatan" class="form-control" required>
          <option value="">Pilih Angkatan</option>
          <option value="X">X</option>
          <option value="XI">XI</option>
          <option value="XII">XII</option>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" name="tambah" class="btn btn-primary w-100">Tambah Kelas</button>
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
            <td><?= $kelas['nama_kelas'] ?></td>
            <td><?= $kelas['angkatan'] ?></td>
            <td>
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