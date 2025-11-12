<?php
include 'koneksi.php';

$hari = $_POST['hari'];
$les = $_POST['les'];
$kelas = $_POST['kelas'];

$lesList = [
  'Les 1' => ['07:30', '08:00'],
  'Les 2' => ['08:00', '08:30'],
  'Les 3' => ['08:30', '09:00'],
  'Les 4' => ['09:00', '09:30'],
  'Les 5' => ['09:30', '10:00'],
  'Les 6' => ['10:00', '10:30'],
  'Les 7' => ['10:30', '11:00'],
  'Les 8' => ['11:00', '11:30']
];
$jam_mulai = $lesList[$les][0];

$id_kelas = $conn->query("SELECT id_kelas FROM kelas WHERE nama_kelas='$kelas'")->fetch_assoc()['id_kelas'];

$conn->query("DELETE FROM roster WHERE hari='$hari' AND id_kelas=$id_kelas AND jam_mulai='$jam_mulai'");
echo "DELETED";
