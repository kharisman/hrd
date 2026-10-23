{{-- Wadah toast (notifikasi kecil pojok kanan bawah) + template-nya. Dipakai hrd.js: HRD.toast(pesan, jenis, aksiCobaLagi) --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="wadahToast" style="z-index:1090"></div>
<template id="tplToast">
    <div class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body"><i class="bi me-1"></i><span class="pesan"></span>
                <button type="button" class="btn btn-sm btn-light ms-2 coba-lagi d-none"><i class="bi bi-arrow-clockwise me-1"></i>Coba lagi</button>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
        </div>
    </div>
</template>
