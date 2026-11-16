/* Pesta Kita — filter lokal katalog Vendor (Tahap 5.1).
   Menyaring daftar contoh yang sedang tampil di halaman saja.
   Tidak melakukan permintaan ke server dan tidak menyimpan data apa pun.
   Tanpa JS (progressive enhancement): seluruh kartu tetap tampil. */
(function () {
  'use strict';

  var list = document.getElementById('kat-cards');
  if (!list) return;

  var cards = Array.prototype.slice.call(list.querySelectorAll('[data-kategori]'));
  var input = document.getElementById('kat-cari');
  var harga = document.getElementById('kat-harga');
  var btn = document.getElementById('kat-cari-btn');
  var empty = document.getElementById('kat-kosong');
  var reset = document.getElementById('kat-reset');
  var pills = Array.prototype.slice.call(document.querySelectorAll('[data-kat-pill]'));
  var activeKat = '';

  var params = new URLSearchParams(window.location.search);
  if (params.get('kategori')) activeKat = params.get('kategori');

  function syncPills() {
    pills.forEach(function (p) {
      p.setAttribute('aria-current', p.getAttribute('data-kat-pill') === activeKat ? 'true' : 'false');
    });
  }

  function apply() {
    var q = input && input.value ? input.value.trim().toLowerCase() : '';
    var max = parseInt((harga && harga.value ? harga.value : '').replace(/[^0-9]/g, ''), 10);
    var shown = 0;

    cards.forEach(function (card) {
      var kat = card.getAttribute('data-kategori') || '';
      var nama = (card.getAttribute('data-nama') || '').toLowerCase();
      var price = parseInt(card.getAttribute('data-harga') || '0', 10);
      var ok = true;

      if (activeKat && kat !== activeKat) ok = false;
      if (ok && q && nama.indexOf(q) === -1) ok = false;
      if (ok && !isNaN(max) && price > max) ok = false;

      card.hidden = !ok;
      if (ok) shown++;
    });

    if (empty) empty.hidden = shown !== 0;
  }

  pills.forEach(function (pill) {
    pill.addEventListener('click', function (e) {
      e.preventDefault();
      activeKat = pill.getAttribute('data-kat-pill') || '';
      syncPills();
      apply();
    });
  });

  if (btn) btn.addEventListener('click', apply);
  if (reset) reset.addEventListener('click', function () {
    if (input) input.value = '';
    if (harga) harga.value = '';
    activeKat = '';
    syncPills();
    apply();
  });
  if (input) {
    input.addEventListener('input', apply);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); apply(); }
    });
  }
  if (harga) harga.addEventListener('input', apply);

  syncPills();
  apply();
})();
