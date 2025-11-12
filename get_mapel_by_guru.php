<?php
include 'koneksi.php';

if (isset($_GET['id_guru'])) {
  $id_guru = intval($_GET['id_guru']);

  $query = "
    SELECT m.id_mapel, m.nama 
    FROM guru_mapel gm
    JOIN mata_pelajaran m ON gm.id_mapel = m.id_mapel
    WHERE gm.id_guru = ?
    ORDER BY m.nama
  ";

  $stmt = $conn->prepare($query);
  $stmt->bind_param("i", $id_guru);
  $stmt->execute();
  $result = $stmt->get_result();

  $mapel = [];
  while ($row = $result->fetch_assoc()) {
    $mapel[] = $row;
  }

  header('Content-Type: application/json');
  echo json_encode($mapel);
}
