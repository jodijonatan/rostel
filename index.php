<?php
include 'koneksi.php';
$pageTitle = "Dashboard Admin";
$pageLocation = "Dashboard";
include 'layout.php';

// Statistik
$total_guru = $conn->query("SELECT COUNT(*) as total FROM guru")->fetch_assoc()['total'];
$total_kelas = $conn->query("SELECT COUNT(*) as total FROM kelas")->fetch_assoc()['total'];
$total_mapel = $conn->query("SELECT COUNT(*) as total FROM mata_pelajaran")->fetch_assoc()['total'];
$total_roster = $conn->query("SELECT COUNT(*) as total FROM roster")->fetch_assoc()['total'];
?>

<div class="row mt-4">
  <div class="col-md-3">
    <div class="card text-white bg-primary mb-3">
      <div class="card-body">
        <h5 class="card-title">Guru</h5>
        <p class="card-text"><?= $total_guru ?> Guru</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-white bg-success mb-3">
      <div class="card-body">
        <h5 class="card-title">Kelas</h5>
        <p class="card-text"><?= $total_kelas ?> Kelas</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-white bg-warning mb-3">
      <div class="card-body">
        <h5 class="card-title">Mata Pelajaran</h5>
        <p class="card-text"><?= $total_mapel ?> Mapel</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-white bg-danger mb-3">
      <div class="card-body">
        <h5 class="card-title">Roster</h5>
        <p class="card-text"><?= $total_roster ?> Jadwal</p>
      </div>
    </div>
  </div>
</div>

<?php
// Tutup main content
echo "</div>";
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>