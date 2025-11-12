<?php
include 'koneksi.php';
$pageTitle = "Manajemen Roster";
$pageLocation = "Roster";
include 'layout.php';

// Daftar les (mulai dan selesai)
$lesList = [
  'Les 1' => ['07:30', '08:00'],
  'Les 2' => ['08:00', '08:30'],
  'Les 3' => ['08:30', '09:00'],
  'Les 4' => ['09:00', '09:30'],
  'Les 5' => ['09:30', '10:00'],
  'Les 6' => ['10:00', '10:30'],
  'Les 7' => ['10:30', '11:00'],
  'Les 8' => ['11:00', '11:30']
];

// Ambil data untuk dropdown
$guruList = $conn->query("SELECT * FROM guru");
$kelasList = $conn->query("SELECT * FROM kelas");
$mapelList = $conn->query("SELECT * FROM mata_pelajaran");

// Tambah roster
if (isset($_POST['tambah'])) {
  $id_guru = $_POST['id_guru'];
  $id_mapel = $_POST['id_mapel'];
  $id_kelas = $_POST['id_kelas'];
  $hari = $_POST['hari'];
  $les = $_POST['les'];
  $jam_mulai = $lesList[$les][0];
  $jam_selesai = $lesList[$les][1];

  // Validasi double-booking guru
  $cek = $conn->query("SELECT * FROM roster WHERE id_guru=$id_guru AND hari='$hari' AND NOT (jam_selesai<='$jam_mulai' OR jam_mulai>='$jam_selesai')");
  if ($cek->num_rows > 0) {
    $error = "Guru sudah memiliki jadwal di waktu ini!";
  } else {
    $stmt = $conn->prepare("INSERT INTO roster (id_guru,id_mapel,id_kelas,hari,jam_mulai,jam_selesai) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("iiisss", $id_guru, $id_mapel, $id_kelas, $hari, $jam_mulai, $jam_selesai);
    $stmt->execute();
    header("Location: roster.php");
    exit;
  }
}

// Ambil data roster
$roster = $conn->query("SELECT r.id_roster,g.nama AS guru,m.nama AS mapel,k.nama_kelas,r.hari,r.jam_mulai,r.jam_selesai
    FROM roster r
    JOIN guru g ON r.id_guru=g.id_guru
    JOIN mata_pelajaran m ON r.id_mapel=m.id_mapel
    JOIN kelas k ON r.id_kelas=k.id_kelas
");
?>

<div class="container-fluid">
  <h2>Manajemen Roster</h2>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <!-- Form Tambah Roster -->
  <form method="post" class="mb-4">
    <div class="row g-2">
      <div class="col-md-2">
        <select name="id_guru" class="form-control" required>
          <option value="">Pilih Guru</option>
          <?php $guruList->data_seek(0);
          while ($g = $guruList->fetch_assoc()): ?>
            <option value="<?= $g['id_guru'] ?>"><?= $g['nama'] ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="id_mapel" class="form-control" required>
          <option value="">Pilih Mapel</option>
          <?php $mapelList->data_seek(0);
          while ($m = $mapelList->fetch_assoc()): ?>
            <option value="<?= $m['id_mapel'] ?>"><?= $m['nama'] ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="id_kelas" class="form-control" required>
          <option value="">Pilih Kelas</option>
          <?php $kelasList->data_seek(0);
          while ($k = $kelasList->fetch_assoc()): ?>
            <option value="<?= $k['id_kelas'] ?>"><?= $k['nama_kelas'] ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="hari" class="form-control" required>
          <option value="">Pilih Hari</option>
          <option value="Senin">Senin</option>
          <option value="Selasa">Selasa</option>
          <option value="Rabu">Rabu</option>
          <option value="Kamis">Kamis</option>
          <option value="Jumat">Jumat</option>
        </select>
      </div>
      <div class="col-md-2">
        <select name="les" class="form-control" required>
          <option value="">Pilih Les</option>
          <?php foreach ($lesList as $les => $jam): ?>
            <option value="<?= $les ?>"><?= $les ?> (<?= $jam[0] ?> - <?= $jam[1] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" name="tambah" class="btn btn-primary w-100">Tambah Roster</button>
      </div>
    </div>
  </form>

  <!-- Tabel Roster -->
  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead class="table-primary">
        <tr>
          <th>ID</th>
          <th>Guru</th>
          <th>Mapel</th>
          <th>Kelas</th>
          <th>Hari</th>
          <th>Jam Mulai</th>
          <th>Jam Selesai</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($r = $roster->fetch_assoc()): ?>
          <tr>
            <td><?= $r['id_roster'] ?></td>
            <td><?= $r['guru'] ?></td>
            <td><?= $r['mapel'] ?></td>
            <td><?= $r['nama_kelas'] ?></td>
            <td><?= $r['hari'] ?></td>
            <td><?= $r['jam_mulai'] ?></td>
            <td><?= $r['jam_selesai'] ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
echo "</div>"; // tutup main-content
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>