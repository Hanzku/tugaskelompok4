/**
 * SIA SMK Malaka - Kelompok 4 (Modul Guru)
 * JavaScript Interaktivitas
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Mobile Sidebar Toggle
  var sidebar = document.getElementById('appSidebar');
  var mobileBackdrop = document.getElementById('mobileBackdrop');
  var hamburgerBtn = document.getElementById('hamburgerBtn');

  if (hamburgerBtn && sidebar && mobileBackdrop) {
    hamburgerBtn.addEventListener('click', function () {
      sidebar.classList.toggle('open');
      mobileBackdrop.classList.toggle('open');
    });

    mobileBackdrop.addEventListener('click', function () {
      sidebar.classList.remove('open');
      mobileBackdrop.classList.remove('open');
    });
  }

  // 2. Modal Helper Functions
  function openModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeModal(modal) {
    if (typeof modal === 'string') {
      modal = document.getElementById(modal);
    }
    if (modal) {
      modal.classList.remove('open');
      document.body.style.overflow = '';
    }
  }

  // Tombol Tutup Modal
  document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var modal = this.closest('.modal-backdrop');
      if (modal) closeModal(modal);
    });
  });

  // Tutup dengan klik di luar modal (backdrop)
  document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
    backdrop.addEventListener('click', function (e) {
      if (e.target === this) {
        closeModal(this);
      }
    });
  });

  // Tutup dengan tombol Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-backdrop.open').forEach(function (modal) {
        closeModal(modal);
      });
    }
  });

  // 3. Tombol Tambah Guru
  var btnTambah = document.getElementById('btnTambahGuru');
  if (btnTambah) {
    btnTambah.addEventListener('click', function (e) {
      e.preventDefault();
      openModal('modalTambah');
    });
  }

  var btnTambahMobile = document.getElementById('btnTambahGuruMobile');
  if (btnTambahMobile) {
    btnTambahMobile.addEventListener('click', function (e) {
      e.preventDefault();
      openModal('modalTambah');
    });
  }

  // 4. Tombol Edit Guru (delegasi atau selector)
  document.querySelectorAll('.btn-edit-guru').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var kode = this.getAttribute('data-kode');
      var nama = this.getAttribute('data-nama');
      var hasPhoto = this.getAttribute('data-has-photo') === '1';

      var inputKode = document.getElementById('editKodeGuru');
      var inputHiddenKode = document.getElementById('editHiddenKodeGuru');
      var inputNama = document.getElementById('editNama');
      var currentPhotoThumb = document.getElementById('editCurrentPhoto');

      if (inputKode) inputKode.value = kode;
      if (inputHiddenKode) inputHiddenKode.value = kode;
      if (inputNama) inputNama.value = nama;

      if (currentPhotoThumb) {
        if (hasPhoto) {
          currentPhotoThumb.src = 'index.php?page=photo&kode=' + encodeURIComponent(kode);
          currentPhotoThumb.style.display = 'block';
        } else {
          currentPhotoThumb.src = 'assets/img/avatar-placeholder.svg';
          currentPhotoThumb.style.display = 'block';
        }
      }

      openModal('modalEdit');
    });
  });

  // 5. Tombol Hapus Guru
  document.querySelectorAll('.btn-hapus-guru').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var kode = this.getAttribute('data-kode');
      var nama = this.getAttribute('data-nama');

      var hiddenKode = document.getElementById('hapusHiddenKode');
      var namaPlaceholder = document.getElementById('hapusNamaPlaceholder');

      if (hiddenKode) hiddenKode.value = kode;
      if (namaPlaceholder) {
        namaPlaceholder.textContent = '"' + nama + '" (' + kode + ')';
      }

      openModal('modalHapus');
    });
  });

  // 6. Preview Foto Upload (Tambah)
  var fileInputTambah = document.getElementById('tambahFotoInput');
  var dropzoneTambah = document.getElementById('tambahDropzone');
  var previewTambah = document.getElementById('tambahPhotoPreview');

  if (fileInputTambah && previewTambah) {
    fileInputTambah.addEventListener('change', function () {
      if (this.files && this.files[0]) {
        var file = this.files[0];
        if (file.size > 3 * 1024 * 1024) {
          alert('Ukuran file melebihi batas 3 MB.');
          this.value = '';
          previewTambah.style.display = 'none';
          return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
          var img = previewTambah.querySelector('img');
          if (img) img.src = e.target.result;
          var label = previewTambah.querySelector('.file-name');
          if (label) label.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
          previewTambah.style.display = 'flex';
        };
        reader.readAsDataURL(file);
      } else {
        previewTambah.style.display = 'none';
      }
    });
  }

  // 7. Preview Foto Upload (Edit)
  var fileInputEdit = document.getElementById('editFotoInput');
  var previewEdit = document.getElementById('editNewPhotoPreview');

  if (fileInputEdit && previewEdit) {
    fileInputEdit.addEventListener('change', function () {
      if (this.files && this.files[0]) {
        var file = this.files[0];
        if (file.size > 3 * 1024 * 1024) {
          alert('Ukuran file melebihi batas 3 MB.');
          this.value = '';
          previewEdit.style.display = 'none';
          return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
          var img = previewEdit.querySelector('img');
          if (img) img.src = e.target.result;
          var label = previewEdit.querySelector('.file-name');
          if (label) label.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
          previewEdit.style.display = 'flex';
        };
        reader.readAsDataURL(file);
      } else {
        previewEdit.style.display = 'none';
      }
    });
  }
});
