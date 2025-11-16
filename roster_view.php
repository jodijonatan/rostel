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

// Query roster (mengambil ID Roster juga untuk keperluan EDIT/DELETE di Modal)
$query = "
  SELECT r.id_roster, g.nama AS guru, m.nama AS mapel,
          k.nama_kelas, k.angkatan, r.hari, r.jam_mulai, r.jam_selesai
  FROM roster r
  JOIN guru g ON r.id_guru = g.id_guru
  JOIN mata_pelajaran m ON r.id_mapel = m.id_mapel
  JOIN kelas k ON r.id_kelas = k.id_kelas
  WHERE k.angkatan = '$filterAngkatan' OR '$filterAngkatan' = ''
";

$result = $conn->query($query);

// Susun roster
$rosterData = [];
while ($r = $result->fetch_assoc()) {
  $jamKey = substr($r['jam_mulai'], 0, 5);
  // Simpan ID Roster dan konten
  $rosterData[$r['hari']][$jamKey][$r['nama_kelas']] = [
    'id' => $r['id_roster'],
    'content' => "{$r['mapel']}<br><small class='text-muted'>{$r['guru']}</small>"
  ];
}

// Ambil semua kelas
$kelasList = $conn->query("SELECT * FROM kelas ORDER BY angkatan, nama_kelas");

// Filter kelas
$kelasFiltered = [];
if ($filterAngkatan !== "") {
  while ($k = $kelasList->fetch_assoc()) {
    if ($k['angkatan'] === $filterAngkatan) {
      $kelasFiltered[] = $k;
    }
  }
}
?>

<style>
  /* === Styling Tabel Roster Modern === */
  .table-roster {
    font-size: 0.9rem;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    background-color: white;
  }

  .table-roster thead th {
    background-color: #34495e;
    color: white;
    border: none;
    font-weight: 600;
    vertical-align: middle;
  }

  /* Styling Jam dan Hari */
  .time-cell {
    background-color: #f8f9fa;
    font-weight: bold;
    color: #555;
    width: 150px;
    border-right: 2px solid #ddd;
  }

  /* Sel Interaktif */
  .editable-cell {
    cursor: pointer;
    transition: background-color 0.2s, transform 0.1s;
    background-color: #e8f5e9;
  }

  .editable-cell:empty {
    background-color: #fff;
  }

  .editable-cell:hover {
    background-color: #c8e6c9;
    transform: scale(1.01);
    box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.1);
  }

  /* Warna untuk sel yang kosong saat hover (untuk memudahkan edit) */
  .editable-cell:empty:hover {
    background-color: #ffe0b2;
  }

  .table-roster td,
  .table-roster th {
    border-color: #e9ecef !important;
  }
</style>

<div class="container-fluid">
  <h2 class="mb-4"><i class="fas fa-calendar-alt me-2"></i>Lihat Jadwal (Roster)</h2>

  <div class="d-flex flex-wrap justify-content-start align-items-center mb-4 gap-3">

    <form method="GET" class="d-flex align-items-center gap-2">
      <label class="form-label mb-0 fw-bold text-muted">Filter Angkatan:</label>
      <select name="angkatan" class="form-select" onchange="this.form.submit()" style="min-width: 150px;">
        <option value="">-- Semua Angkatan --</option>
        <option value="X" <?= $filterAngkatan == 'X' ? 'selected' : '' ?>>Kelas X</option>
        <option value="XI" <?= $filterAngkatan == 'XI' ? 'selected' : '' ?>>Kelas XI</option>
        <option value="XII" <?= $filterAngkatan == 'XII' ? 'selected' : '' ?>>Kelas XII</option>
      </select>
    </form>

    <a href="export_roster_pdf.php?angkatan=<?= $filterAngkatan ?>"
      class="btn btn-danger <?= $filterAngkatan === '' ? 'disabled' : '' ?>">
      <i class="fas fa-file-pdf me-1"></i> Export PDF
    </a>

  </div>

  <?php if ($filterAngkatan !== "" && empty($kelasFiltered)): ?>
    <div class="alert alert-info mt-3" style="max-width: 500px;">
      <i class="fas fa-info-circle me-2"></i> Tidak ada data kelas untuk angkatan **<?= $filterAngkatan ?>**.
    </div>
  <?php elseif ($filterAngkatan === ""): ?>
    <div class="alert alert-warning mt-3" style="max-width: 400px;">
      <i class="fas fa-arrow-up me-2"></i> Silakan pilih angkatan untuk melihat roster.
    </div>
  <?php endif; ?>

  <?php if (!empty($kelasFiltered)): ?>
    <div class="table-responsive mt-3">
      <table class="table table-bordered table-roster text-center align-middle">
        <thead class="table-primary">
          <tr>
            <th class="time-cell" style="background-color: #27374D;">Jam Pelajaran</th>
            <?php foreach ($kelasFiltered as $kelas): ?>
              <th>
                <?= htmlspecialchars($kelas['nama_kelas']) ?><br>
                <span class="small opacity-75">(<?= $kelas['angkatan'] ?>)</span>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php
          $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
          foreach ($hariList as $hari):
            // Membuat baris pemisah hari
            echo "<tr><td colspan='" . (count($kelasFiltered) + 1) . "' class='text-start bg-light fw-bold p-2'>
              <i class='far fa-calendar-check me-2'></i> " . strtoupper($hari) . "
            </td></tr>";

            foreach ($lesList as $jam):
              $jamKey = $jam[0];
          ?>
              <tr>
                <td class="time-cell">
                  <?= $jam[0] ?> - <?= $jam[1] ?>
                </td>

                <?php foreach ($kelasFiltered as $kelas): ?>
                  <?php
                  $data = $rosterData[$hari][$jamKey][$kelas['nama_kelas']] ?? ['id' => null, 'content' => ''];
                  $isi = $data['content'];
                  $id_roster = $data['id'];
                  $cellClass = $id_roster ? 'filled-cell' : 'empty-cell'; // Class tambahan untuk warna
                  ?>
                  <td class="editable-cell <?= $cellClass ?>"
                    data-hari="<?= $hari ?>"
                    data-jam="<?= $jamKey ?>"
                    data-kelas-id="<?= $kelas['id_kelas'] ?>"
                    data-roster-id="<?= $id_roster ?>">
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

