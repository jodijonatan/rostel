<?php
include 'koneksi.php';
$pageTitle = "Lihat Roster";
$pageLocation = "Roster";
include 'layout.php';

// Ambil semua kelas dari database
$kelasList = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetch_all(MYSQLI_ASSOC);

// Definisi hari dan jam pelajaran
$hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
$lesList = [
  ['1', '08:00', '08:30'],
  ['2', '08:30', '09:00'],
  ['3', '09:00', '09:30'],
  ['4', '09:30', '10:00'],
  ['5', '10:30', '11:00'],
  ['6', '11:00', '11:30'],
  ['7', '11:30', '12:00'],
  ['8', '12:00', '12:30'],
  ['9', '13:20', '13:50'],
  ['10', '13:50', '14:20'],
  ['11', '14:20', '14:50'],
  ['12', '14:50', '15:20']
];

// Ambil data roster
$q = "
SELECT r.id_roster, g.nama AS guru, m.nama AS mapel, 
       k.nama_kelas, r.hari, r.jam_mulai
FROM roster r
JOIN guru g ON r.id_guru = g.id_guru
JOIN mata_pelajaran m ON r.id_mapel = m.id_mapel
JOIN kelas k ON r.id_kelas = k.id_kelas
";
$result = $conn->query($q);

$rosterData = [];
while ($r = $result->fetch_assoc()) {
  $jamKey = substr($r['jam_mulai'], 0, 5);
  $rosterData[$r['hari']][$jamKey][$r['nama_kelas']] =
    "{$r['mapel']}<br><small>{$r['guru']}</small>";
}
?>

<style>
  .hari-vertikal {
    writing-mode: vertical-rl;
    transform: rotate(180deg);
    text-align: center;
    vertical-align: middle;
    font-weight: bold;
    background-color: #f8f9fa;
  }

  .non-editable {
    background-color: #f3e5f5 !important;
    font-weight: bold;
  }

  .istirahat {
    background-color: #ffe0b2 !important;
    font-weight: bold;
  }

  .ibadah {
    background-color: #dcedc8 !important;
    font-weight: bold;
  }

  td,
  th {
    vertical-align: middle !important;
    text-align: center;
    font-size: 13px;
  }

  .table-bordered th,
  .table-bordered td {
    border: 1px solid #dee2e6;
  }
</style>

