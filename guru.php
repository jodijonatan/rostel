<?php
include 'koneksi.php';
$pageTitle = "Manajemen Guru";
$pageLocation = "Guru";
include 'layout.php'; // Sidebar + header

// Tambah guru
if (isset($_POST['tambah'])) {
  $nama = $_POST['nama'];
  $mapel_ids = $_POST['mapel'] ?? [];

  if (empty($mapel_ids)) {
    $error = "Pilih minimal satu mata pelajaran untuk guru!";
  } else {
    // Simpan guru baru
    $stmt = $conn->prepare("INSERT INTO guru (nama) VALUES (?)");
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $id_guru_baru = $stmt->insert_id;

    // Simpan relasi guru-mapel
    foreach ($mapel_ids as $id_mapel) {
      $conn->query("INSERT INTO guru_mapel (id_guru, id_mapel) VALUES ($id_guru_baru, $id_mapel)");
    }

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

// Ambil data guru untuk edit
$editMode = false;
if (isset($_GET['edit'])) {
  $editMode = true;
  $id_edit = $_GET['edit'];
  $editData = $conn->query("SELECT * FROM guru WHERE id_guru=$id_edit")->fetch_assoc();

  // Ambil mapel guru
  $editMapel = [];
  $res = $conn->query("SELECT id_mapel FROM guru_mapel WHERE id_guru=$id_edit");
  while ($row = $res->fetch_assoc()) {
    $editMapel[] = $row['id_mapel'];
  }
}

// Update guru
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
      $conn->query("INSERT INTO guru_mapel (id_guru, id_mapel) VALUES ($id, $id_mapel)");
    }

    header("Location: guru.php");
    exit;
  }
}

// Ambil semua guru + mapelnya
$result = $conn->query("
  SELECT g.id_guru, g.nama, GROUP_CONCAT(m.nama SEPARATOR ', ') AS mapel
  FROM guru g
  LEFT JOIN guru_mapel gm ON g.id_guru = gm.id_guru
  LEFT JOIN mata_pelajaran m ON gm.id_mapel = m.id_mapel
  GROUP BY g.id_guru
  ORDER BY g.id_guru ASC
");

// Ambil semua mapel untuk modal
$mapelList = $conn->query("SELECT * FROM mata_pelajaran ORDER BY nama ASC");
?>

<div class="container-fluid">
  <h2>Manajemen Guru</h2>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <!-- Form tambah/edit guru -->
  <form method="post" class="mb-4">
    <div class="row g-2 align-items-center">
      <div class="col-md-5">
        <input type="text" name="nama" placeholder="Nama Guru" class="form-control"
          value="<?= $editMode ? htmlspecialchars($editData['nama']) : '' ?>" required>
      </div>

      <div class="col-md-5">
        <!-- Tombol pilih mapel -->
        <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#mapelModal">
          Pilih Mapel
        </button>

        <!-- Simpan mapel terpilih -->
        <div id="selectedMapel" class="mt-2 text-secondary small">
          <?php if ($editMode): ?>
            <?php
            $selectedMapelNames = [];
            if (!empty($editMapel)) {
              $in = implode(',', $editMapel);
              $r = $conn->query("SELECT nama FROM mata_pelajaran WHERE id_mapel IN ($in)");
              while ($m = $r->fetch_assoc()) $selectedMapelNames[] = $m['nama'];
            }
            echo implode(', ', $selectedMapelNames);
            ?>
          <?php else: ?>
            Belum ada mapel dipilih
          <?php endif; ?>
        </div>
      </div>

      <div class="col-md-2">
        <?php if ($editMode): ?>
          <input type="hidden" name="id_guru" value="<?= $editData['id_guru'] ?>">
          <button type="submit" name="update" class="btn btn-success w-100">Update</button>
          <a href="guru.php" class="btn btn-secondary w-100 mt-2">Batal</a>
        <?php else: ?>
          <button type="submit" name="tambah" class="btn btn-primary w-100">Tambah</button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Input hidden untuk menyimpan id_mapel -->
    <div id="mapelInputs"></div>
  </form>

  <!-- Modal pilih mapel -->
  <div class="modal fade" id="mapelModal" tabindex="-1" aria-labelledby="mapelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="mapelModalLabel">Pilih Mata Pelajaran</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <?php while ($mapel = $mapelList->fetch_assoc()): ?>
            <div class="form-check">
              <input class="form-check-input mapel-checkbox" type="checkbox"
                value="<?= $mapel['id_mapel'] ?>" id="mapel<?= $mapel['id_mapel'] ?>"
                <?= $editMode && in_array($mapel['id_mapel'], $editMapel ?? []) ? 'checked' : '' ?>>
              <label class="form-check-label" for="mapel<?= $mapel['id_mapel'] ?>">
                <?= htmlspecialchars($mapel['nama']) ?>
              </label>
            </div>
          <?php endwhile; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" id="saveMapelBtn" data-bs-dismiss="modal">Simpan Pilihan</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Daftar guru -->
  <div class="table-responsive mt-4">
    <table class="table table-bordered table-striped">
      <thead class="table-primary">
        <tr>
          <th>ID</th>
          <th>Nama</th>
          <th>Mata Pelajaran Dibawa</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($guru = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $guru['id_guru'] ?></td>
            <td><?= htmlspecialchars($guru['nama']) ?></td>
            <td><?= htmlspecialchars($guru['mapel'] ?: '-') ?></td>
            <td>
              <a href="guru.php?edit=<?= $guru['id_guru'] ?>" class="btn btn-warning btn-sm">Edit</a>
              <a href="guru.php?hapus=<?= $guru['id_guru'] ?>" class="btn btn-danger btn-sm"
                onclick="return confirm('Yakin ingin menghapus guru ini?')">Hapus</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Simpan mapel dari modal ke form
  document.getElementById("saveMapelBtn").addEventListener("click", function() {
    const selected = Array.from(document.querySelectorAll(".mapel-checkbox:checked"));
    const selectedNames = selected.map(el => el.nextElementSibling.textContent.trim());
    const selectedIds = selected.map(el => el.value);

    // Tampilkan di teks
    document.getElementById("selectedMapel").textContent =
      selectedNames.length ? selectedNames.join(", ") : "Belum ada mapel dipilih";

    // Buat input hidden
    const container = document.getElementById("mapelInputs");
    container.innerHTML = "";
    selectedIds.forEach(id => {
      const input = document.createElement("input");
      input.type = "hidden";
      input.name = "mapel[]";
      input.value = id;
      container.appendChild(input);
    });
  });
</script>
</body>

</html>