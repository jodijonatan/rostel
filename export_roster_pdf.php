<?php
require __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

include 'koneksi.php';

$angkatan = isset($_GET['angkatan']) ? $_GET['angkatan'] : "";
$title_angkatan = $angkatan ? "Angkatan " . htmlspecialchars($angkatan) : "Semua Angkatan";

// Daftar jam pelajaran (HARUS SINKRON dengan roster.php)
$lesList = [
  ['07:30', '08:00'],
  ['08:00', '08:30'],
  ['08:30', '09:00'],
  ['09:00', '09:30'],
  ['09:30', '10:00'],
  ['10:00', '10:30'],
  ['10:30', '11:00'],
  ['11:00', '11:30'],
  ['11:30', '12:00'],
  ['12:00', '12:30'],
  ['12:30', '13:00'],
  ['13:00', '13:30'],
  ['13:30', '14:00'],
  ['14:00', '14:30'],
  ['14:30', '15:00'],
  ['15:00', '15:30'],
];

// Query Roster dan Kelas
$query = "
    SELECT r.hari, r.jam_mulai, k.nama_kelas, m.nama AS mapel, g.nama AS guru
    FROM roster r
    JOIN guru g ON r.id_guru = g.id_guru
    JOIN mata_pelajaran m ON r.id_mapel = m.id_mapel
    JOIN kelas k ON r.id_kelas = k.id_kelas
    " . ($angkatan !== "" ? " WHERE k.angkatan = '$angkatan'" : "");

$result = $conn->query($query);

// Susun data ke format Roster (Hari -> Jam -> Kelas)
$rosterData = [];
while ($r = $result->fetch_assoc()) {
  $jamKey = substr($r['jam_mulai'], 0, 5);
  $rosterData[$r['hari']][$jamKey][$r['nama_kelas']] =
    "<b>{$r['mapel']}</b><br><span style='font-size: 8px;'>{$r['guru']}</span>";
}

// Ambil semua kelas yang relevan
$kelasQuery = "SELECT nama_kelas FROM kelas " . ($angkatan !== "" ? " WHERE angkatan = '$angkatan'" : "") . " ORDER BY nama_kelas";
$kelasList = $conn->query($kelasQuery)->fetch_all(MYSQLI_ASSOC);

// Jika tidak ada data kelas, hentikan
if (empty($kelasList)) {
  $html = "<h1>Tidak ada data Roster atau Kelas untuk {$title_angkatan}.</h1>";
  // Lanjutkan ke PDF render
} else {

  // =========================================================================
  // HTML dan CSS Baru
  // =========================================================================

  $html = "
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; margin: 0; padding: 0;}
        
        /* HEADER */
        .header {
            background-color: #34495e; /* Warna biru tua */
            color: white;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        .header h3 {
            margin: 5px 0 0 0;
            font-size: 12px;
            font-style: italic;
        }

        /* TABLE STYLING */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        th, td { 
            border: 1px solid #ddd; 
            padding: 5px 8px; 
            text-align: center; 
            height: 40px;
            vertical-align: top;
        }
        
        /* THEAD - Judul Kolom */
        thead th { 
            background: #4682B4; /* Biru muda yang elegan */
            color: white; 
            border-color: #4682B4; 
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            vertical-align: middle;
        }
        
        /* Kolom Jam/Hari */
        .time-cell {
            background: #f0f3f6; /* Latar belakang abu-abu terang */
            font-weight: bold;
            color: #555;
            width: 80px; /* Lebar tetap */
            vertical-align: middle;
            text-align: left;
            border-right: 2px solid #ddd;
        }
        
        /* Isi Sel Roster */
        td {
            background-color: #fff;
        }
        
        td:not(.time-cell):empty {
            background-color: #fcfcfc; /* Sel kosong sedikit berbeda */
        }
        
        td span {
            color: #666; /* Guru agak abu-abu */
        }
        
        .day-separator {
            background-color: #dcdcdc; /* Pembatas hari */
            font-weight: bold;
            text-align: left;
            padding: 3px 8px !important;
            border: none;
        }
    </style>

    <div class='header'>
        <h1>JADWAL PELAJARAN SEKOLAH</h1>
        <h3>Roster Kelas - {$title_angkatan}</h3>
    </div>

    <table>
        <thead>
            <tr>
                <th class='time-cell'>Jam Pelajaran</th>
                " . implode("", array_map(fn($k) => "<th>{$k['nama_kelas']}</th>", $kelasList)) . "
            </tr>
        </thead>
        <tbody>
    ";

  $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

  foreach ($hariList as $hari):
    // Pembatas Hari
    $html .= "
            <tr>
                <td colspan='" . (count($kelasList) + 1) . "' class='day-separator'>
                    {$hari}
                </td>
            </tr>
        ";

    foreach ($lesList as $jam):
      $jamKey = $jam[0];
      $html .= "<tr>";

      // Kolom Jam
      $html .= "<td class='time-cell'>{$jam[0]} - {$jam[1]}</td>";

      // Kolom Kelas
      foreach ($kelasList as $kelas):
        $isi = $rosterData[$hari][$jamKey][$kelas['nama_kelas']] ?? '';
        $html .= "<td>{$isi}</td>";
      endforeach;

      $html .= "</tr>";
    endforeach;
  endforeach;

  $html .= "</tbody></table>";
}

// Konfigurasi PDF
$options = new Options();
// Wajib di set jika ingin menggunakan CSS/HTML yang kompleks
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
// Mengatur orientasi landscape agar tabel lebar lebih muat
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Output ke browser
$dompdf->stream("roster_{$angkatan}.pdf", ["Attachment" => true]);
