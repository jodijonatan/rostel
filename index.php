<?php
include 'koneksi.php';
$pageTitle = "Dashboard Admin";
$pageLocation = "Dashboard";
// Pastikan layout.php yang modern (dengan link Font Awesome) di-include
include 'layout.php';

// Statistik
$total_guru = $conn->query("SELECT COUNT(*) as total FROM guru")->fetch_assoc()['total'];
$total_kelas = $conn->query("SELECT COUNT(*) as total FROM kelas")->fetch_assoc()['total'];
$total_mapel = $conn->query("SELECT COUNT(*) as total FROM mata_pelajaran")->fetch_assoc()['total'];
$total_roster = $conn->query("SELECT COUNT(*) as total FROM roster")->fetch_assoc()['total'];
?>

<style>
  /* CSS Tambahan untuk Dashboard Modern */
  .hero-dashboard {
    background: linear-gradient(135deg, #3498db, #2c3e50);
    /* Gradient halus */
    color: white;
    padding: 4rem 2rem;
    border-radius: 15px;
    /* Lebih rounded */
    text-align: center;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    /* Shadow lebih kuat */
    margin-bottom: 3rem;
  }

  .stat-card {
    border: none;
    border-radius: 12px;
    transition: transform 0.3s, box-shadow 0.3s;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    color: var(--main-text-color);
    /* Ambil warna teks dari layout */
    text-align: left;
    position: relative;
    overflow: hidden;
  }

  .stat-card:hover {
    transform: translateY(-5px);
    /* Efek angkat saat hover */
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
  }

  .card-icon {
    font-size: 2.5rem;
    opacity: 0.2;
    /* Ikon besar sebagai watermark */
    position: absolute;
    top: 15px;
    right: 20px;
  }

  .stat-card .card-body {
    padding: 1.5rem;
  }

  .stat-card h5 {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #7f8c8d;
    /* Judul lebih kalem */
  }

  .stat-card .fs-2 {
    font-weight: 700;
    color: var(--sidebar-bg);
    /* Menggunakan warna sidebar sebagai aksen utama */
  }
</style>

<div class="hero-dashboard">
  <i class="fas fa-chart-line fa-3x mb-3 opacity-75"></i>
  <h1 class="display-5 fw-bold">Selamat Datang, Admin!</h1>
  <p class="col-md-10 mx-auto fs-5">
    Dasbor ini memberikan gambaran cepat mengenai seluruh data Rostel yang Anda kelola. Mari optimalkan jadwal pembelajaran!
  </p>
</div>

<div class="row g-4">

  <div class="col-md-3">
    <a href="guru.php" class="text-decoration-none">
      <div class="card stat-card h-100">
        <div class="card-body">
          <i class="fas fa-chalkboard-teacher card-icon text-primary"></i>
          <h5 class="card-title">Total Guru</h5>
          <p class="card-text fs-2"><?= $total_guru ?></p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-3">
    <a href="kelas.php" class="text-decoration-none">
      <div class="card stat-card h-100">
        <div class="card-body">
          <i class="fas fa-school card-icon text-success"></i>
          <h5 class="card-title">Total Kelas</h5>
          <p class="card-text fs-2"><?= $total_kelas ?></p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-3">
    <a href="mapel.php" class="text-decoration-none">
      <div class="card stat-card h-100">
        <div class="card-body">
          <i class="fas fa-book-open card-icon text-warning"></i>
          <h5 class="card-title">Total Mapel</h5>
          <p class="card-text fs-2"><?= $total_mapel ?></p>
        </div>
      </div>
    </a>
  </div>

  <div class="col-md-3">
    <a href="roster_view.php" class="text-decoration-none">
      <div class="card stat-card h-100">
        <div class="card-body">
          <i class="fas fa-calendar-alt card-icon text-danger"></i>
          <h5 class="card-title">Total Roster</h5>
          <p class="card-text fs-2"><?= $total_roster ?></p>
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