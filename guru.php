<?php
include 'koneksi.php';
$pageTitle = "Manajemen Guru";
$pageLocation = "Guru";
include 'layout.php'; // Layout dengan sidebar + header

// Tambah guru
if (isset($_POST['tambah'])) {
  $nama = $_POST['nama'];
  $email = $_POST['email'];

  // Validasi email unik
  $cek = $conn->query("SELECT * FROM guru WHERE email='$email'");
  if ($cek->num_rows > 0) {
    $error = "Email sudah digunakan!";
  } else {
    $stmt = $conn->prepare("INSERT INTO guru (nama,email) VALUES (?,?)");
    $stmt->bind_param("ss", $nama, $email);
    $stmt->execute();
    header("Location: guru.php");
    exit;
  }
}

// Hapus guru
if (isset($_GET['hapus'])) {
  $id = $_GET['hapus'];
  $conn->query("DELETE FROM guru WHERE id_guru=$id");
  header("Location: guru.php");
  exit;
}

// Ambil data guru
$result = $conn->query("SELECT * FROM guru");
?>

<div class="container-fluid">
  <h2>Manajemen Guru</h2>

  <!-- Alert error -->
  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <!-- Form tambah guru -->
  <form method="post" class="mb-4">
    <div class="row g-2">
      <div class="col-md-5">
        <input type="text" name="nama" placeholder="Nama Guru" class="form-control" required>
      </div>
      <div class="col-md-5">
        <input type="email" name="email" placeholder="Email Guru" class="form-control" required>
      </div>
      <div class="col-md-2">
        <button type="submit" name="tambah" class="btn btn-primary w-100">Tambah Guru</button>
      </div>
    </div>
  </form>

  <!-- Tabel data guru -->
  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead class="table-primary">
        <tr>
          <th>ID</th>
          <th>Nama</th>
          <th>Email</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($guru = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $guru['id_guru'] ?></td>
            <td><?= $guru['nama'] ?></td>
            <td><?= $guru['email'] ?></td>
            <td>
              <a href="guru.php?hapus=<?= $guru['id_guru'] ?>"
                class="btn btn-danger btn-sm"
                onclick="return confirm('Yakin ingin menghapus guru ini?')">Hapus</a>
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