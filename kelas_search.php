<?php
include 'koneksi.php';

$keyword = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : "";
$angkatan = isset($_GET['angkatan']) ? $conn->real_escape_string($_GET['angkatan']) : "";

$query = "SELECT * FROM kelas WHERE 1";

if ($keyword !== "") {
  $query .= " AND (nama_kelas LIKE '%$keyword%' OR angkatan LIKE '%$keyword%')";
}

if ($angkatan !== "") {
  $query .= " AND angkatan = '$angkatan'";
}

$query .= " ORDER BY id_kelas DESC";
$result = $conn->query($query);

while ($kelas = $result->fetch_assoc()):
?>
  <tr>
    <td><?= $kelas['id_kelas'] ?></td>
    <td><?= htmlspecialchars($kelas['nama_kelas']) ?></td>
    <td><?= htmlspecialchars($kelas['angkatan']) ?></td>
    <td>
      <a href="kelas.php?edit=<?= $kelas['id_kelas'] ?>" class="btn btn-warning btn-sm">Edit</a>
      <a href="kelas.php?hapus=<?= $kelas['id_kelas'] ?>"
        onclick="return confirm('Yakin ingin menghapus kelas ini?')"
        class="btn btn-danger btn-sm">Hapus</a>
    </td>
  </tr>
<?php endwhile; ?>