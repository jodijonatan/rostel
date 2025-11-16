<?php
require __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Pastikan koneksi.php sudah tersedia
include 'koneksi.php';

$angkatan = isset($_GET['angkatan']) ? $_GET['angkatan'] : "";
$title_angkatan = $angkatan ? "Angkatan " . htmlspecialchars($angkatan) : "Semua Angkatan";

// --- DAFTAR WAKTU DAN HARI ---
// List dipertahankan, penambahan 'text_color' untuk styling
$lesList = [
  '07:15 - 08:00' => ['type' => 'UPACARA', 'data' => 'UPACARA / APEL PAGI', 'color' => '#8B4513', 'text_color' => 'white'],
  '08:00 - 08:30' => ['type' => 'ROSTER', 'les' => 1, 'mulai' => '08:00'],
  '08:30 - 09:00' => ['type' => 'ROSTER', 'les' => 2, 'mulai' => '08:30'],
  '09:00 - 09:30' => ['type' => 'ROSTER', 'les' => 3, 'mulai' => '09:00'],
  '09:30 - 10:00' => ['type' => 'ROSTER', 'les' => 4, 'mulai' => '09:30'],
  '10:00 - 10:30' => ['type' => 'BREAK', 'data' => 'I S T I R A H A T I', 'color' => '#4CAF50', 'text_color' => 'white'],
  '10:30 - 11:00' => ['type' => 'ROSTER', 'les' => 5, 'mulai' => '10:30'],
  '11:00 - 11:30' => ['type' => 'ROSTER', 'les' => 6, 'mulai' => '11:00'],
  '11:30 - 12:00' => ['type' => 'ROSTER', 'les' => 7, 'mulai' => '11:30'],
  '12:00 - 12:30' => ['type' => 'ROSTER', 'les' => 8, 'mulai' => '12:00'],
  '12:30 - 13:20' => ['type' => 'BREAK', 'data' => 'I S T I R A H A T II / SHOLAT', 'color' => '#2196F3', 'text_color' => 'white'],
  '13:20 - 13:50' => ['type' => 'ROSTER', 'les' => 9, 'mulai' => '13:20'],
  '13:50 - 14:20' => ['type' => 'ROSTER', 'les' => 10, 'mulai' => '13:50'],
  '14:20 - 14:50' => ['type' => 'ROSTER', 'les' => 11, 'mulai' => '14:20'],
  '14:50 - 15:20' => ['type' => 'ROSTER', 'les' => 12, 'mulai' => '14:50'],
];

$hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

// --- 1. AMBIL DAN SUSUN DATA ROSTER ---
$query = "
    SELECT r.hari, r.jam_mulai, k.nama_kelas,
           m.nama AS mapel, g.nama AS guru
    FROM roster r
    JOIN guru g ON r.id_guru = g.id_guru
    JOIN mata_pelajaran m ON r.id_mapel = m.id_mapel
    JOIN kelas k ON r.id_kelas = k.id_kelas
    " . ($angkatan !== "" ? " WHERE k.angkatan = '$angkatan'" : "");

$result = $conn->query($query);

$rosterData = [];
while ($r = $result->fetch_assoc()) {
  $rosterData[$r['hari']][$r['jam_mulai']][$r['nama_kelas']] = [
    'mapel' => $r['mapel'],
    'guru' => $r['guru']
  ];
}

// Ambil semua kelas yang relevan
$kelasQuery = "SELECT nama_kelas FROM kelas " . ($angkatan !== "" ? " WHERE angkatan = '$angkatan'" : "") . " ORDER BY angkatan, nama_kelas";
$kelasList = $conn->query($kelasQuery)->fetch_all(MYSQLI_ASSOC);

