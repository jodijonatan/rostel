<?php
include 'koneksi.php';
header('Content-Type: application/json; charset=utf-8');

// Kontainer respons JSON
$response = [
  'status' => 'error',
  'message' => 'Gagal menghapus jadwal.'
];

try {
  // 1. Ambil ID Roster dari query string
  $id_roster = (int)($_GET['id_roster'] ?? 0);

  if ($id_roster === 0) {
    http_response_code(400); // Bad Request
    $response['message'] = "ID Roster tidak valid atau tidak ditemukan.";
    echo json_encode($response);
    exit;
  }

  // 2. Query DELETE menggunakan Prepared Statement
  $sql_delete = "DELETE FROM roster WHERE id_roster = ?";

  $stmt = $conn->prepare($sql_delete);

  if ($stmt === false) {
    throw new Exception("Prepare statement failed: " . $conn->error);
  }

  $stmt->bind_param("i", $id_roster);

  if ($stmt->execute()) {
    // Cek apakah ada baris yang benar-benar terhapus
    if ($stmt->affected_rows > 0) {
      $response['status'] = 'success';
      $response['message'] = 'Jadwal berhasil dihapus.';
    } else {
      $response['message'] = 'Jadwal tidak ditemukan.';
    }
  } else {
    $response['message'] = "Gagal menjalankan query: " . $stmt->error;
  }

  $stmt->close();
} catch (Throwable $e) {
  http_response_code(500); // Server Error
  $response['message'] = 'Server error: ' . $e->getMessage();
}

// 3. Output JSON
echo json_encode($response);
exit;