<div class="container-fluid">
  <h2 class="mb-4">Lihat Jadwal Roster (Admin)</h2>

  <div class="table-responsive">
    <table class="table table-bordered align-middle text-center">
      <thead class="table-primary">
        <tr>
          <th>HARI</th>
          <th>LES</th>
          <th>WAKTU</th>
          <?php foreach ($kelasList as $kelas): ?>
            <th><?= htmlspecialchars($kelas['nama_kelas']) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($hariList as $hari): ?>
          <?php $printedHari = false; ?>
          <?php foreach ($lesList as $jam):
            $jamKey = $jam[1];
            $barisKhusus = '';

            // Tentukan baris khusus
            if ($jam[0] == 4) $barisKhusus = 'UPACARA';
            elseif ($jam[0] == 8) $barisKhusus = 'ISTIRAHAT';
            elseif ($jam[0] == 9 && $hari == 'Rabu') $barisKhusus = 'IBADAH PAGI';
            elseif ($hari == 'Jumat' && $jam[0] == 3) $barisKhusus = 'SKJ / EKSTRA';
            elseif ($hari == 'Jumat' && $jam[0] == 5) $barisKhusus = 'IBADAH JUMAT';
          ?>
            <tr>
              <?php if (!$printedHari): ?>
                <td rowspan="<?= count($lesList) ?>" class="hari-vertikal"><?= strtoupper($hari) ?></td>
                <?php $printedHari = true; ?>
              <?php endif; ?>

              <td><?= $jam[0] ?></td>
              <td><?= $jam[1] ?> - <?= $jam[2] ?></td>

              <?php if ($barisKhusus): ?>
                <td colspan="<?= count($kelasList) ?>"
                  class="<?=
                          $barisKhusus == 'ISTIRAHAT' ? 'istirahat' : (($barisKhusus == 'IBADAH PAGI' || $barisKhusus == 'IBADAH JUMAT') ? 'ibadah' : 'non-editable')
                          ?>">
                  <?= $barisKhusus ?>
                </td>
              <?php else: ?>
                <?php foreach ($kelasList as $kelas):
                  $isi = $rosterData[$hari][$jamKey][$kelas['nama_kelas']] ?? '';
                ?>
                  <td class="editable-cell"
                    data-hari="<?= $hari ?>"
                    data-jam="<?= $jamKey ?>"
                    data-kelas="<?= $kelas['id_kelas'] ?>">
                    <?= $isi ?>
                  </td>
                <?php endforeach; ?>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal tambah/edit -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" id="editForm" action="roster_save.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah / Edit Jadwal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="hari" id="hariInput">
        <input type="hidden" name="jam_mulai" id="jamInput">
        <input type="hidden" name="id_kelas" id="kelasInput">

        <div class="mb-3">
          <label class="form-label">Pilih Guru</label>
          <select name="id_guru" id="guruSelect" class="form-select" required></select>
        </div>

        <div class="mb-3">
          <label class="form-label">Pilih Mata Pelajaran</label>
          <select name="id_mapel" id="mapelSelect" class="form-select" required disabled>
            <option value="">-- Pilih Mapel --</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
  const guruSelect = document.getElementById('guruSelect');
  const mapelSelect = document.getElementById('mapelSelect');

  document.querySelectorAll('.editable-cell').forEach(cell => {
    cell.addEventListener('click', () => {
      if (cell.closest('tr').querySelector('td[colspan]')) return;

      const hari = cell.dataset.hari;
      const jam = cell.dataset.jam;
      const kelas = cell.dataset.kelas;

      document.getElementById('hariInput').value = hari;
      document.getElementById('jamInput').value = jam;
      document.getElementById('kelasInput').value = kelas;

      guruSelect.innerHTML = '<option>Memuat guru...</option>';
      guruSelect.disabled = true;
      mapelSelect.innerHTML = '<option value="">-- Pilih Mapel --</option>';
      mapelSelect.disabled = true;

      const modal = new bootstrap.Modal(document.getElementById('editModal'));
      modal.show();

      fetch(`get_available_guru.php?hari=${hari}&jam_mulai=${jam}`)
        .then(res => res.json())
        .then(data => {
          guruSelect.innerHTML = '';
          if (data.length > 0) {
            guruSelect.innerHTML = '<option value="">-- Pilih Guru --</option>';
            data.forEach(g => {
              const opt = document.createElement('option');
              opt.value = g.id_guru;
              opt.textContent = g.nama;
              guruSelect.appendChild(opt);
            });
            guruSelect.disabled = false;
          } else {
            const opt = document.createElement('option');
            opt.textContent = 'Semua guru sudah terjadwal';
            guruSelect.appendChild(opt);
            guruSelect.disabled = true;
          }
        });
    });
  });

  guruSelect.addEventListener('change', () => {
    const idGuru = guruSelect.value;
    mapelSelect.innerHTML = '<option value="">-- Pilih Mapel --</option>';

    if (idGuru === '') {
      mapelSelect.disabled = true;
      return;
    }

    fetch(`get_mapel_by_guru.php?id_guru=${idGuru}`)
      .then(res => res.json())
      .then(data => {
        if (data.length > 0) {
          data.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.id_mapel;
            opt.textContent = m.nama;
            mapelSelect.appendChild(opt);
          });
          mapelSelect.disabled = false;
        } else {
          const opt = document.createElement('option');
          opt.textContent = 'Guru ini belum punya mapel';
          mapelSelect.appendChild(opt);
          mapelSelect.disabled = true;
        }
      });
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>