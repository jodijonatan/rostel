<?php
include 'koneksi.php';
header('Content-Type: application/json; charset=utf-8');

// Kontainer respons JSON
$response = [
  'status' => 'error',
  'message' => 'Aksi gagal atau tidak dikenali.'
];

if (isset($_POST['simpan'])) {
  // 1. Ambil dan Bersihkan Input
  $roster_id = (int)($_POST['roster_id'] ?? 0); // Ambil ID Roster (0 jika mode tambah)
  $hari = $_POST['hari'] ?? '';
  $jam_mulai = $_POST['jam_mulai'] ?? '';
  $id_kelas = (int)($_POST['id_kelas'] ?? 0);
  $id_guru = (int)($_POST['id_guru'] ?? 0);
  $id_mapel = (int)($_POST['id_mapel'] ?? 0);

  // 2. Map Jam Mulai -> Jam Selesai (HARUS SINKRON dengan roster.php)
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

  $jam_selesai = $les[$jam_mulai] ?? null;

  if (!$jam_selesai || $id_kelas === 0 || $id_guru === 0 || $id_mapel === 0) {
    $response['message'] = "Data tidak lengkap atau jam pelajaran tidak valid.";
    echo json_encode($response);
    exit;
  }

  // 3. Pengecekan Konflik Guru (Roster Conflict)
  // Cek apakah guru sudah punya jadwal lain pada waktu ini (mengabaikan dirinya sendiri jika mode update)
  $sql_conflict = "
        SELECT id_roster FROM roster
        WHERE id_guru = ? AND hari = ?
        AND NOT (jam_selesai <= ? OR jam_mulai >= ?)
        AND id_roster != ? 
        LIMIT 1
    ";

  $stmt_conflict = $conn->prepare($sql_conflict);
  $stmt_conflict->bind_param("isssi", $id_guru, $hari, $jam_mulai, $jam_selesai, $roster_id);
  $stmt_conflict->execute();

  if ($stmt_conflict->get_result()->num_rows > 0) {
    $response['message'] = "Gagal! Guru sudah memiliki jadwal lain pada slot waktu ini.";
    echo json_encode($response);
    exit;
  }
  $stmt_conflict->close();

  // 4. Pengecekan Konflik Kelas (Kelas Conflict)
  // Cek apakah kelas sudah punya jadwal lain pada waktu ini (mengabaikan dirinya sendiri jika mode update)
  // Kelas tidak boleh diisi dua kali dalam slot yang sama
  $sql_kelas_conflict = "
        SELECT id_roster FROM roster
        WHERE id_kelas = ? AND hari = ?
        AND NOT (jam_selesai <= ? OR jam_mulai >= ?)
        AND id_roster != ? 
        LIMIT 1
    ";

  $stmt_kelas_conflict = $conn->prepare($sql_kelas_conflict);
  $stmt_kelas_conflict->bind_param("isssi", $id_kelas, $hari, $jam_mulai, $jam_selesai, $roster_id);
  $stmt_kelas_conflict->execute();

  if ($stmt_kelas_conflict->get_result()->num_rows > 0) {
    $response['message'] = "Gagal! Kelas ini sudah terisi jadwal lain pada slot waktu ini.";
    echo json_encode($response);
    exit;
  }
  $stmt_kelas_conflict->close();

  // 5. Eksekusi INSERT atau UPDATE

  if ($roster_id > 0) {
    // MODE UPDATE
    $sql = "
            UPDATE roster SET 
                id_guru = ?, id_mapel = ?, id_kelas = ?, 
                hari = ?, jam_mulai = ?, jam_selesai = ?
            WHERE id_roster = ?
        ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisssi", $id_guru, $id_mapel, $id_kelas, $hari, $jam_mulai, $jam_selesai, $roster_id);
    $message_success = "Jadwal berhasil diperbarui!";
    $message_fail = "Gagal memperbarui jadwal.";
  } else {
    // MODE INSERT (TAMBAH BARU)
    $sql = "
            INSERT INTO roster (id_guru, id_mapel, id_kelas, hari, jam_mulai, jam_selesai)
            VALUES (?, ?, ?, ?, ?, ?)
        ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisss", $id_guru, $id_mapel, $id_kelas, $hari, $jam_mulai, $jam_selesai);
    $message_success = "Jadwal berhasil ditambahkan!";
    $message_fail = "Gagal menambahkan jadwal.";
  }

  if ($stmt->execute()) {
    $response['status'] = 'success';
    $response['message'] = $message_success;
  } else {
    $response['message'] = $message_fail . " Error: " . $conn->error;
  }

  $stmt->close();
}

echo json_encode($response);
exit;
