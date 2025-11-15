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
    $stmt = $conn->prepare("INSERT INTO guru (nama) VALUES (?)");
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $id_guru_baru = $stmt->insert_id;

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
$editMapel = [];
if (isset($_GET['edit'])) {
  $editMode = true;
  $id_edit = $_GET['edit'];
  $editData = $conn->query("SELECT * FROM guru WHERE id_guru=$id_edit")->fetch_assoc();

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

// Ambil daftar guru
$result = $conn->query("
  SELECT g.id_guru, g.nama, GROUP_CONCAT(m.nama SEPARATOR ', ') AS mapel
  FROM guru g
  LEFT JOIN guru_mapel gm ON g.id_guru = gm.id_guru
  LEFT JOIN mata_pelajaran m ON gm.id_mapel = m.id_mapel
  GROUP BY g.id_guru
  ORDER BY g.id_guru ASC
");

// Ambil daftar mapel
$mapelList = $conn->query("SELECT * FROM mata_pelajaran ORDER BY nama ASC");

// CEK GURU YANG PUNYA KETIDAKTERSEDIAAN
$cekUnavailable = $conn->query("SELECT id_guru, COUNT(*) AS total FROM guru_unavailable GROUP BY id_guru");
$dataUnavailable = [];
while ($u = $cekUnavailable->fetch_assoc()) {
  $dataUnavailable[$u['id_guru']] = $u['total'];
}
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
        <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#mapelModal">
          Pilih Mapel
        </button>

        <div id="selectedMapel" class="mt-2 text-secondary small">
          <?php
          if ($editMode && !empty($editMapel)) {
            $in = implode(',', $editMapel);
            $names = [];
            $r = $conn->query("SELECT nama FROM mata_pelajaran WHERE id_mapel IN ($in)");
            while ($m = $r->fetch_assoc()) $names[] = $m['nama'];
            echo implode(', ', $names);
          } else {
            echo "Belum ada mapel dipilih";
          }
          ?>
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

    <div id="mapelInputs"></div>
  </form>

  <!-- Modal mapel -->
  <div class="modal fade" id="mapelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Pilih Mata Pelajaran</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <?php while ($mapel = $mapelList->fetch_assoc()): ?>
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
          <button type="button" class="btn btn-primary" id="saveMapelBtn" data-bs-dismiss="modal">Simpan Pilihan</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabel guru -->
  <div class="table-responsive mt-4">
    <table class="table table-bordered table-striped">
      <thead class="table-primary">
        <tr>
          <th>ID</th>
          <th>Nama</th>
          <th>Mata Pelajaran</th>
          <th>Aksi</th>
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
            <td><?= htmlspecialchars($guru['nama']) ?></td>
            <td><?= $guru['mapel'] ?: '-' ?></td>

            <td>
              <a href="guru.php?edit=<?= $guru['id_guru'] ?>" class="btn btn-warning btn-sm">Edit</a>

              <a href="guru.php?hapus=<?= $guru['id_guru'] ?>"
                onclick="return confirm('Yakin ingin menghapus?')"
                class="btn btn-danger btn-sm">Hapus</a>

              <a href="guru_unavailable.php?id_guru=<?= $guru['id_guru'] ?>"
                class="btn <?= $btnColor ?> btn-sm">
                <?= $label ?>
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  document.getElementById("saveMapelBtn").addEventListener("click", function() {
    const selected = Array.from(document.querySelectorAll(".mapel-checkbox:checked"));
    const names = selected.map(el => el.nextElementSibling.textContent.trim());
    const ids = selected.map(el => el.value);

    document.getElementById("selectedMapel").textContent =
      names.length ? names.join(", ") : "Belum ada mapel dipilih";

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
</script>

</body>

</html>