<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="editForm" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Atur Jadwal</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p class="small text-muted mb-3">Mengatur jadwal untuk <span class="fw-bold" id="displayHariJam"></span> di kelas <span class="fw-bold" id="displayKelas"></span>.</p>

        <input type="hidden" name="roster_id" id="rosterIdInput" value="">
        <input type="hidden" name="hari" id="hariInput">
        <input type="hidden" name="jam_mulai" id="jamInput">
        <input type="hidden" name="id_kelas" id="kelasInput">

        <div class="mb-3">
          <label class="form-label fw-bold"><i class="fas fa-user-tie me-1"></i> Pilih Guru</label>
          <select name="id_guru" id="guruSelect" class="form-select" required>
            <option value="">-- Pilih Guru --</option>
          </select>
          <div class="form-text text-danger" id="guruStatus" style="display:none;"></div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold"><i class="fas fa-book me-1"></i> Pilih Mapel</label>
          <select name="id_mapel" id="mapelSelect" class="form-select" required disabled>
            <option value="">-- Pilih Mapel --</option>
          </select>
        </div>

        <div id="deleteAction" class="mt-4 border-top pt-3" style="display:none;">
          <p class="small text-muted mb-2">Aksi untuk jadwal yang sudah ada:</p>
          <button type="button" class="btn btn-outline-danger w-100" id="deleteRosterBtn">
            <i class="fas fa-trash-alt me-1"></i> Hapus Jadwal Ini
          </button>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="simpan" class="btn btn-primary" id="saveRosterBtn">
          <i class="fas fa-save me-1"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  const editModal = new bootstrap.Modal(document.getElementById('editModal'));
  const guruSelect = document.getElementById('guruSelect');
  const mapelSelect = document.getElementById('mapelSelect');
  const form = document.getElementById('editForm');
  const deleteBtn = document.getElementById('deleteRosterBtn');
  const deleteSection = document.getElementById('deleteAction');
  const saveBtn = document.getElementById('saveRosterBtn');
  const guruStatus = document.getElementById('guruStatus');

  let currentCell = null; // Menyimpan sel yang sedang diedit

  function resetModal() {
    form.reset();
    deleteSection.style.display = 'none';
    guruStatus.style.display = 'none';
    guruSelect.innerHTML = '<option value="">-- Pilih Guru --</option>';
    mapelSelect.innerHTML = '<option value="">-- Pilih Mapel --</option>';
    mapelSelect.disabled = true;
    saveBtn.textContent = 'Simpan';
    saveBtn.classList.remove('btn-success');
    saveBtn.classList.add('btn-primary');
    document.getElementById('rosterIdInput').value = '';

    // Mengambil nama kelas untuk ditampilkan di modal header
    const id_kelas = document.getElementById('kelasInput').value;
    const kelasName = currentCell.closest('table').querySelector(`th:nth-child(${currentCell.cellIndex + 1})`).textContent.trim().split('\n')[0];
    document.getElementById('displayKelas').textContent = kelasName;
  }

  // ========== 1. PENGATURAN MODAL SAAT KLIK SEL ==========
  document.querySelectorAll('.editable-cell').forEach(cell => {
    cell.addEventListener('click', () => {
      currentCell = cell;
      const hari = cell.dataset.hari;
      const jam = cell.dataset.jam;
      const id_kelas = cell.dataset.kelasId;
      const id_roster = cell.dataset.rosterId;
      const currentContent = cell.innerHTML.trim();

      // Isi hidden input
      document.getElementById('hariInput').value = hari;
      document.getElementById('jamInput').value = jam;
      document.getElementById('kelasInput').value = id_kelas;
      document.getElementById('rosterIdInput').value = id_roster || '';

      // Tampilan Modal Header
      document.getElementById('displayHariJam').textContent = `${hari}, ${jam}`;

      resetModal(); // Reset dan update nama kelas

      if (id_roster) {
        // MODE EDIT/DELETE
        deleteSection.style.display = 'block';
        saveBtn.textContent = 'Ubah Jadwal';
        saveBtn.classList.remove('btn-primary');
        saveBtn.classList.add('btn-success');
      }

      editModal.show();
      loadGuruMapel(hari, jam, id_kelas, id_roster, currentContent);
    });
  });

  // ========== 2. LOGIC LOAD GURU DAN MAPEL ==========
  function loadGuruMapel(hari, jam, id_kelas, id_roster, currentContent) {
    guruSelect.innerHTML = '<option value="">-- Memuat guru... --</option>';
    guruSelect.disabled = true;
    mapelSelect.disabled = true;

    // 1. Ambil guru yang tersedia pada jam tersebut
    fetch(`get_available_guru.php?hari=${hari}&jam_mulai=${jam}&id_roster=${id_roster}`) // Kirim id_roster untuk mengabaikan dirinya sendiri saat cek ketersediaan
      .then(res => res.json())
      .then(data => {
        guruSelect.innerHTML = '<option value="">-- Pilih Guru --</option>';
        if (data.available_guru && data.available_guru.length > 0) {
          data.available_guru.forEach(g => {
            let opt = document.createElement('option');
            opt.value = g.id_guru;
            opt.textContent = g.nama;
            guruSelect.appendChild(opt);
          });
          guruSelect.disabled = false;
        } else {
          guruStatus.textContent = 'Tidak ada guru yang tersedia.';
          guruStatus.style.display = 'block';
          guruSelect.disabled = true;
        }

        // 2. Jika ID Roster ada (mode edit), cari guru dan mapel yang saat ini dipilih
        if (id_roster && data.current_data) {
          const current = data.current_data;
          // Tambahkan guru yang saat ini bertugas ke daftar jika belum ada (misal dia tidak tersedia di jam lain)
          if (!guruSelect.querySelector(`option[value="${current.id_guru}"]`)) {
            let opt = document.createElement('option');
            opt.value = current.id_guru;
            opt.textContent = current.guru_nama + ' (Saat Ini)';
            guruSelect.appendChild(opt);
          }

          // Set nilai terpilih
          guruSelect.value = current.id_guru;

          // Load mapel untuk guru yang terpilih saat ini
          loadMapelForGuru(current.id_guru, current.id_mapel);
        }
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        guruStatus.textContent = 'Gagal memuat data guru.';
        guruStatus.style.display = 'block';
      });
  }

  // ========== 3. LOGIC LOAD MAPEL SAAT GURU BERUBAH ==========
  guruSelect.addEventListener('change', () => {
    const idGuru = guruSelect.value;
    loadMapelForGuru(idGuru);
  });

  function loadMapelForGuru(idGuru, selectedMapelId = null) {
    mapelSelect.innerHTML = '<option value="">-- Pilih Mapel --</option>';

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
        if (selectedMapelId) {
          mapelSelect.value = selectedMapelId;
        }
      });
  }

  // ========== 4. LOGIC SUBMIT FORM ==========
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(form);
    const action = formData.get('roster_id') ? 'update' : 'tambah';

    fetch('roster_save.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        editModal.hide();
        if (data.status === 'success') {
          Swal.fire('Berhasil!', data.message, 'success')
            .then(() => window.location.reload());
        } else {
          Swal.fire('Gagal!', data.message, 'error');
        }
      })
      .catch(() => {
        editModal.hide();
        Swal.fire('Error!', 'Terjadi kesalahan jaringan.', 'error');
      });
  });

  // ========== 5. LOGIC DELETE ==========
  deleteBtn.addEventListener('click', () => {
    const rosterId = document.getElementById('rosterIdInput').value;

    if (!rosterId) return;

    Swal.fire({
      title: 'Yakin Hapus Jadwal?',
      text: "Anda tidak bisa mengembalikan ini!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, Hapus!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(`roster_delete.php?id_roster=${rosterId}`)
          .then(res => res.json())
          .then(data => {
            editModal.hide();
            if (data.status === 'success') {
              Swal.fire('Terhapus!', data.message, 'success')
                .then(() => window.location.reload());
            } else {
              Swal.fire('Gagal!', data.message, 'error');
            }
          })
          .catch(() => {
            editModal.hide();
            Swal.fire('Error!', 'Gagal menghapus jadwal.', 'error');
          });
      }
    });
  });
</script>

</body>

</html>