/**
 * HRD ITB PalComTech — AJAX untuk semua form & navigasi (satu modul, dipakai semua halaman).
 *
 * - Form & tautan internal dimuat lewat fetch; yang diganti hanya #halaman (judul, isi) dan menu.
 *   Server tetap mengembalikan halaman HTML biasa, jadi controller tidak perlu diubah.
 * - Setiap proses dibungkus try/catch; gagal → toast berisi pesan + tombol "Coba lagi".
 * - Retry otomatis hanya untuk GET (aman diulang) saat jaringan putus / 502-504.
 *   POST tidak diulang otomatis supaya data tidak tersimpan ganda.
 * - Bilah progres di atas, tombol jadi spinner, dan skeleton di area konten saat memuat.
 * - Lewati AJAX: data-no-ajax, target=_blank, download, unduhan (pdf/export/berkas/template).
 */
const HRD = (() => {
    const RETRY_GET = 2;                 // percobaan ulang otomatis untuk GET
    const STATUS_ULANG = [502, 503, 504];
    const POLA_UNDUHAN = /\/pdf(\?|$)|[?&]export=|\/berkas|\/template(\?|$)|\/validasi\//;
    let urutan = 0;                      // muatan terbaru menang (klik cepat beruntun)

    // ---------- Bilah progres ----------
    const bar = () => document.getElementById('progres');
    let detak, sembunyi;
    function progresMulai() {
        const b = bar(); if (!b) return;
        clearInterval(detak);
        clearTimeout(sembunyi);          // muatan baru sebelum bilah lama sempat disembunyikan
        b.style.transition = 'none'; b.style.width = '0'; b.style.opacity = '1';
        requestAnimationFrame(() => { b.style.transition = 'width .3s ease'; b.style.width = '25%'; });
        let w = 25;
        detak = setInterval(() => { w += (90 - w) * 0.08; b.style.width = w + '%'; }, 300); // mendekati 90%, tidak pernah penuh sebelum selesai
    }
    function progresSelesai() {
        const b = bar(); if (!b) return;
        clearInterval(detak);
        b.style.width = '100%';
        sembunyi = setTimeout(() => { b.style.opacity = '0'; }, 250);
    }

    // ---------- Toast ----------
    function toast(pesan, jenis = 'danger', cobaLagi = null) {
        const tpl = document.getElementById('tplToast'), wadah = document.getElementById('wadahToast');
        if (!tpl || !wadah) { alert(pesan); return; }
        const el = tpl.content.firstElementChild.cloneNode(true);
        el.classList.add('text-bg-' + jenis);
        el.querySelector('.bi').classList.add(jenis === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle');
        el.querySelector('.pesan').textContent = pesan;
        if (cobaLagi) {
            const tombol = el.querySelector('.coba-lagi');
            tombol.classList.remove('d-none');
            tombol.addEventListener('click', () => { bootstrap.Toast.getOrCreateInstance(el).hide(); cobaLagi(); });
        }
        wadah.appendChild(el);
        el.addEventListener('hidden.bs.toast', () => el.remove());
        bootstrap.Toast.getOrCreateInstance(el, { delay: cobaLagi ? 15000 : 5000 }).show();
    }

    // ---------- Tombol: spinner selama proses ----------
    function tombolSibuk(tombol) {
        if (!tombol || tombol.dataset.sibuk) return () => {};
        const asli = tombol.innerHTML, lebar = tombol.offsetWidth;
        tombol.dataset.sibuk = '1';
        tombol.disabled = true;
        tombol.style.minWidth = lebar + 'px';
        tombol.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Memproses...';
        return () => { tombol.innerHTML = asli; tombol.disabled = false; delete tombol.dataset.sibuk; tombol.style.minWidth = ''; };
    }

    // ---------- Skeleton di area konten ----------
    function pasangSkeleton() {
        const main = document.querySelector('#halaman main'), tpl = document.getElementById('tplSkeleton');
        if (!main || !tpl) return () => {};
        const asli = main.innerHTML;
        main.innerHTML = tpl.innerHTML;
        return () => { main.innerHTML = asli; };   // dipakai bila gagal: kembalikan isi semula
    }

    // ---------- fetch dengan try/catch + retry ----------
    const tunggu = (ms) => new Promise((r) => setTimeout(r, ms));
    async function ambil(url, opsi = {}) {
        const bolehUlang = (opsi.method || 'GET').toUpperCase() === 'GET';
        for (let coba = 0; ; coba++) {
            try {
                const res = await fetch(url, { credentials: 'same-origin', ...opsi, headers: { 'X-HRD-Ajax': '1', Accept: 'text/html', ...(opsi.headers || {}) } });
                if (bolehUlang && STATUS_ULANG.includes(res.status) && coba < RETRY_GET) {
                    await tunggu(800 * (coba + 1));
                    continue;
                }
                return res;
            } catch (err) {               // jaringan putus / server tidak menjawab
                if (bolehUlang && coba < RETRY_GET) { await tunggu(800 * (coba + 1)); continue; }
                throw new Error('Tidak bisa terhubung ke server. Periksa koneksi internet Anda.');
            }
        }
    }

    // ---------- Muat halaman & tukar isi ----------
    async function muat(url, opsi = {}, { simpanRiwayat = true, tombol = null } = {}) {
        const nomor = ++urutan;
        const isPost = (opsi.method || 'GET').toUpperCase() !== 'GET';
        const lepasTombol = tombolSibuk(tombol);
        let batalSkeleton = () => {};
        const timerSkeleton = setTimeout(() => { if (!isPost) batalSkeleton = pasangSkeleton(); }, 150); // hindari kedip untuk muatan cepat
        progresMulai();

        try {
            const res = await ambil(url, opsi);

            if (res.status === 419) {     // token CSRF kedaluwarsa (halaman terlalu lama dibuka)
                toast('Sesi halaman kedaluwarsa, memuat ulang...', 'warning');
                setTimeout(() => location.reload(), 1200);
                return;
            }
            const jenis = res.headers.get('content-type') || '';
            if (!jenis.includes('text/html')) { location.href = res.url; return; }   // unduhan tak terduga → biarkan browser

            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            if (nomor !== urutan) return;                                            // sudah ada muatan yang lebih baru

            const baru = doc.getElementById('halaman');
            if (!baru) {
                if (res.ok || res.redirected) { location.href = res.url; return; }   // mis. dialihkan ke halaman login
                throw new Error(pesanGalat(res.status, doc));
            }

            clearTimeout(timerSkeleton);
            tukar(doc, baru);
            if (simpanRiwayat && res.url !== location.href) history.pushState({}, '', res.url);
            else if (isPost) history.replaceState({}, '', res.url);
            if (!isPost || !doc.querySelector('#halaman .alert-danger')) window.scrollTo({ top: 0, behavior: 'instant' });
        } catch (err) {
            clearTimeout(timerSkeleton);
            batalSkeleton();
            console.error('[HRD]', err);
            toast(err.message || 'Terjadi kesalahan.', 'danger', () => muat(url, ulangOpsi(opsi), { simpanRiwayat, tombol }));
        } finally {
            progresSelesai();
            lepasTombol();
        }
    }

    // FormData hanya bisa dikirim sekali → salin untuk "Coba lagi"
    function ulangOpsi(opsi) {
        if (!(opsi.body instanceof FormData)) return opsi;
        const salin = new FormData();
        for (const [k, v] of opsi.body.entries()) salin.append(k, v);
        return { ...opsi, body: salin };
    }

    function pesanGalat(status, doc) {
        const judul = doc.querySelector('title')?.textContent?.trim();
        return ({
            403: 'Anda tidak punya akses untuk aksi ini.',
            404: 'Halaman atau data tidak ditemukan.',
            429: 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.',
            500: 'Terjadi kesalahan di server.',
        })[status] || ((judul && judul.length < 120 ? judul : 'Gagal memuat') + ` (kode ${status}).`);
    }

    function tukar(doc, baru) {
        document.title = doc.title;
        document.getElementById('halaman').replaceWith(baru);
        const menuLama = document.getElementById('menu'), menuBaru = doc.getElementById('menu');
        if (menuLama && menuBaru) {
            const posisi = menuLama.scrollTop;          // pertahankan posisi scroll sidebar
            menuLama.replaceWith(menuBaru);
            menuBaru.scrollTop = posisi;
        }
        bootstrap.Offcanvas.getInstance(document.getElementById('sidebar'))?.hide();
        jalankanSkrip(baru);
        inisialisasi(baru);
    }

    // Skrip di dalam konten baru tidak otomatis jalan bila disisipkan → buat ulang elemennya
    function jalankanSkrip(akar) {
        akar.querySelectorAll('script').forEach((lama) => {
            const s = document.createElement('script');
            [...lama.attributes].forEach((a) => s.setAttribute(a.name, a.value));
            s.textContent = lama.textContent;
            lama.replaceWith(s);
        });
    }

    function inisialisasi(akar = document) {
        akar.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => bootstrap.Tooltip.getOrCreateInstance(el));
    }

    // ---------- Penangkap event ----------
    function lewati(el) {
        return el.closest('[data-no-ajax]') || el.target === '_blank' || el.hasAttribute('download');
    }

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (e.defaultPrevented || lewati(form)) return;     // mis. dibatalkan oleh confirm()
        const url = new URL(form.action || location.href, location.href);
        if (url.origin !== location.origin || POLA_UNDUHAN.test(url.pathname + url.search)) return;
        e.preventDefault();

        const tombol = e.submitter || form.querySelector('button:not([type=button])');
        if ((form.method || 'get').toLowerCase() === 'get') {
            url.search = new URLSearchParams(new FormData(form)).toString();
            muat(url.toString(), {}, { tombol: e.submitter });
        } else {
            muat(url.toString(), { method: 'POST', body: new FormData(form, e.submitter) }, { tombol });
        }
    });

    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href]');
        if (!a || e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
        if (lewati(a) || a.hasAttribute('data-bs-toggle') || a.getAttribute('href').startsWith('#')) return;
        const url = new URL(a.href, location.href);
        if (url.origin !== location.origin || POLA_UNDUHAN.test(url.pathname + url.search)) return;
        if (url.pathname === location.pathname && url.search === location.search && url.hash) return; // lompat ke anchor
        e.preventDefault();
        muat(url.toString());
    });

    window.addEventListener('popstate', () => muat(location.href, {}, { simpanRiwayat: false }));

    // onchange="this.form.submit()" melewati event submit → arahkan ke requestSubmit supaya ikut AJAX
    const kirimAsli = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () {
        if (this.requestSubmit && !lewati(this)) return this.requestSubmit();
        return kirimAsli.call(this);
    };

    document.addEventListener('DOMContentLoaded', () => inisialisasi());
    return { muat, toast };
})();
