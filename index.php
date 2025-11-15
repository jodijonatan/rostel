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

<!-- Sambutan Admin -->
<div class="p-5 mb-4 bg-info rounded-3 text-center">
  <h1 class="display-5 fw-bold">Selamat Datang, Admin!</h1>
  <p class="col-md-8 mx-auto">Kelola roster dengan mudah melalui website ini</p>
</div>

<!-- Statistik Cards -->
<div class="row g-4">
  <div class="col-md-3">
    <a href="guru.php" class="text-decoration-none">
      <div class="card text-white bg-primary h-100">
        <div class="card-body text-center">
          <h5 class="card-title">Guru</h5>
          <p class="card-text fs-4"><?= $total_guru ?> Guru</p>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="kelas.php" class="text-decoration-none">
      <div class="card text-white bg-success h-100">
        <div class="card-body text-center">
          <h5 class="card-title">Kelas</h5>
          <p class="card-text fs-4"><?= $total_kelas ?> Kelas</p>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="mapel.php" class="text-decoration-none">
      <div class="card text-white bg-warning h-100">
        <div class="card-body text-center">
          <h5 class="card-title">Mata Pelajaran</h5>
          <p class="card-text fs-4"><?= $total_mapel ?> Mapel</p>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="roster_view.php" class="text-decoration-none">
      <div class="card text-white bg-danger h-100">
        <div class="card-body text-center">
          <h5 class="card-title">Roster</h5>
          <p class="card-text fs-4"><?= $total_roster ?> Jadwal</p>
        </div>
      </div>
    </a>
  </div>
</div>

<?php
// Tutup main content
echo "</div>";
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>