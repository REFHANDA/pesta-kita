/* Pesta Kita — kalender ketersediaan (Tahap 5.5).
   Logika kalender bulanan murni frontend (Vanilla JS, tanpa dependensi).
   Data ketersediaan berasal dari data contoh yang ditulis di dalam halaman
   (blok <script type="application/json" id="kal-config">), bukan dari server.
   Seluruh perubahan status (jika disediakan) hanya mengubah state di memori
   selama halaman terbuka dan tidak pernah dikirim atau disimpan ke server. */
(function () {
  'use strict';

  var root = document.querySelector('[data-kalender]');
  if (!root) return;

  var cfgEl = document.getElementById('kal-config');
  if (!cfgEl) return;

  var cfg;
  try {
    cfg = JSON.parse(cfgEl.textContent || '{}');
  } catch (e) {
    return;
  }

  var MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  var STATUS = {
    tersedia: { label: 'Tersedia', mark: '\u2713', cls: 'kal-ok', badge: 'b-ok' },
    tidak: { label: 'Tidak tersedia', mark: '\u2715', cls: 'kal-bad', badge: 'b-bad' },
    belum: { label: 'Belum dikonfirmasi', mark: '?', cls: 'kal-warn', badge: 'b-warn' }
  };

  var datasets = (cfg.datasets || []).slice();
  var editable = !!cfg.editable;

  var titleEl = document.getElementById('kal-title');
  var gridEl = document.getElementById('kal-grid');
  var prevBtn = root.querySelector('[data-kal-prev]');
  var nextBtn = root.querySelector('[data-kal-next]');
  var pickerEl = document.getElementById('kal-picker');
  var kategoriEl = document.getElementById('kal-kategori');
  var detailEl = document.getElementById('kal-detail-body');
  var listEl = document.getElementById('kal-list');
  var emptyEl = document.getElementById('kal-empty');
  var calWrap = document.getElementById('kal-calendar');
  var controlsWrap = root.querySelector('[data-kal-controls]');
  var editWrap = root.querySelector('[data-kal-editable]');
  var quoteLink = document.getElementById('kal-quote-link');

  if (!titleEl || !gridEl) return;

  var now = new Date();
  var view = {
    y: (cfg.start && cfg.start.year) || now.getFullYear(),
    m: (cfg.start && cfg.start.month) || (now.getMonth() + 1)
  };
  var currentKey = null;
  var selected = null;

  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function iso(y, m, d) { return y + '-' + pad(m) + '-' + pad(d); }

  function datasetByKey(key) {
    for (var i = 0; i < datasets.length; i++) {
      if (datasets[i].key === key) return datasets[i];
    }
    return null;
  }
  function current() { return datasetByKey(currentKey); }

  function statusOf(ds, date) {
    if (!ds || !ds.days) return 'belum';
    var s = ds.days[date];
    return STATUS[s] ? s : 'belum';
  }

  function esc(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function dateLong(dateStr) {
    var p = String(dateStr).split('-');
    return parseInt(p[2], 10) + ' ' + MONTHS[parseInt(p[1], 10) - 1] + ' ' + p[0];
  }

  function matchesKategori(ds, kat) {
    if (!kat) return true;
    if (!ds.kategori) return true;
    return ds.kategori === kat;
  }

  function buildPicker() {
    if (!pickerEl) return;
    var kat = kategoriEl ? kategoriEl.value : '';
    var firstKey = null;
    var html = '';
    datasets.forEach(function (ds) {
      if (!matchesKategori(ds, kat)) return;
      if (!firstKey) firstKey = ds.key;
      var label = ds.nama + (ds.jenis ? ' — ' + ds.jenis : '');
      html += '<option value="' + esc(ds.key) + '">' + esc(label) + '</option>';
    });
    pickerEl.innerHTML = html;
    var keep = datasets.some(function (ds) {
      return ds.key === currentKey && matchesKategori(ds, kat);
    });
    if (!keep) currentKey = firstKey;
    pickerEl.value = currentKey || '';
  }

  function renderGrid() {
    var ds = current();
    var first = new Date(view.y, view.m - 1, 1);
    var lead = (first.getDay() + 6) % 7; // minggu dimulai Senin
    var days = new Date(view.y, view.m, 0).getDate();
    var rows = Math.ceil((lead + days) / 7);
    var html = '';
    var day = 1;
    for (var r = 0; r < rows; r++) {
      html += '<tr>';
      for (var c = 0; c < 7; c++) {
        var idx = r * 7 + c;
        if (idx < lead || day > days) {
          html += '<td class="kal-fill" aria-hidden="true"></td>';
          continue;
        }
        var d = day++;
        var date = iso(view.y, view.m, d);
        var info = STATUS[statusOf(ds, date)];
        var isSel = selected === date;
        html += '<td class="kal-cell">' +
          '<button type="button" class="kal-day ' + info.cls + (isSel ? ' is-sel' : '') + '"' +
          ' data-date="' + date + '" aria-pressed="' + (isSel ? 'true' : 'false') + '"' +
          ' aria-label="' + d + ' ' + MONTHS[view.m - 1] + ' ' + view.y + ': ' + info.label + '">' +
          '<span class="kal-num" aria-hidden="true">' + d + '</span>' +
          '<span class="kal-mark" aria-hidden="true">' + info.mark + '</span>' +
          '</button></td>';
      }
      html += '</tr>';
    }
    gridEl.innerHTML = html;
  }

  function renderDetail() {
    if (!detailEl) return;
    if (!selected) {
      detailEl.innerHTML = '<p class="hint">Pilih tanggal pada kalender untuk melihat keterangan ketersediaannya.</p>';
      if (quoteLink && quoteLink.getAttribute('data-base')) {
        quoteLink.setAttribute('href', quoteLink.getAttribute('data-base'));
      }
      return;
    }
    var ds = current();
    var info = STATUS[statusOf(ds, selected)];
    var html = '<p class="kal-date">' + esc(dateLong(selected)) + '</p>' +
      '<p class="mb-12"><span class="badge ' + info.badge + '">' + info.label + '</span></p>';
    if (ds) {
      html += '<div class="detail-list">' +
        '<div class="dl-row"><span>Nama</span><strong>' + esc(ds.nama) + '</strong></div>';
      if (ds.jenis) {
        html += '<div class="dl-row"><span>Jenis</span><strong>' + esc(ds.jenis) + '</strong></div>';
      }
      if (ds.kategori) {
        html += '<div class="dl-row"><span>Kategori</span><strong>' + (ds.kategori === 'venue' ? 'Venue' : 'Vendor') + '</strong></div>';
      }
      html += '</div>';
    }
    html += '<div class="warn-box mt-12">Status ini merupakan informasi prototipe, bukan jaminan ketersediaan. Pesta Kita mengonfirmasi jadwal sebelum pesanan disahkan Admin.</div>';
    detailEl.innerHTML = html;
    if (quoteLink && quoteLink.getAttribute('data-base')) {
      quoteLink.setAttribute('href', quoteLink.getAttribute('data-base') + '?tanggal=' + encodeURIComponent(selected));
    }
  }

  function renderList() {
    if (!listEl) return;
    var ds = current();
    if (!ds || !ds.days) {
      listEl.innerHTML = '<p class="hint">Belum ada tanggal yang ditandai ketersediaannya.</p>';
      return;
    }
    var keys = Object.keys(ds.days).filter(function (k) {
      return STATUS[ds.days[k]] && ds.days[k] !== 'belum';
    }).sort();
    if (!keys.length) {
      listEl.innerHTML = '<p class="hint">Belum ada tanggal yang ditandai ketersediaannya.</p>';
      return;
    }
    var html = '<ul class="kal-list">';
    keys.forEach(function (k) {
      var info = STATUS[ds.days[k]];
      html += '<li><span>' + esc(dateLong(k)) + '</span><span class="badge ' + info.badge + '">' + info.label + '</span></li>';
    });
    html += '</ul>';
    listEl.innerHTML = html;
  }

  function renderNodata() {
    var nodataEl = document.getElementById('kal-nodata');
    if (!nodataEl) return;
    var ds = current();
    var none = !ds || !ds.days || Object.keys(ds.days).length === 0;
    nodataEl.hidden = !none;
  }

  function renderShell() {
    var none = datasets.length === 0;
    if (emptyEl) emptyEl.hidden = !none;
    if (calWrap) calWrap.hidden = none;
    if (controlsWrap) controlsWrap.hidden = none;
  }

  function render() {
    titleEl.textContent = MONTHS[view.m - 1] + ' ' + view.y;
    renderGrid();
    renderDetail();
    renderList();
    renderNodata();
  }

  function refocus(date) {
    var again = gridEl.querySelector('.kal-day[data-date="' + date + '"]');
    if (again) again.focus();
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', function () {
      view.m--;
      if (view.m < 1) { view.m = 12; view.y--; }
      render();
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      view.m++;
      if (view.m > 12) { view.m = 1; view.y++; }
      render();
    });
  }

  gridEl.addEventListener('click', function (e) {
    var btn = e.target.closest ? e.target.closest('.kal-day') : null;
    if (!btn) return;
    selected = btn.getAttribute('data-date');
    renderGrid();
    renderDetail();
    refocus(selected);
  });

  if (pickerEl) {
    pickerEl.addEventListener('change', function () {
      currentKey = pickerEl.value;
      selected = null;
      render();
    });
  }

  if (kategoriEl) {
    kategoriEl.addEventListener('change', function () {
      buildPicker();
      selected = null;
      render();
    });
  }

  if (editWrap) {
    editWrap.hidden = !editable;
    if (editable) {
      editWrap.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('[data-kal-set]') : null;
        if (!btn) return;
        e.preventDefault();
        if (!selected) return;
        var ds = current();
        if (!ds) return;
        if (!ds.days) ds.days = {};
        var val = btn.getAttribute('data-kal-set');
        if (val === 'belum') {
          delete ds.days[selected];
        } else if (STATUS[val]) {
          ds.days[selected] = val;
        }
        renderGrid();
        renderDetail();
        renderList();
        renderNodata();
        refocus(selected);
      });
    }
  }

  buildPicker();
  renderShell();
  render();
})();
