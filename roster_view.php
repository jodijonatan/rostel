<?php
include 'koneksi.php';
$pageTitle = "Lihat Roster";
$pageLocation = "Roster";
include 'layout.php';

// Daftar jam pelajaran
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

// FILTER ANGKATAN
$filterAngkatan = isset($_GET['angkatan']) ? $_GET['angkatan'] : "";

// Query roster
$query = "
  SELECT r.id_roster, g.nama AS guru, m.nama AS mapel,
         k.nama_kelas, k.angkatan, r.hari, r.jam_mulai, r.jam_selesai
  FROM roster r
  JOIN guru g ON r.id_guru = g.id_guru
  JOIN mata_pelajaran m ON r.id_mapel = m.id_mapel
  JOIN kelas k ON r.id_kelas = k.id_kelas
";

$result = $conn->query($query);

// Susun roster
$rosterData = [];
while ($r = $result->fetch_assoc()) {
  $jamKey = substr($r['jam_mulai'], 0, 5);
  $rosterData[$r['hari']][$jamKey][$r['nama_kelas']] =
    "{$r['mapel']}<br><small>{$r['guru']}</small>";
}

// Ambil semua kelas
$kelasList = $conn->query("SELECT * FROM kelas ORDER BY nama_kelas");

// Filter kelas
$kelasFiltered = [];
if ($filterAngkatan !== "") {
  foreach ($kelasList as $k) {
    if ($k['angkatan'] === $filterAngkatan) {
      $kelasFiltered[] = $k;
    }
  }
}
?>

<div class="container-fluid">
  <h2 class="mb-4">Lihat Jadwal (Roster)</h2>

  <!-- FILTER ANGKATAN -->
  <form method="GET" class="mb-3">
    <div class="row g-2" style="max-width: 350px;">
      <div class="col-12">
        <select name="angkatan" class="form-select" onchange="this.form.submit()">
          <option value="">-- Pilih Angkatan --</option>
          <option value="X" <?= $filterAngkatan == 'X' ? 'selected' : '' ?>>X</option>
          <option value="XI" <?= $filterAngkatan == 'XI' ? 'selected' : '' ?>>XI</option>
          <option value="XII" <?= $filterAngkatan == 'XII' ? 'selected' : '' ?>>XII</option>
        </select>
      </div>
    </div>
  </form>


  <!-- EXPORT PDF BUTTON -->
  <a href="export_roster_pdf.php?angkatan=<?= $filterAngkatan ?>"
    class="btn btn-danger mb-3 ms-2 <?= $filterAngkatan === '' ? 'disabled' : '' ?>">
    Export PDF
  </a>

  <!-- PESAN JIKA BELUM PILIH ANGKATAN -->
  <?php if ($filterAngkatan === ""): ?>
    <div class="alert alert-warning mt-3" style="max-width: 400px;">
      <strong>Silakan pilih angkatan dulu.</strong>
    </div>
  <?php endif; ?>

  <!-- TABEL ROSTER (hanya muncul jika angkatan dipilih) -->
  <?php if ($filterAngkatan !== ""): ?>
    <div class="table-responsive mt-3">
      <table class="table table-bordered text-center align-middle">
        <thead class="table-primary">
          <tr>
            <th>Hari / Jam</th>
            <?php foreach ($kelasFiltered as $kelas): ?>
              <th>
                <?= htmlspecialchars($kelas['nama_kelas']) ?><br>
                <small>(<?= $kelas['angkatan'] ?>)</small>
              </th>
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

                <?php foreach ($kelasFiltered as $kelas): ?>
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
  <?php endif; ?>
</div>

<!-- Modal Edit -->
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
          <label>Pilih Guru</label>
          <select name="id_guru" id="guruSelect" class="form-select" required>
            <option value="">-- Pilih Guru --</option>
          </select>
        </div>

        <div class="mb-3">
          <label>Pilih Mapel</label>
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

      guruSelect.innerHTML = '<option>-- Memuat guru... --</option>';
      guruSelect.disabled = true;
      mapelSelect.innerHTML = '<option>-- Pilih Mapel --</option>';
      mapelSelect.disabled = true;

      new bootstrap.Modal(document.getElementById('editModal')).show();

      fetch(`get_available_guru.php?hari=${hari}&jam_mulai=${jam}`)
        .then(res => res.json())
        .then(data => {
          guruSelect.innerHTML = '';
          if (data.length > 0) {
            guruSelect.innerHTML = '<option value="">-- Pilih Guru --</option>';
            data.forEach(g => {
              let opt = document.createElement('option');
              opt.value = g.id_guru;
              opt.textContent = g.nama;
              guruSelect.appendChild(opt);
            });
            guruSelect.disabled = false;
          } else {
            let opt = document.createElement('option');
            opt.textContent = 'Semua guru sudah terjadwal';
            guruSelect.appendChild(opt);
            guruSelect.disabled = true;
          }
        });
    });
  });

  guruSelect.addEventListener('change', () => {
    const idGuru = guruSelect.value;
    mapelSelect.innerHTML = '<option>-- Pilih Mapel --</option>';

    if (idGuru === '') {
      mapelSelect.disabled = true;
      return;
    }

    fetch(`get_mapel_by_guru.php?id_guru=${idGuru}`)
      .then(res => res.json())
      .then(data => {
        data.forEach(m => {
          let opt = document.createElement('option');
          opt.value = m.id_mapel;
          opt.textContent = m.nama;
          mapelSelect.appendChild(opt);
        });
        mapelSelect.disabled = false;
      });
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>