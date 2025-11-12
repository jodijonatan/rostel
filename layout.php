<?php
// Tentukan judul halaman (dinamis)
if (!isset($pageTitle)) $pageTitle = "Rostel";

// Tentukan lokasi halaman untuk header
if (!isset($pageLocation)) $pageLocation = "Dashboard";
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      min-height: 100vh;
      margin: 0;
      font-family: Arial, sans-serif;
    }

    .sidebar {
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      width: 220px;
      background-color: #0d6efd;
      color: white;
      padding-top: 60px;
      z-index: 1000;
    }

    .sidebar h4 {
      padding: 0 15px;
    }

    .sidebar a {
      display: block;
      color: white;
      text-decoration: none;
      padding: 10px 15px;
      border-radius: 4px;
      margin-bottom: 5px;
    }

    .sidebar a.active,
    .sidebar a:hover {
      background-color: #0b5ed7;
      text-decoration: none;
    }

    .header {
      height: 60px;
      line-height: 60px;
      padding-left: 240px;
      padding-right: 20px;
      background-color: #f8f9fa;
      border-bottom: 1px solid #dee2e6;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 500;
    }

    .main-content {
      margin-left: 220px;
      padding: 80px 20px 20px 20px;
      /* Tambah padding top supaya tidak tertutup header */
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding-top: 10px;
      }

      .header {
        padding-left: 20px;
      }

      .main-content {
        margin-left: 0;
        padding-top: 80px;
      }
    }
  </style>
</head>

<body>
  <!-- Sidebar -->
  <div class="sidebar d-flex flex-column position-fixed">
    <h4 class="text-center mb-4">Rostel</h4>
    <a href="index.php" class="<?= ($pageLocation == "Dashboard") ? "active" : "" ?>">Dashboard</a>
    <a href="guru.php" class="<?= ($pageLocation == "Guru") ? "active" : "" ?>">Guru</a>
    <a href="kelas.php" class="<?= ($pageLocation == "Kelas") ? "active" : "" ?>">Kelas</a>
    <a href="mapel.php" class="<?= ($pageLocation == "Mata Pelajaran") ? "active" : "" ?>">Mata Pelajaran</a>
    <a href="roster_view.php" class="<?= ($pageLocation == "Roster") ? "active" : "" ?>">Roster</a>
  </div>

  <!-- Header -->
  <div class="header">
    <strong><?= $pageLocation ?></strong>
  </div>

  <!-- Main content -->
  <div class="main-content">