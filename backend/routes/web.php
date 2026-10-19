<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\PeminjamController;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// Admin Punya Kuasa
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

    // Route katak mori
    Route::get('/kategori', [AdminController::class, 'indexKategori'])->name('kategori.index');
    Route::get('/kategori/create', [AdminController::class, 'createKategori'])->name('kategori.create');
    Route::post('/kategori', [AdminController::class, 'storeKategori'])->name('kategori.store');
    Route::get('/kategori/{id}/edit', [AdminController::class, 'editKategori'])->name('kategori.edit');
    Route::put('/kategori/{id}', [AdminController::class, 'updateKategori'])->name('kategori.update');
    Route::delete('/kategori/{id}', [AdminController::class, 'destroyKategori'])->name('kategori.destroy');
    Route::get('/kategori/{id}', [AdminController::class, 'showKategori'])->name('kategori.show');

    // CRUD lupa balikin
    Route::get('/pengembalian', [AdminController::class, 'indexPengembalian'])->name('pengembalian.index');
    Route::get('/pengembalian/create', [AdminController::class, 'createPengembalian'])->name('pengembalian.create');
    Route::post('/pengembalian', [AdminController::class, 'storePengembalian'])->name('pengembalian.store');
    Route::delete('/pengembalian/{id}', [AdminController::class, 'destroyPengembalian'])->name('pengembalian.destroy');

    //CRUD gak modal
    Route::get('/peminjaman', [AdminController::class, 'indexPeminjaman'])->name('peminjaman.index');
    Route::get('/peminjaman/create', [AdminController::class, 'createPeminjaman'])->name('peminjaman.create');
    Route::post('/peminjaman', [AdminController::class, 'storePeminjaman'])->name('peminjaman.store');
    Route::get('/peminjaman/{id}', [AdminController::class, 'showPeminjaman'])->name('peminjaman.show');

    // Search User & Alat untuk Form Peminjaman
    Route::get('/search/users', [AdminController::class, 'searchUser'])->name('search.users');
    Route::get('/search/alats', [AdminController::class, 'searchAlat'])->name('search.alats');
    Route::put('/peminjaman/{id}/status', [AdminController::class, 'updateStatusPeminjaman'])->name('peminjaman.updateStatus');
    Route::delete('/peminjaman/{id}', [AdminController::class, 'destroyPeminjaman'])->name('peminjaman.destroy');

    // CRUD Alat
    Route::get('/alat', [AdminController::class, 'indexAlat'])->name('alat.index');
    Route::post('/alat', [AdminController::class, 'storeAlat'])->name('alat.store');
    Route::get('/alat/create', [AdminController::class, 'createAlat'])->name('alat.create');
    Route::get('/alat/{id}/edit', [AdminController::class, 'editAlat'])->name('alat.edit');
    Route::put('/alat/{id}', [AdminController::class, 'updateAlat'])->name('alat.update');
    Route::delete('/alat/{id}', [AdminController::class, 'destroyAlat'])->name('alat.destroy');
    Route::get('/alat/{id}', [AdminController::class, 'showAlat'])->name('alat.show');

    // CRUD User
    Route::get('/users', [AdminController::class, 'indexUser'])->name('user.index');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('user.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('user.store');
    Route::get('/users/{id}/edit', [AdminController::class, 'editUser'])->name('user.edit');
    Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('user.update');
    Route::delete('/users/{id}', [AdminController::class, 'destroyUser'])->name('user.destroy');
    Route::get('/users/{id}', [AdminController::class, 'showUser'])->name('user.show');

    // Log Aktivitas
    Route::get('/log-aktivitas', [AdminController::class, 'logAktivitas'])->name('log.index');

    // Laporan
    Route::get('/laporan', [AdminController::class, 'indexLaporan'])->name('laporan.index');
    Route::get('/laporan/pdf', [AdminController::class, 'cetakLaporan'])->name('laporan.pdf');

});

// Para Pekerja (Petugas)
Route::middleware(['auth', 'role:petugas'])->prefix('petugas')->name('petugas.')->group(function () {
    Route::get('/peminjaman', [PetugasController::class, 'indexPeminjaman'])->name('peminjaman.index');
    Route::post('/peminjaman/{id}/setujui', [PetugasController::class, 'setujuiPeminjaman'])->name('peminjaman.setujui');
    Route::post('/peminjaman/{id}/tolak', [PetugasController::class, 'tolakPeminjaman'])->name('peminjaman.tolak');
    Route::get('/pengembalian', [PetugasController::class, 'indexPengembalian'])->name('pengembalian.index');
    Route::post('/pengembalian/{id}', [PetugasController::class, 'prosesPengembalian'])->name('pengembalian.proses');
    Route::get('/laporan', [PetugasController::class, 'indexLaporan'])->name('laporan.index');
    // Route Pedofil
    Route::get('/laporan/pdf', [PetugasController::class, 'cetakLaporan'])->name('laporan.pdf');
});

// Peminjam gak modal
Route::middleware(['auth', 'role:peminjam'])->prefix('peminjam')->name('peminjam.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [PeminjamController::class, 'dashboard'])
        ->name('dashboard');
    // Katalog Alat
    Route::get('/katalog', [PeminjamController::class, 'katalogAlat'])
        ->name('katalog');
    // Ajukan Peminjaman
    Route::post('/peminjaman/ajukan', [PeminjamController::class, 'ajukanPeminjaman'])
        ->name('peminjaman.ajukan');
    // Riwayat Peminjaman
    Route::get('/riwayat', [PeminjamController::class, 'riwayatPeminjaman'])
        ->name('riwayat');
    // Peminjaman
    Route::get('/peminjaman', [PeminjamController::class, 'peminjaman'])
        ->name('peminjaman');
    Route::get('/peminjaman', [PeminjamController::class, 'peminjaman'])
        ->name('peminjaman.index');
    Route::get('/pengembalian', [PeminjamController::class, 'pengembalian'])
        ->name('pengembalian.index');
    Route::post('/pengembalian/{peminjaman}/ajukan', [PeminjamController::class, 'ajukanPengembalian'])
        ->name('pengembalian.ajukan');

});


// Route Tamu Tak Dikenal
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Route Logout (Harus sudah login)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');