<?php
include 'koneksi.php';
header('Content-Type: application/json; charset=utf-8');

// Container untuk output
$response = [
  'available_guru' => [],
  'current_data' => null
];

try {
  // 1. Validasi dan Ambil Input
  $hari = $_GET['hari'] ?? null;
  $jam_mulai = $_GET['jam_mulai'] ?? null;
  $id_roster = $_GET['id_roster'] ?? 0; // Input untuk mode EDIT

  if (!$hari || !$jam_mulai) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Parameter hari atau jam_mulai tidak lengkap.']);
    exit;
  }

  // 2. Map Jam Mulai -> Jam Akhir (Sesuaikan dengan data yang Anda gunakan di DB)
  $les = [
    '07:30' => '08:00',
    '08:00' => '08:30',
    '08:30' => '09:00',
    '09:00' => '09:30',
    '09:30' => '10:00',
    '10:00' => '10:30',
    '10:30' => '11:00',
    '11:00' => '11:30',
    '11:30' => '12:00',
    '12:00' => '12:30',
    '12:30' => '13:00',
    '13:00' => '13:30',
    '13:30' => '14:00',
    '14:00' => '14:30',
    '14:30' => '15:00',
    '15:00' => '15:30',
  ];

  if (!isset($les[$jam_mulai])) {
    // Jam tidak dikenali
    echo json_encode($response);
    exit;
  }

  $jam_akhir = $les[$jam_mulai];

  // 3. Ambil Data Roster Saat Ini (Jika Mode Edit)
  if ($id_roster > 0) {
    $sql_current = "
            SELECT r.id_guru, r.id_mapel, g.nama AS guru_nama, m.nama AS mapel_nama
            FROM roster r
            JOIN guru g ON r.id_guru = g.id_guru
            JOIN mata_pelajaran m ON r.id_mapel = m.id_mapel
            WHERE r.id_roster = ?
        ";
    $stmt_current = $conn->prepare($sql_current);
    $stmt_current->bind_param("i", $id_roster);
    $stmt_current->execute();
    $res_current = $stmt_current->get_result();
    $response['current_data'] = $res_current->fetch_assoc();
    $stmt_current->close();
  }

  // 4. Ambil semua guru
  $allGuruStmt = $conn->prepare("SELECT id_guru, nama FROM guru ORDER BY nama");
  $allGuruStmt->execute();
  $allGuruRes = $allGuruStmt->get_result();
  $allGuru = $allGuruRes->fetch_all(MYSQLI_ASSOC);
  $allGuruStmt->close();

  // 5. Siapkan Statement Pengecekan

  // Cek Unavailable Conflict
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

  // Cek Roster Conflict (EDIT: id_roster != ?)
  $sql_roster = "
        SELECT 1 FROM roster
        WHERE id_guru = ?
          AND hari = ?
          AND NOT (jam_selesai <= ? OR jam_mulai >= ?)
          AND id_roster != ? 
        LIMIT 1
    ";
  $stmt_ro = $conn->prepare($sql_roster);
  if ($stmt_ro === false) throw new Exception("Prepare failed (roster)");


  // 6. Loop dan Filter Guru
  foreach ($allGuru as $g) {
    $id_guru = (int)$g['id_guru'];

    // Cek 1: UNAVAILABLE
    $stmt_un->bind_param("isss", $id_guru, $hari, $jam_mulai, $jam_akhir);
    $stmt_un->execute();
    $res_un = $stmt_un->get_result();
    if ($res_un->num_rows > 0) {
      continue;
    }

    // Cek 2: ROSTER CONFLICT (Mengabaikan jadwal yang sedang diedit/id_roster)
    $stmt_ro->bind_param("isssi", $id_guru, $hari, $jam_mulai, $jam_akhir, $id_roster);
    $stmt_ro->execute();
    $res_ro = $stmt_ro->get_result();
    if ($res_ro->num_rows > 0) {
      continue;
    }

    // Lulus semua pengecekan -> tersedia
    $response['available_guru'][] = $g;
  }

  // 7. Tutup statement dan kembalikan output
  $stmt_un->close();
  $stmt_ro->close();

  echo json_encode($response);
  exit;
} catch (Throwable $e) {
  // Tangkap semua error dan kembalikan JSON yang rapi
  http_response_code(500);
  echo json_encode([
    'error' => true,
    'message' => 'Server error: ' . $e->getMessage()
  ]);
  exit;
}
