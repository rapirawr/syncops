# Prompt: Improve UI/UX Animasi Drag-and-Drop Card Project

Perbaiki UI/UX dan animasi untuk fitur drag-and-drop reorder card project di dashboard "Systems Monitor" (Blade + Alpine.js + Tailwind, dark theme, base warna gelap dengan accent hijau/biru).

## Requirement Animasi & Interaksi

1. **Saat drag dimulai (`onStart`):**
   - Card yang di-drag: scale up sedikit (`scale-105`), tambah `shadow-2xl`, opacity turun ke ~0.8.
   - Card lain di grid: transisi smooth (`transition-all duration-200`) saat geser posisi buat kasih tempat, bukan lompat instan.
   - Cursor berubah jadi `grabbing`, drag handle default `grab`.

2. **Ghost/placeholder element:**
   - Placeholder tempat card akan didrop pakai border dashed dengan warna accent dashboard, background sedikit transparan (`bg-white/5`), bukan blok kosong polos.
   - Ukuran placeholder sama persis dengan card asli supaya grid tidak "kedut".

3. **Saat drag berakhir (`onEnd`):**
   - Card yang baru menetap kasih efek "settle" singkat: sedikit bounce/scale balik ke normal (pakai easing `ease-out`, durasi ~150-200ms).
   - Kalau reorder sukses ke server: kasih indikator halus (misal border accent kedip sebentar atau small checkmark toast pojok kanan bawah) — jangan pakai alert/modal yang mengganggu.
   - Kalau gagal: card yang gagal reorder animasi kembali ke posisi lama (bukan langsung snap), lalu toast error muncul dari bawah.

4. **Auto-scroll saat drag mendekati tepi viewport** (kalau grid project banyak dan perlu scroll), supaya user bisa drag card ke row paling bawah/atas tanpa harus lepas drag dulu.

5. **Drag handle indicator:**
   - Tampilkan icon grip/drag handle (misal `⠿` atau icon dari lucide-react/heroicons) yang muncul saat hover di header card, dengan transisi fade-in halus (`opacity-0 group-hover:opacity-100 transition-opacity`).
   - Jangan tampilkan permanen supaya UI tetap bersih saat tidak sedang reorder.

6. **Mobile/touch support:**
   - Pastikan delay kecil (misal 150ms) sebelum drag aktif di touch device supaya tidak konflik dengan scroll.
   - Haptic feedback opsional kalau memungkinkan (browser API terbatas, skip kalau tidak applicable).

7. **Reduce motion:**
   - Hormati `prefers-reduced-motion` — kalau aktif, matikan animasi scale/bounce, ganti dengan transisi opacity simpel saja.

8. **Konsistensi tema:**
   - Semua warna transisi/highlight harus pakai token warna yang sudah ada di dashboard (jangan hardcode warna baru), supaya konsisten dengan skema dark theme + accent yang dipakai di card status (healthy/degraded/warning).

## Yang harus ditunjukkan

- CSS/Tailwind classes tambahan (transition, animation keyframes kalau custom)
- Konfigurasi SortableJS yang relevan (animation duration, ghostClass, chosenClass, dragClass)
- Snippet Alpine.js untuk handle state saat start/end/error
- Toast/feedback component (kalau belum ada, buat versi minimal reusable)