/* Pesta Kita — Pratinjau lokal media profil/portofolio penyedia (Tahap 5.3).
   Hanya menampilkan pratinjau file yang dipilih di browser dengan
   URL.createObjectURL. Tidak mengunggah, tidak menyimpan, dan tidak
   menampilkan status "berhasil disimpan" karena backend belum tersedia.
   Format dan ukuran mengikuti ketentuan contoh yang sudah dipakai
   di repository: JPG/PNG, maksimal 5 MB per foto. */
(function () {
  'use strict';

  var MAX_BYTES = 5 * 1024 * 1024;
  var ALLOWED = ['image/jpeg', 'image/png'];
  var FORMAT_LABEL = 'JPG atau PNG';

  function humanSize(bytes) {
    return (bytes / (1024 * 1024)).toFixed(1).replace('.0', '') + ' MB';
  }

  Array.prototype.forEach.call(document.querySelectorAll('[data-media-input]'), function (input) {
    var list = document.getElementById(input.getAttribute('data-media-list') || '');
    var error = document.getElementById(input.getAttribute('data-media-error') || '');
    var empty = document.getElementById(input.getAttribute('data-media-empty') || '');
    var objectUrls = [];

    function releaseUrls() {
      objectUrls.forEach(function (url) { URL.revokeObjectURL(url); });
      objectUrls = [];
    }

    function showErrors(messages) {
      if (!error) return;
      if (messages.length) {
        error.textContent = messages.join(' ');
        error.hidden = false;
      } else {
        error.textContent = '';
        error.hidden = true;
      }
    }

    input.addEventListener('change', function () {
      releaseUrls();
      if (list) list.innerHTML = '';

      var files = Array.prototype.slice.call(input.files || []);
      var errors = [];
      var accepted = 0;

      files.forEach(function (file) {
        if (ALLOWED.indexOf(file.type) === -1) {
          errors.push(file.name + ': format tidak didukung. Gunakan ' + FORMAT_LABEL + '.');
          return;
        }
        if (file.size > MAX_BYTES) {
          errors.push(file.name + ': ukuran ' + humanSize(file.size) + ' melebihi batas 5 MB.');
          return;
        }

        accepted++;
        var url = URL.createObjectURL(file);
        objectUrls.push(url);

        if (list) {
          var item = document.createElement('li');
          item.className = 'media-item';

          var img = document.createElement('img');
          img.src = url;
          img.alt = 'Pratinjau ' + file.name;

          var caption = document.createElement('figcaption');
          caption.textContent = file.name;

          item.appendChild(img);
          item.appendChild(caption);
          list.appendChild(item);
        }
      });

      showErrors(errors);
      if (empty) empty.hidden = accepted > 0;
    });

    window.addEventListener('beforeunload', releaseUrls);
  });
})();