if (empty($kelasList)) {
  $html = "<h1>Tidak ada data Roster atau Kelas untuk {$title_angkatan}.</h1>";
} else {

  // Hitung jumlah kolom kelas untuk colspan
  $kelasColSpan = count($kelasList) * 2;

  // --- 2. CSS KECIL & MODERN (Fokus Satu Halaman) ---
  $html = "
    <style>
        /* CSS Dikecilkan untuk memaksa muat dalam satu halaman A3 Landscape */
        body { 
            font-family: 'Helvetica', sans-serif; 
            font-size: 6px; /* SANGAT KECIL */
            margin: 0; 
            padding: 0;
        }

        /* HEADER */
        .header {
            background-color: #007bff; 
            color: white;
            padding: 5px; 
            text-align: center;
            margin-bottom: 5px; 
        }
        .header h1 { margin: 0; font-size: 14px; font-weight: bold;} 
        .header h3 { margin: 2px 0 0 0; font-size: 8px; font-weight: normal; opacity: 0.9;}

        /* TABEL */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed;
        }
        
        th, td { 
            border: 1px solid #ddd;
            padding: 2px 1px; /* KECIL */
            text-align: center; 
            vertical-align: middle;
            word-wrap: break-word;
            line-height: 1; /* PADAT */
        }
        
        /* HEADER UTAMA */
        thead th { 
            background: #2c3e50; 
            color: white; 
            font-weight: 700;
        }
        
        /* KOLOM KHUSUS */
        .header-col { width: 3%; background: #2c3e50; color: white; vertical-align: middle; } 
        .header-waktu { width: 8%; background: #ecf0f1; font-weight: bold; color: #34495e; font-size: 6px; }
        
        /* HEADER KELAS DAN DETAIL */
        .header-kelas { background: #3498db; color: white; font-size: 7px; }
        .header-mapel-guru { background: #bdc3c7; color: #34495e; font-weight: bold; font-size: 6px;}
        
        /* CSS UNTUK ROTASI TEKS HARI */
        .rotated-text {
            transform: rotate(-90deg); 
            transform-origin: 50% 50%;
            width: 100%;
            white-space: nowrap;
            display: block;
            margin: 0;
            padding: 0;
            font-size: 8px; 
            font-weight: bold;
        }
        
        /* Baris Khusus (Upacara/Istirahat) */
        .special-row td {
            color: var(--text-color, white);
            font-weight: bold;
            text-align: center !important;
            height: 10px; /* SANGAT KECIL */
            font-size: 8px; 
        }
        
        /* Sel Roster */
        .roster-cell { padding: 2px 1px; height: 20px; background-color: white;} /* KECIL */
        .roster-mapel { font-weight: 700; font-size: 7px; } 
        .roster-guru { font-size: 5px; color: #555; display: block; } /* PALING KECIL */
        .roster-empty { background-color: #f7f7f7; }
        
    </style>

    <div class='header'>
        <h1>JADWAL PELAJARAN SMK</h1>
        <h3>Roster Kelas - {$title_angkatan}</h3>
    </div>

    <table>
        <thead>
            <tr>
                <th class='header-col' rowspan='3'>HARI</th> 
                <th class='header-waktu' rowspan='3'>WAKTU</th>
                
                <th colspan='{$kelasColSpan}' class='header-kelas'>KELAS</th>
            </tr>
            <tr>
                " . implode("", array_map(function ($k) {
    return "<th class='header-mapel-guru' colspan='2'>{$k['nama_kelas']}</th>";
  }, $kelasList)) . "
            </tr>
            <tr>
                <th colspan='{$kelasColSpan}'>
                    " . str_repeat("<th class='header-mapel-guru' style='background:#bdc3c7; width: 45%;'>MAPEL</th><th class='header-mapel-guru' style='background:#f1f3f4; width: 55%;'>GURU</th>", count($kelasList)) . "
                </th>
            </tr>
        </thead>
        <tbody>
    ";

  // --- 3. GENERATE ISI TABEL ---

  foreach ($hariList as $hari):
    $isFirstRow = true;

    foreach ($lesList as $waktu => $lesData):
      $jam_mulai = $lesData['mulai'] ?? explode(' - ', $waktu)[0];

      $html .= "<tr>";

      // Baris Hari
      if ($isFirstRow) {
        // Baris pertama hari ini akan memiliki rowspan sepanjang jumlah jam
        $html .= "<td class='header-col' rowspan='" . count($lesList) . "'>
                                <div class='rotated-text'>{$hari}</div>
                            </td>";
        $isFirstRow = false;
      }

      // Baris Khusus (UPACARA, ISTIRAHAT)
      if ($lesData['type'] !== 'ROSTER') {
        // Colspan = kolom waktu (1) + kolom kelas (kelasColSpan)
        $spanCols = $kelasColSpan + 1;
        $html .= "
                    <td colspan='{$spanCols}' class='special-row' style='background-color: {$lesData['color']} !important; color: {$lesData['text_color']} !important;'>
                        {$waktu} &bull; {$lesData['data']}
                    </td>
                </tr>";
        continue;
      }

      // Baris Roster Normal
      $html .= "<td class='header-waktu'>{$waktu}</td>";

      // Kolom Data Roster (Mapel dan Guru untuk setiap kelas)
      foreach ($kelasList as $kelas):
        $dataCell = $rosterData[$hari][$jam_mulai][$kelas['nama_kelas']] ?? null;

        if ($dataCell) {
          // Sel Mapel (latar putih) dan Sel Guru (latar abu muda)
          $html .= "<td class='roster-cell roster-mapel' style='background-color: white;'>{$dataCell['mapel']}</td>";
          $html .= "<td class='roster-cell roster-guru roster-empty' style='background-color: #f1f3f4;'>{$dataCell['guru']}</td>";
        } else {
          // Gabungkan dua sel kosong menjadi satu dengan colspan=2 untuk kerapian
          $html .= "<td colspan='2' class='roster-empty'></td>";
        }
      endforeach;

      $html .= "</tr>";

    endforeach;
  endforeach;


  $html .= "</tbody></table>";
}

// --- 4. KONFIGURASI DAN RENDER PDF ---
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
// Pastikan kertas A3 Landscape
$dompdf->setPaper('A3', 'landscape');
$dompdf->render();

$dompdf->stream("roster_{$angkatan}.pdf", ["Attachment" => true]);
