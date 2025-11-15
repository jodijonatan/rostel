<?php
require __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

include 'koneksi.php';

$angkatan = isset($_GET['angkatan']) ? $_GET['angkatan'] : "";

// Ambil data roster
$query = "
  SELECT r.hari, r.jam_mulai, r.jam_selesai,
         k.nama_kelas, k.angkatan,
         m.nama AS mapel,
         g.nama AS guru
  FROM roster r
  JOIN guru g ON r.id_guru = g.id_guru
  JOIN mata_pelajaran m ON r.id_mapel = m.id_mapel
  JOIN kelas k ON r.id_kelas = k.id_kelas
";

if ($angkatan !== "") {
  $query .= " WHERE k.angkatan = '$angkatan'";
}

$query .= " ORDER BY r.hari, r.jam_mulai, k.nama_kelas";

$data = $conn->query($query);

// Siapkan HTML
$html = "
<style>
  body { font-family: sans-serif; font-size: 12px; }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  th, td { border: 1px solid #333; padding: 6px; text-align: center; }
  th { background: #f2f2f2; }
  h2 { text-align: center; margin-bottom: 10px; }
</style>

<h2>ROSTER SEKOLAH - EXPORT PDF</h2>
<h4>Angkatan: " . ($angkatan ?: "Semua") . "</h4>

<table>
  <thead>
    <tr>
      <th>Hari</th>
      <th>Jam Mulai</th>
      <th>Jam Selesai</th>
      <th>Kelas</th>
      <th>Angkatan</th>
      <th>Mata Pelajaran</th>
      <th>Guru</th>
    </tr>
  </thead>
  <tbody>
";

while ($row = $data->fetch_assoc()) {
  $html .= "
    <tr>
      <td>{$row['hari']}</td>
      <td>{$row['jam_mulai']}</td>
      <td>{$row['jam_selesai']}</td>
      <td>{$row['nama_kelas']}</td>
      <td>{$row['angkatan']}</td>
      <td>{$row['mapel']}</td>
      <td>{$row['guru']}</td>
    </tr>
  ";
}

$html .= "</tbody></table>";

// Konfigurasi PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output ke browser
$dompdf->stream("roster_{$angkatan}.pdf", ["Attachment" => true]);
