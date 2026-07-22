# Prompt: Fitur Drag-and-Drop Reorder Card Project

Tambahkan fitur drag-and-drop untuk reorder card project di dashboard "Systems Monitor" (Blade + Alpine.js + Tailwind, dark theme).

## Requirement

1. Gunakan SortableJS (via CDN atau npm) untuk drag handle di grid card project — card yang di-drag bisa dipindah posisi antar kolom grid (grid saat ini 3 kolom).
2. Saat drag selesai (`onEnd`), kirim urutan baru (array project ID sesuai posisi) ke backend via AJAX/fetch ke endpoint baru, misal `POST /api/projects/reorder`.
3. Backend (Laravel): tambah kolom `order` (integer) di tabel `projects`, buat method `reorder()` di controller yang terima array `{id, position}[]` lalu update kolom `order` masing-masing project dalam 1 transaction.
4. Query list project di dashboard urutkan `orderBy('order')` bukan default id/created_at.
5. Tambah visual feedback saat drag: card yang di-drag jadi semi-transparent, placeholder/ghost element pakai warna accent dashboard (border dashed), cursor jadi `grabbing`.
6. Simpan state optimistically di UI (langsung reorder DOM), tapi kalau request ke server gagal, revert urutan dan tampilkan toast error.
7. Drag handle cukup di area header card (bagian nama project), bukan di seluruh card, supaya tombol refresh/edit/eye di card tetap bisa diklik normal.
8. Pastikan fitur ini tetap jalan walau ada filter/sort aktif (kalau lagi difilter, reorder cukup berlaku untuk project yang ke-filter saat itu, simpan order globalnya tetap konsisten).

## Yang harus ditunjukkan

- Migration
- Model
- Controller
- Route
- File Blade + Alpine.js component