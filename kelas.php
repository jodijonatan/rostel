<?php
include 'koneksi.php';
$pageTitle = "Manajemen Kelas";
$pageLocation = "Kelas";
include 'layout.php'; // Sidebar + header

// =============================
//  SEARCH & FILTER
// =============================
$keyword = isset($_GET['search']) ? trim($_GET['search']) : "";
$filter_angkatan = isset($_GET['angkatan']) ? trim($_GET['angkatan']) : "";

// =============================
//  PAGINATION
// =============================
$limit = 10; // jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// =============================
//  QUERY DASAR
// =============================
$query = "SELECT * FROM kelas WHERE 1";
$countQuery = "SELECT COUNT(*) AS total FROM kelas WHERE 1";

// SEARCH
if ($keyword !== "") {
  $query .= " AND (nama_kelas LIKE '%$keyword%' OR angkatan LIKE '%$keyword%')";
  $countQuery .= " AND (nama_kelas LIKE '%$keyword%' OR angkatan LIKE '%$keyword%')";
}

// FILTER ANGKATAN
if ($filter_angkatan !== "") {
  $query .= " AND angkatan = '$filter_angkatan'";
  $countQuery .= " AND angkatan = '$filter_angkatan'";
}

// HITUNG TOTAL DATA
$countResult = $conn->query($countQuery);
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// PAGINATION LIMIT
$query .= " LIMIT $start, $limit";
$result = $conn->query($query);

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

// =============================
//  UPDATE KELAS
// =============================
if (isset($_POST['update'])) {
  $id = $_POST['id_kelas'];
  $nama_kelas = $_POST['nama_kelas'];
  $angkatan = $_POST['angkatan'];

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
?>

<div class="container-fluid">
  <h2>Manajemen Kelas</h2>

  <!-- Alert -->
  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <!-- Search + Filter -->
  <form method="GET" class="mb-3">
    <div class="row g-2">
      <div class="col-md-4">
        <input type="text" name="search" class="form-control"
          placeholder="Cari kelas..." value="<?= htmlspecialchars($keyword) ?>">
      </div>

      <div class="col-md-3">
        <select name="angkatan" class="form-control">
          <option value="">Filter Angkatan</option>
          <option value="X" <?= $filter_angkatan == "X" ? "selected" : "" ?>>X</option>
          <option value="XI" <?= $filter_angkatan == "XI" ? "selected" : "" ?>>XI</option>
          <option value="XII" <?= $filter_angkatan == "XII" ? "selected" : "" ?>>XII</option>
        </select>
      </div>

      <div class="col-md-2">
        <button class="btn btn-primary w-100">Cari</button>
      </div>

      <div class="col-md-2">
        <a href="kelas.php" class="btn btn-secondary w-100">Reset</a>
      </div>
    </div>
  </form>

  <!-- Form Tambah / Edit -->
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

  <!-- Tabel -->
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
                onclick="return confirm('Yakin ingin menghapus kelas ini?')"
                class="btn btn-danger btn-sm">Hapus</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- PAGINATION -->
  <nav>
    <ul class="pagination">

      <!-- First -->
      <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
        <a class="page-link"
          href="?page=1&search=<?= urlencode($keyword) ?>&angkatan=<?= urlencode($filter_angkatan) ?>">
          First
        </a>
      </li>

      <!-- Prev -->
      <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
        <a class="page-link"
          href="?page=<?= $page - 1 ?>&search=<?= urlencode($keyword) ?>&angkatan=<?= urlencode($filter_angkatan) ?>">
          Prev
        </a>
      </li>

      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
          <a class="page-link"
            href="?page=<?= $i ?>&search=<?= urlencode($keyword) ?>&angkatan=<?= urlencode($filter_angkatan) ?>">
            <?= $i ?>
          </a>
        </li>
      <?php endfor; ?>

      <!-- Next -->
      <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
        <a class="page-link"
          href="?page=<?= $page + 1 ?>&search=<?= urlencode($keyword) ?>&angkatan=<?= urlencode($filter_angkatan) ?>">
          Next
        </a>
      </li>

      <!-- Last -->
      <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
        <a class="page-link"
          href="?page=<?= $totalPages ?>&search=<?= urlencode($keyword) ?>&angkatan=<?= urlencode($filter_angkatan) ?>">
          Last
        </a>
      </li>

    </ul>
  </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>