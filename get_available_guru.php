<?php
// get_available_guru.php (robust version)
// Pastikan file ini menggantikan file lama

include 'koneksi.php';
header('Content-Type: application/json; charset=utf-8');

try {
  // validasi input
  $hari = $_GET['hari'] ?? null;
  $jam_mulai = $_GET['jam_mulai'] ?? null;

  if (!$hari || !$jam_mulai) {
    echo json_encode([]); // parameter tidak lengkap -> kembalikan list kosong
    exit;
  }

  // map jam mulai -> jam akhir (sinkron dengan roster_view)
  $les = [
    '08:00' => '08:30',
    '08:30' => '09:00',
    '09:00' => '09:30',
    '09:30' => '10:00',
    '10:30' => '11:00',
    '11:00' => '11:30',
    '11:30' => '12:00',
    '12:00' => '12:30',
    '13:20' => '13:50',
    '13:50' => '14:20',
    '14:20' => '14:50',
    '14:50' => '15:20'
  ];

  if (!isset($les[$jam_mulai])) {
    // jam tidak dikenali -> tidak ada guru (atau bisa kembalikan error message)
    echo json_encode([]);
    exit;
  }

  $jam_akhir = $les[$jam_mulai];

  // Ambil semua guru
  $allGuruStmt = $conn->prepare("SELECT id_guru, nama FROM guru ORDER BY nama");
  $allGuruStmt->execute();
  $allGuruRes = $allGuruStmt->get_result();
  $allGuru = $allGuruRes->fetch_all(MYSQLI_ASSOC);
  $allGuruStmt->close();

  $available = [];

  // Siapkan statement untuk cek unavailable (full day atau overlapping)
  // Overlap test: NOT (unavailable.jam_selesai <= slot_start OR unavailable.jam_mulai >= slot_end)
  $sql_unavailable = "
        SELECT 1 FROM guru_unavailable
        WHERE id_guru = ?
          AND hari = ?
          AND (
                full_day = 1
                OR (jam_mulai IS NULL AND jam_selesai IS NULL)
                OR NOT (jam_selesai <= ? OR jam_mulai >= ?)
              )
        LIMIT 1
    ";
  $stmt_un = $conn->prepare($sql_unavailable);
  if ($stmt_un === false) throw new Exception("Prepare failed (unavailable)");

  // Siapkan statement untuk cek roster conflict (overlap)
  $sql_roster = "
        SELECT 1 FROM roster
        WHERE id_guru = ?
          AND hari = ?
          AND NOT (jam_selesai <= ? OR jam_mulai >= ?)
        LIMIT 1
    ";
  $stmt_ro = $conn->prepare($sql_roster);
  if ($stmt_ro === false) throw new Exception("Prepare failed (roster)");

  // Loop semua guru dan filter
  foreach ($allGuru as $g) {
    $id_guru = (int)$g['id_guru'];

    // cek unavailable
    $stmt_un->bind_param("isss", $id_guru, $hari, $jam_mulai, $jam_akhir);
    $stmt_un->execute();
    $res_un = $stmt_un->get_result();
    if ($res_un->num_rows > 0) {
      continue; // skip guru ini karena unavailable
    }

    // cek roster conflict
    $stmt_ro->bind_param("isss", $id_guru, $hari, $jam_mulai, $jam_akhir);
    $stmt_ro->execute();
    $res_ro = $stmt_ro->get_result();
    if ($res_ro->num_rows > 0) {
      continue; // skip guru ini karena sudah terjadwal
    }

    // lulus semua pengecekan -> tersedia
    $available[] = $g;
  }

  // tutup statement
  $stmt_un->close();
  $stmt_ro->close();

  echo json_encode($available);
  exit;
} catch (Throwable $e) {
  // tangkap semua error dan kembalikan JSON yang rapi (tanpa HTML)
  http_response_code(500);
  echo json_encode([
    'error' => true,
    'message' => 'Server error: ' . $e->getMessage()
  ]);
  exit;
}
