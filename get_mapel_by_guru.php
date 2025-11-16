<?php
include 'koneksi.php';
header('Content-Type: application/json; charset=utf-8');

// Inisialisasi array data kosong untuk memastikan output JSON selalu valid
$data = [];

try {
  // 1. Cek Parameter
  if (!isset($_GET['id_guru']) || !is_numeric($_GET['id_guru'])) {
    // Jika parameter tidak ada atau tidak valid, kirim array kosong
    echo json_encode($data);
    exit;
  }

  $id_guru = (int)$_GET['id_guru'];

  // 2. Query menggunakan Prepared Statement
  $query = "
        SELECT m.id_mapel, m.nama
        FROM guru_mapel gm
        JOIN mata_pelajaran m ON gm.id_mapel = m.id_mapel
        WHERE gm.id_guru = ?
        ORDER BY m.nama
    ";

  $stmt = $conn->prepare($query);

  if ($stmt === false) {
    throw new Exception("Prepare statement failed: " . $conn->error);
  }

  $stmt->bind_param("i", $id_guru);
  $stmt->execute();
  $result = $stmt->get_result();

  // 3. Ambil Hasil
  while ($row = $result->fetch_assoc()) {
    $data[] = $row;
  }

  $stmt->close();
} catch (Throwable $e) {
  // Tangkap error database atau eksekusi, dan set respons error
  http_response_code(500);
  $data = [
    'error' => true,
    'message' => 'Server error: ' . $e->getMessage()
  ];
}

// 4. Output JSON
echo json_encode($data);
exit;
