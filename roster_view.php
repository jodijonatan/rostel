<?php
include 'koneksi.php';
$pageTitle = "Lihat Roster";
$pageLocation = "Roster";
include 'layout.php';

// Daftar jam pelajaran
$lesList = [
  ['07:00', '08:30'],
  ['08:30', '10:00'],
  ['10:00', '11:30'],
  ['11:30', '13:00']
];

// Ambil data roster
$query = "
  SELECT r.id_roster, g.nama AS guru, m.nama AS mapel,
         k.nama_kelas, r.hari, r.jam_mulai, r.jam_selesai
  FROM roster r
  JOIN guru g ON r.id_guru = g.id_guru
  JOIN mata_pelajaran m ON r.id_mapel = m.id_mapel
  JOIN kelas k ON r.id_kelas = k.id_kelas
";
$result = $conn->query($query);

// Bentuk array untuk tampilan tabel
$rosterData = [];
while ($r = $result->fetch_assoc()) {
  $jamKey = substr($r['jam_mulai'], 0, 5);
  $rosterData[$r['hari']][$jamKey][$r['nama_kelas']] =
    "{$r['mapel']}<br><small>{$r['guru']}</small>";
}

$kelasList = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas");
?>

<div class="container-fluid">
  <h2 class="mb-4">Lihat Jadwal (Roster)</h2>

  <div class="table-responsive">
    <table class="table table-bordered align-middle text-center">
      <thead class="table-primary">
        <tr>
          <th>Hari / Jam</th>
          <?php foreach ($kelasList as $kelas): ?>
            <th><?= htmlspecialchars($kelas['nama_kelas']) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        foreach ($hariList as $hari):
          foreach ($lesList as $jam):
            $jamKey = $jam[0];
        ?>
            <tr>
              <td><strong><?= $hari ?></strong><br><?= $jam[0] ?> - <?= $jam[1] ?></td>
              <?php foreach ($kelasList as $kelas): ?>
                <?php
                $isi = $rosterData[$hari][$jamKey][$kelas['nama_kelas']] ?? '';
                ?>
                <td class="editable-cell"
                  data-hari="<?= $hari ?>"
                  data-jam="<?= $jamKey ?>"
                  data-kelas="<?= $kelas['id_kelas'] ?>">
                  <?= $isi ?>
                </td>
              <?php endforeach; ?>
            </tr>
        <?php
          endforeach;
        endforeach;
        ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal tambah/edit -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" id="editForm" action="roster_save.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah/Edit Jadwal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="hari" id="hariInput">
        <input type="hidden" name="jam_mulai" id="jamInput">
        <input type="hidden" name="id_kelas" id="kelasInput">

        <div class="mb-3">
          <label class="form-label">Pilih Guru</label>
          <select name="id_guru" id="guruSelect" class="form-select" required>
            <option value="">-- Pilih Guru --</option>
          </select>
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
      const hari = cell.dataset.hari;
      const jam = cell.dataset.jam;
      const kelas = cell.dataset.kelas;

      document.getElementById('hariInput').value = hari;
      document.getElementById('jamInput').value = jam;
      document.getElementById('kelasInput').value = kelas;

      guruSelect.innerHTML = '<option value="">-- Memuat guru... --</option>';
      guruSelect.disabled = true;
      mapelSelect.innerHTML = '<option value="">-- Pilih Mapel --</option>';
      mapelSelect.disabled = true;

      const modal = new bootstrap.Modal(document.getElementById('editModal'));
      modal.show();

      // Ambil guru yang masih tersedia
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