<?php
include 'koneksi.php';

if (isset($_POST['simpan'])) {
  $hari = $_POST['hari'];
  $jam_mulai = $_POST['jam_mulai'];
  $id_kelas = $_POST['id_kelas'];
  $id_guru = $_POST['id_guru'];
  $id_mapel = $_POST['id_mapel'];

  // Tentukan jam_selesai otomatis dari jam_mulai
  $jam_selesai_map = [
    '07:00' => '08:30',
    '08:30' => '10:00',
    '10:00' => '11:30',
    '11:30' => '13:00'
  ];
  $jam_selesai = $jam_selesai_map[$jam_mulai] ?? '00:00';

  // 🔍 Cek apakah guru sudah mengajar di waktu dan hari yang sama di kelas lain
  $cek = $conn->prepare("
    SELECT * FROM roster
    WHERE id_guru = ? 
      AND hari = ? 
      AND jam_mulai = ? 
      AND id_kelas != ?
  ");
  $cek->bind_param("sssi", $id_guru, $hari, $jam_mulai, $id_kelas);
  $cek->execute();
  $result = $cek->get_result();

  if ($result->num_rows > 0) {
    echo "<script>
      alert('Guru sudah memiliki jadwal di waktu tersebut di kelas lain!');
      window.history.back();
    </script>";
    exit;
  }

  // 🧹 Hapus jika ada jadwal lama di slot yang sama untuk kelas itu
  $conn->query("DELETE FROM roster WHERE hari='$hari' AND id_kelas=$id_kelas AND jam_mulai='$jam_mulai:00'");

  // 💾 Simpan jadwal baru
  $stmt = $conn->prepare("
    INSERT INTO roster (id_guru, id_mapel, id_kelas, hari, jam_mulai, jam_selesai)
    VALUES (?, ?, ?, ?, ?, ?)
  ");
  $stmt->bind_param("iiisss", $id_guru, $id_mapel, $id_kelas, $hari, $jam_mulai, $jam_selesai);
  $stmt->execute();

  header("Location: roster_view.php");
  exit;
}
