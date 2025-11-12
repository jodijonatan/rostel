<?php
include 'koneksi.php';

if (isset($_GET['hari']) && isset($_GET['jam_mulai'])) {
  $hari = $_GET['hari'];
  $jam_mulai = $_GET['jam_mulai'];

  $query = "
    SELECT g.id_guru, g.nama
    FROM guru g
    WHERE g.id_guru NOT IN (
      SELECT id_guru FROM roster
      WHERE hari = ? AND jam_mulai = ?
    )
    ORDER BY g.nama
  ";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ss", $hari, $jam_mulai);
  $stmt->execute();
  $result = $stmt->get_result();

  $data = [];
  while ($row = $result->fetch_assoc()) {
    $data[] = $row;
  }

  header('Content-Type: application/json');
  echo json_encode($data);
}
