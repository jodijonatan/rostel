  <?php
  require __DIR__ . '/vendor/autoload.php';

  use Dompdf\Dompdf;
  use Dompdf\Options;

  include 'koneksi.php';

  $angkatan = isset($_GET['angkatan']) ? $_GET['angkatan'] : "";
  $title_angkatan = $angkatan ? "Angkatan " . htmlspecialchars($angkatan) : "Semua Angkatan";

  // --- DAFTAR WAKTU DAN HARI (SAMA DENGAN SEBELUMNYA) ---
  $lesList = [
    '07:15 - 08:00' => ['type' => 'UPACARA', 'data' => 'UPACARA', 'color' => '#8b4513'],
    '08:00 - 08:30' => ['type' => 'ROSTER', 'les' => 1, 'mulai' => '08:00'],
    '08:30 - 09:00' => ['type' => 'ROSTER', 'les' => 2, 'mulai' => '08:30'],
    '09:00 - 09:30' => ['type' => 'ROSTER', 'les' => 3, 'mulai' => '09:00'],
    '09:30 - 10:00' => ['type' => 'ROSTER', 'les' => 4, 'mulai' => '09:30'],
    '10:00 - 10:30' => ['type' => 'ISTIRAHAT', 'data' => 'ISTIRAHAT', 'color' => '#ff8c00'],
    '10:30 - 11:00' => ['type' => 'ROSTER', 'les' => 5, 'mulai' => '10:30'],
    '11:00 - 11:30' => ['type' => 'ROSTER', 'les' => 6, 'mulai' => '11:00'],
    '11:30 - 12:00' => ['type' => 'ROSTER', 'les' => 7, 'mulai' => '11:30'],
    '12:00 - 12:30' => ['type' => 'ROSTER', 'les' => 8, 'mulai' => '12:00'],
    '12:30 - 13:20' => ['type' => 'ISTIRAHAT', 'data' => 'ISTIRAHAT', 'color' => '#ff8c00'],
    '13:20 - 13:50' => ['type' => 'ROSTER', 'les' => 9, 'mulai' => '13:20'],
    '13:50 - 14:20' => ['type' => 'ROSTER', 'les' => 10, 'mulai' => '13:50'],
    '14:20 - 14:50' => ['type' => 'ROSTER', 'les' => 11, 'mulai' => '14:20'],
    '14:50 - 15:20' => ['type' => 'ROSTER', 'les' => 12, 'mulai' => '14:50'],
  ];

  $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

  // --- 1. AMBIL DAN SUSUN DATA ROSTER ---
  // Query untuk mengambil jam_mulai saja (untuk matching)
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

    // --- 2. CSS MODERN BARU (TERMASUK ROTASI) ---
    $html = "
      <style>
          body { font-family: 'Helvetica', sans-serif; font-size: 8px; margin: 0; padding: 0;}
          
          .header {
              background-color: #34495e; 
              color: white;
              padding: 15px;
              text-align: center;
              margin-bottom: 15px;
          }
          .header h1 { margin: 0; font-size: 16px; }
          .header h3 { margin: 5px 0 0 0; font-size: 10px; }

          table { 
              width: 100%; 
              border-collapse: collapse; 
              table-layout: fixed;
          }
          
          th, td { 
              border: 1px solid #000; 
              padding: 3px; 
              text-align: center; 
              vertical-align: top;
              word-wrap: break-word;
          }
          
          /* HEADER UTAMA */
          thead th { 
              background: #4682B4; 
              color: white; 
              font-weight: 700;
              text-transform: uppercase;
          }
          
          /* Kolom Khusus */
          .header-col { width: 3%; height: 120px; background: #ddd; vertical-align: middle; } /* Ditingkatkan tingginya */
          .header-les { width: 4%; background: #ddd; }
          .header-waktu { width: 8%; background: #ddd; }
          .header-kelas { background: #f0f0f0; color: #333; font-weight: 600; }
          
          /* CSS UNTUK ROTASI TEKS HARI */
          .rotated-text {
              /* Transformasi untuk Dompdf */
              transform: rotate(-90deg); 
              transform-origin: 50% 50%; /* Titik putar di tengah */
              
              /* Properti tambahan untuk tampilan */
              width: 100%;
              white-space: nowrap;
              display: block;
              margin: 0;
              padding: 0;
              font-size: 12px;
          }
          
          /* Baris Khusus (Upacara/Istirahat) */
          .special-row td {
              color: white;
              font-weight: bold;
              text-align: center !important;
              height: 15px;
          }
          
          /* Sel Roster */
          .roster-cell { line-height: 1.2; padding: 2px; height: 35px;}
          .roster-mapel { font-weight: bold; font-size: 9px; }
          .roster-guru { font-size: 7px; color: #555; display: block; }
          
      </style>

      <div class='header'>
          <h1>JADWAL PELAJARAN SEKOLAH</h1>
          <h3>Roster Kelas - {$title_angkatan}</h3>
      </div>

      <table>
          <thead>
              <tr>
                  <th class='header-col' rowspan='3'></th> <th class='header-les' rowspan='2'>LES</th>
                  <th class='header-waktu' rowspan='2'>WAKTU</th>
                  
                  <th colspan='" . (count($kelasList) * 2) . "'>KELAS</th>
              </tr>
              <tr>
                  " . implode("", array_map(function ($k) {
      return "<th class='header-kelas' colspan='2'>{$k['nama_kelas']}</th>";
    }, $kelasList)) . "
              </tr>
              <tr>
                  <th class='header-les'></th>
                  <th class='header-waktu'></th>
                  " . str_repeat("<th>Mapel</th><th>Guru</th>", count($kelasList)) . "
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

        // Baris Hari Vertikal (Rotasi)
        if ($isFirstRow) {
          // Baris pertama hari ini akan memiliki rowspan sepanjang jumlah jam
          $html .= "<td class='header-col' rowspan='" . count($lesList) . "'>
                              <span class='rotated-text'>{$hari}</span>
                            </td>";
          $isFirstRow = false;
        }

        // Baris Khusus (UPACARA, ISTIRAHAT)
        if ($lesData['type'] !== 'ROSTER') {
          $spanCols = 2 * count($kelasList);
          $html .= "
                      <td colspan='2' class='header-waktu special-row' style='background-color: {$lesData['color']} !important;'>{$waktu}</td>
                      <td colspan='{$spanCols}' class='special-row' style='background-color: {$lesData['color']} !important;'>
                          {$lesData['data']}
                      </td>
                  </tr>";
          continue;
        }

        // Baris Roster Normal
        $html .= "<td class='header-les'>{$lesData['les']}</td>";
        $html .= "<td class='header-waktu'>{$waktu}</td>";

        // Kolom Data Roster (Mapel dan Guru untuk setiap kelas)
        foreach ($kelasList as $kelas):
          $dataCell = $rosterData[$hari][$jam_mulai][$kelas['nama_kelas']] ?? null;

          if ($dataCell) {
            // PASTIKAN MAPEL DAN GURU TERCETAK DI SINI
            $html .= "<td class='roster-cell roster-mapel'>{$dataCell['mapel']}</td>";
            $html .= "<td class='roster-cell roster-guru'>{$dataCell['guru']}</td>";
          } else {
            // Hanya satu sel kosong dengan colspan='2'
            $html .= "<td colspan='2' style='background-color:#fcfcfc;'></td>";
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
  $dompdf->setPaper('A3', 'landscape');
  $dompdf->render();

  $dompdf->stream("roster_{$angkatan}.pdf", ["Attachment" => true]);
