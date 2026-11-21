/* aksi.js — perilaku bersama ketiga panel: dialog konfirmasi, alasan wajib,
   notifikasi hasil, dan mode Tambah/Ubah pada formulir. Tanpa dependensi. */
(function(){
'use strict';
var d=document,dlg,pending,lastBtn,uid=0;
function el(t,c,h){var e=d.createElement(t);if(c)e.className=c;if(h!=null)e.innerHTML=h;return e}
function toast(m){var t=d.getElementById('toast');if(!t){t=el('div','toast');t.id='toast';t.setAttribute('role','status');d.body.appendChild(t)}
 t.classList.remove('on');t.textContent=m;void t.offsetWidth;t.classList.add('on');clearTimeout(t._h);t._h=setTimeout(function(){t.classList.remove('on')},4500)}
function clearErr(f){f.removeAttribute('aria-invalid');var p=d.getElementById((f.id||'x')+'-err');
 if(p){var ds=(f.getAttribute('aria-describedby')||'').replace(p.id,'').trim();if(ds)f.setAttribute('aria-describedby',ds);else f.removeAttribute('aria-describedby');p.remove()}}
function mark(f,msg){clearErr(f);if(!f.id)f.id='fld'+(++uid);var p=el('p','field-error');p.id=f.id+'-err';p.setAttribute('role','alert');p.textContent=msg;
 f.setAttribute('aria-invalid','true');f.setAttribute('aria-describedby',((f.getAttribute('aria-describedby')||'')+' '+p.id).trim());
 f.parentNode.insertBefore(p,f.nextSibling);f.addEventListener('input',function h(){clearErr(f);f.removeEventListener('input',h)})}
function build(){if(dlg)return;dlg=el('dialog','dlg');dlg.setAttribute('aria-labelledby','aksi-t');
 dlg.innerHTML='<h2 id="aksi-t"></h2><p id="aksi-p"></p><div class="field" id="aksi-ask" hidden><label for="aksi-a" id="aksi-al"></label><textarea class="input" id="aksi-a" rows="3"></textarea></div><div class="actions"><button type="button" class="btn btn-o" data-x="no">Batal</button><button type="button" class="btn" id="aksi-ok" data-x="ok"></button></div>';
 d.body.appendChild(dlg);
 dlg.addEventListener('click',function(e){var x=e.target.getAttribute&&e.target.getAttribute('data-x');
  if(x==='no')dlg.close();
  if(x==='ok'){var ta=d.getElementById('aksi-a');if(pending.getAttribute('data-ask')&&!ta.value.trim()){mark(ta,'Alasan wajib diisi.');ta.focus();return}dlg.close();run(pending)}});
 dlg.addEventListener('close',function(){if(lastBtn&&lastBtn.isConnected)lastBtn.focus()})}
function run(b){toast(b.getAttribute('data-done')||'Tindakan disimpan dan dicatat di log aktivitas.');
 var r=b.getAttribute('data-remove');if(r){var t=b.closest(r);if(t)t.remove()}
 var g=b.getAttribute('data-go');if(g)setTimeout(function(){location.href=g},1200)}
d.addEventListener('click',function(e){
 var b=e.target.closest('[data-confirm]');
 if(!b){if(!e.target.closest('details.menu'))d.querySelectorAll('details.menu[open]').forEach(function(x){x.removeAttribute('open')});return}
 e.preventDefault();
 var need=b.getAttribute('data-need');
 if(need){var f=d.querySelector(need);if(f&&!f.value.trim()){mark(f,b.getAttribute('data-need-msg')||'Kolom ini wajib diisi.');f.focus();return}}
 build();pending=b;lastBtn=b;
 d.getElementById('aksi-t').textContent=b.getAttribute('data-confirm')||'Lanjutkan?';
 d.getElementById('aksi-p').textContent=b.getAttribute('data-text')||'';
 var ok=d.getElementById('aksi-ok');ok.textContent=b.getAttribute('data-label')||'Ya, lanjutkan';ok.className='btn'+(b.getAttribute('data-tone')==='bad'?' is-bad':'');
 var q=b.getAttribute('data-ask'),ask=d.getElementById('aksi-ask'),ta=d.getElementById('aksi-a');ask.hidden=!q;ta.value='';clearErr(ta);
 if(q)d.getElementById('aksi-al').innerHTML=q+' <span class="req" aria-hidden="true">*</span>';
 var det=b.closest('details');if(det)det.removeAttribute('open');
 dlg.showModal();if(q)ta.focus()});
d.addEventListener('keydown',function(e){if(e.key==='Escape')d.querySelectorAll('details.menu[open]').forEach(function(x){x.removeAttribute('open')})});
/* Mode formulir: ?mode=tambah → judul, tombol, dan isi awal untuk data baru */
var tambah=new URLSearchParams(location.search).get('mode')==='tambah';
if(tambah){d.documentElement.setAttribute('data-mode','tambah');
 d.querySelectorAll('[data-tambah]').forEach(function(n){n.textContent=n.getAttribute('data-tambah')});
 d.title=d.title.replace(/^(Ubah|Edit)\b/,'Tambah');
 d.querySelectorAll('form[data-form] input,form[data-form] textarea,form[data-form] select').forEach(function(f){
  if(f.hasAttribute('data-keep'))return;var v=f.getAttribute('data-tambah-value');if(v!==null){f.value=v;return}
  if(f.type==='checkbox'||f.type==='radio')f.checked=f.hasAttribute('data-default-on');
  else if(f.tagName==='SELECT')f.selectedIndex=0;
  else if(f.type!=='file'&&f.type!=='submit'&&f.type!=='button')f.value=''})}
d.querySelectorAll('[data-only=ubah]').forEach(function(n){n.hidden=tambah});
d.querySelectorAll('[data-only=tambah]').forEach(function(n){n.hidden=!tambah});
d.addEventListener('submit',function(e){var f=e.target;if(!f.hasAttribute||!f.hasAttribute('data-form'))return;e.preventDefault();
 toast(tambah?(f.getAttribute('data-done-tambah')||'Data baru disimpan.'):(f.getAttribute('data-done')||'Perubahan disimpan.'));
 var a=f.getAttribute('data-after');if(a)setTimeout(function(){location.href=a},1200)});
})();
