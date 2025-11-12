<?php
include 'koneksi.php';
$pageTitle = "Lihat Roster";
$pageLocation = "Roster";
include 'layout.php';

// Daftar waktu les
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

// Buat array untuk tampilan
$rosterData = [];
while ($r = $result->fetch_assoc()) {
  $jamKey = substr($r['jam_mulai'], 0, 5);
  $rosterData[$r['hari']][$jamKey][$r['nama_kelas']] =
    "{$r['mapel']}<br><small>{$r['guru']}</small>";
}

// Ambil daftar kelas dan guru
$kelasList = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas");
$guruList = $conn->query("SELECT * FROM guru ORDER BY nama");
?>

<div class="container-fluid mt-4">
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
            <?php foreach ($guruList as $g): ?>
              <option value="<?= $g['id_guru'] ?>"><?= htmlspecialchars($g['nama']) ?></option>
            <?php endforeach; ?>
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
  // Klik sel → buka modal
  document.querySelectorAll('.editable-cell').forEach(cell => {
    cell.addEventListener('click', () => {
      const hari = cell.dataset.hari;
      const jam = cell.dataset.jam;
      const kelas = cell.dataset.kelas;

      document.getElementById('hariInput').value = hari;
      document.getElementById('jamInput').value = jam;
      document.getElementById('kelasInput').value = kelas;

      // Reset form setiap buka modal
      document.getElementById('guruSelect').value = "";
      document.getElementById('mapelSelect').innerHTML = '<option value="">-- Pilih Mapel --</option>';
      document.getElementById('mapelSelect').disabled = true;

      const modal = new bootstrap.Modal(document.getElementById('editModal'));
      modal.show();
    });
  });

  // Guru dipilih → ambil mapel via AJAX
  const guruSelect = document.getElementById('guruSelect');
  const mapelSelect = document.getElementById('mapelSelect');

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
          opt.value = '';
          opt.textContent = 'Guru ini belum punya mapel';
          mapelSelect.appendChild(opt);
          mapelSelect.disabled = true;
        }
      })
      .catch(err => {
        console.error('Error:', err);
        alert('Gagal mengambil data mapel!');
      });
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>