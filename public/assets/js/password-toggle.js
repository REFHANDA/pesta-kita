/* password-toggle.js — tombol mata pada setiap kolom type="password".
   Bersifat progressive enhancement: kolom tetap berfungsi tanpa JavaScript,
   dan nilainya tidak berubah saat ditampilkan. Hanya mengganti atribut type
   di browser; pengiriman formulir ke server tetap seperti semula. */
(function () {
    "use strict";
    var d = document,
        uid = 0;
    var EYE =
        '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Z"/><circle cx="12" cy="12" r="2.6"/></svg>';
    var EYE_OFF =
        '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 3l18 18"/><path d="M10.6 6.1A9.9 9.9 0 0 1 12 6c6.4 0 10 6 10 6a17.4 17.4 0 0 1-3.4 4.1"/><path d="M6.3 7.9A17 17 0 0 0 2 12s3.6 6 10 6a9.9 9.9 0 0 0 4-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';
    function pasang(f) {
        if (f.dataset.pwToggle) return;
        f.dataset.pwToggle = "on";
        if (!f.id) f.id = "kata-sandi-" + ++uid;
        var w = d.createElement("span");
        w.className = "pw-wrap";
        f.parentNode.insertBefore(w, f);
        w.appendChild(f);
        var b = d.createElement("button");
        b.type = "button";
        b.className = "pw-toggle";
        b.setAttribute("aria-controls", f.id);
        b.setAttribute("aria-pressed", "false");
        b.setAttribute("aria-label", "Tampilkan kata sandi");
        b.innerHTML = EYE;
        w.appendChild(b);
        /* Sejajarkan tombol dengan tinggi kolom (44 px panel, 48 px halaman masuk). */
        function rata() {
            var t = f.offsetHeight || 44;
            b.style.top =
                Math.max(2, Math.round((t - b.offsetHeight) / 2)) + "px";
        }
        rata();
        addEventListener("resize", rata);
        b.addEventListener("click", function () {
            var tampil = f.type === "text";
            f.type = tampil ? "password" : "text";
            b.setAttribute("aria-pressed", String(!tampil));
            b.setAttribute(
                "aria-label",
                tampil ? "Tampilkan kata sandi" : "Sembunyikan kata sandi",
            );
            b.innerHTML = tampil ? EYE : EYE_OFF;
            rata();
        });
    }
    function pasangkanSemua() {
        d.querySelectorAll("input[type=password]").forEach(pasang);
    }
    if (d.readyState === "loading")
        d.addEventListener("DOMContentLoaded", pasangkanSemua);
    else pasangkanSemua();
})();
