<?php
// Tentukan judul halaman (dinamis)
if (!isset($pageTitle)) $pageTitle = "Rostel";

// Tentukan lokasi halaman untuk header
if (!isset($pageLocation)) $pageLocation = "Dashboard";

// Tentukan ikon untuk setiap halaman (untuk navigasi yang lebih menarik)
$navItems = [
  'Dashboard' => ['url' => 'index.php', 'icon' => 'fa-tachometer-alt'],
  'Guru' => ['url' => 'guru.php', 'icon' => 'fa-chalkboard-teacher'],
  'Kelas' => ['url' => 'kelas.php', 'icon' => 'fa-school'],
  'Mata Pelajaran' => ['url' => 'mapel.php', 'icon' => 'fa-book'],
  'Roster' => ['url' => 'roster_view.php', 'icon' => 'fa-calendar-alt'],
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <style>
    :root {
      --sidebar-bg: #27374D;
      --sidebar-hover-bg: #405167;
      --main-text-color: #34495e;
    }

    body {
      min-height: 100vh;
      margin: 0;
      font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background-color: #f4f7f6;
    }

    /* Styling Sidebar */
    .sidebar {
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      width: 250px;
      background-color: var(--sidebar-bg);
      color: white;
      padding: 20px 0;
      z-index: 1000;
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
      transition: width 0.3s;
    }

    .sidebar-header {
      color: #ecf0f1;
      text-transform: uppercase;
      font-weight: 700;
      letter-spacing: 1px;
      padding: 0 20px 20px 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 15px;
    }

    .sidebar a {
      display: flex;
      align-items: center;
      color: rgba(255, 255, 255, 0.85);
      text-decoration: none;
      padding: 12px 20px;
      margin: 0 10px 5px 10px;
      border-radius: 8px;
      transition: background-color 0.3s, color 0.3s;
    }

    .sidebar a i {
      width: 30px;
      text-align: center;
      margin-right: 10px;
    }

    .sidebar a.active,
    .sidebar a:hover {
      background-color: var(--sidebar-hover-bg);
      color: white;
      font-weight: 600;
    }

    /* Styling Header */
    .header {
      height: 70px;
      display: flex;
      align-items: center;
      padding-left: 270px;
      padding-right: 20px;
      background-color: #ffffff;
      border-bottom: 1px solid #e0e0e0;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 500;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .header strong {
      font-size: 1.5rem;
      color: var(--main-text-color);
    }

    /* Styling Main Content */
    .main-content {
      margin-left: 250px;
      padding: 100px 30px 30px 30px;
      min-height: 100vh;
    }

    /* Responsif untuk Mobile */
    @media (max-width: 992px) {
      .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding-top: 10px;
        box-shadow: none;
      }

      .header {
        padding-left: 20px;
        position: relative;
        height: auto;
        line-height: normal;
        display: block;
        padding: 15px 20px;
      }

      .main-content {
        margin-left: 0;
        padding: 30px 20px;
      }
    }
  </style>
</head>

<body>
  <div class="sidebar d-flex flex-column">
    <div class="sidebar-header">
      ROSTEL APP
    </div>

    <nav class="flex-grow-1">
      <?php foreach ($navItems as $name => $item): ?>
        <a href="<?= $item['url'] ?>" class="nav-link <?= ($pageLocation == $name) ? "active" : "" ?>">
          <i class="fas <?= $item['icon'] ?>"></i>
          <?= $name ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="p-3 text-center small" style="color: rgba(255, 255, 255, 0.5);">
      &copy; <?= date('Y') ?> Rostel by Jodi Jonatan. All right reversed
    </div>
  </div>

  <div class="header">
    <strong><?= $pageLocation ?></strong>
  </div>

  <div class="main-content">