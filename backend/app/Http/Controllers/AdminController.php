<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Kategori;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Models\Pengembalian;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\Peminjaman;
use App\Models\DetailPinjam;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminController extends Controller
{
    // Menampilkan Dashboard Admin & Log Aktivitas
    public function index()
    {
        $totalUser = User::count();
        $totalAlat = Alat::count();
        $totalPeminjaman = Peminjaman::count();
        $menungguPersetujuan = Peminjaman::where('status', 'diajukan')->count();
        $sedangDipinjam = Peminjaman::where('status', 'dipinjam')->count();
        $peminjamanSelesai = Peminjaman::where('status', 'dikembalikan')->count();
        $logsTerbaru = LogAktivitas::with('user')
            ->latest()
            ->take(5)
            ->get();
        return view('admin.dashboard', compact(
            'totalUser',
            'totalAlat',
            'totalPeminjaman',
            'menungguPersetujuan',
            'sedangDipinjam',
            'peminjamanSelesai',
            'logsTerbaru'
        ));
    }

    // CRUD Alat: Menampilkan daftar alat
public function indexAlat(Request $request)
{
    $search = $request->input('search');
    $kategori = $request->input('kategori');
    $kondisi = $request->input('kondisi');
    $stok = $request->input('stok');
    $sort = $request->input('sort', 'latest');

    $alats = Alat::with('kategori')
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_alat', 'like', "%{$search}%")
                    ->orWhere('status_kondisi', 'like', "%{$search}%")
                    ->orWhereHas('kategori', function ($q) use ($search) {
                        $q->where('nama_kategori', 'like', "%{$search}%");
                    });
            });
        })
        ->when($kategori, function ($query, $kategori) {
            $query->where('kategori_id', $kategori);
        })
        ->when($kondisi, function ($query, $kondisi) {
            $query->where('status_kondisi', $kondisi);
        })
        ->when($stok === 'tersedia', function ($query) {
            $query->where('stok', '>', 0);
        })
        ->when($stok === 'habis', function ($query) {
            $query->where('stok', 0);
        });

    // Sort
    if ($sort === 'oldest') {
        $alats->oldest();
    } elseif ($sort === 'name_asc') {
        $alats->orderBy('nama_alat', 'asc');
    } elseif ($sort === 'name_desc') {
        $alats->orderBy('nama_alat', 'desc');
    } elseif ($sort === 'stok_desc') {
        $alats->orderBy('stok', 'desc');
    } elseif ($sort === 'stok_asc') {
        $alats->orderBy('stok', 'asc');
    } else {
        $alats->latest();
    }

    $alats = $alats
        ->paginate(10)
        ->withQueryString();

    $kategoris = Kategori::orderBy('nama_kategori')->get();

    return view('admin.alat.index', compact(
        'alats',
        'kategoris',
        'search',
        'kategori',
        'kondisi',
        'stok',
        'sort'
    ));
}

// 2. Menampilkan form tambah alat
public function createAlat()
{
    $kategoris = Kategori::all();

    return view('admin.alat.create', compact('kategoris'));
}

    // 3. Menyimpan alat baru
public function storeAlat(Request $request)
{
    $request->validate([
        'nama_alat' => 'required|string|max:255',
        'kategori_id' => 'required|exists:kategori,id',
        'stok' => 'required|integer|min:0',
        'status_kondisi' => 'required|string|max:100',
        'deskripsi' => 'nullable|string',
        'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    $data = $request->all();

    // Handle Upload Gambar jika ada
    if ($request->hasFile('gambar')) {
        $file = $request->file('gambar');
        $filename = time() . '_' . $file->getClientOriginalName();

        $file->move(public_path('storage/alat'), $filename);

        $data['gambar'] = 'storage/alat/' . $filename;
    }

    Alat::create($data);

    $this->catatAktivitas(
        "Menambahkan alat '{$request->nama_alat}'."
    );

    return redirect()
        ->route('admin.alat.index')
        ->with('success', 'Data alat berhasil ditambahkan.');
}

// 4. Menampilkan form edit alat
public function editAlat($id)
{
    $alat = Alat::findOrFail($id);
    $kategoris = Kategori::all();

    return view('admin.alat.edit', compact('alat', 'kategoris'));
}

// 5. Memperbarui data alat
public function updateAlat(Request $request, $id)
{
    $alat = Alat::findOrFail($id);

    $request->validate([
        'nama_alat' => 'required|string|max:255',
        'kategori_id' => 'required|exists:kategori,id',
        'stok' => 'required|integer|min:0',
        'status_kondisi' => 'required|string|max:100',
        'deskripsi' => 'nullable|string',
        'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    $data = $request->all();

    // Handle Upload Gambar jika ada file baru
    if ($request->hasFile('gambar')) {

        // Hapus gambar lama jika ada
        if ($alat->gambar && file_exists(public_path($alat->gambar))) {
            unlink(public_path($alat->gambar));
        }

        $file = $request->file('gambar');
        $filename = time() . '_' . $file->getClientOriginalName();

        $file->move(public_path('storage/alat'), $filename);

        $data['gambar'] = 'storage/alat/' . $filename;
    }

    $alat->update($data);

    $this->catatAktivitas(
        "Mengubah data alat '{$alat->nama_alat}'."
    );

    return redirect()
        ->route('admin.alat.index')
        ->with('success', 'Data alat berhasil diperbarui.');
}

// 6. Menghapus data alat
public function destroyAlat($id)
{
    $alat = Alat::findOrFail($id);

    $adaPinjamanAktif = \App\Models\DetailPinjam::where('alat_id', $alat->id)
        ->whereHas('peminjaman', function ($q) {
            $q->whereIn('status', ['diajukan', 'dipinjam', 'telat', 'pengajuan_kembali']);
        })
        ->exists();

    if ($adaPinjamanAktif) {
        return back()->with('error', 'Alat tidak bisa dihapus karena sedang dalam peminjaman aktif.');
    }

    if ($alat->gambar && file_exists(public_path($alat->gambar))) {
        unlink(public_path($alat->gambar));
    }

    $alat->delete();

    $this->catatAktivitas(
        "Menghapus alat '{$alat->nama_alat}'."
    );

    return redirect()
        ->route('admin.alat.index')
        ->with('success', 'Data alat berhasil dihapus.');
}

public function showAlat($id)
{
    $alat = Alat::with('kategori')->findOrFail($id);

    $riwayatPeminjaman = DetailPinjam::with([
        'peminjaman.user',
        'peminjaman.pengembalian'
    ])
        ->where('alat_id', $alat->id)
        ->latest()
        ->paginate(5)
        ->withQueryString();

    $totalDipinjam = DetailPinjam::where('alat_id', $alat->id)
        ->sum('jumlah');

    return view('admin.alat.show', compact(
        'alat',
        'riwayatPeminjaman',
        'totalDipinjam'
    ));
}

// PENGEMBALIAN

// 1. Menampilkan daftar pengembalian
public function indexPengembalian(Request $request)
{
    $search = $request->input('search');

    $pengembalians = \App\Models\Pengembalian::with([
        'peminjaman.user',
        'peminjaman.detailPinjam.alat',
        'petugas'
    ])
    ->when($search, function ($query, $search) {
        $query->whereHas('peminjaman.user', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    })
    ->latest()
    ->paginate(10)
    ->withQueryString();

    return view('admin.pengembalian.index', compact(
        'pengembalians',
        'search'
    ));
}

// 2. Menampilkan form pengembalian
public function createPengembalian()
{
    $peminjamans = Peminjaman::with([
        'user',
        'detailPinjam.alat'
    ])
        ->whereIn('status', ['dipinjam', 'telat', 'dikembalikan'])
        ->whereDoesntHave('pengembalian')
        ->latest()
        ->get();

    return view(
        'admin.pengembalian.create',
        compact('peminjamans')
    );
}

// 3. Menyimpan data pengembalian
public function storePengembalian(Request $request)
{
    $request->validate([
        'peminjaman_id' => 'required|exists:peminjaman,id',
        'tgl_kembali' => 'required|date',
        'kondisi_kembali' => 'required|string|max:100',
        'denda_kerusakan' => 'nullable|integer|min:0',
    ]);

    DB::beginTransaction();

    try {

        $peminjaman = Peminjaman::with('detailPinjam.alat')
            ->findOrFail($request->peminjaman_id);

        // Pastikan hanya peminjaman aktif yang dapat dikembalikan
        if (!in_array($peminjaman->status, ['dipinjam', 'telat'])) {
            throw new \Exception(
                'Peminjaman ini tidak dapat diproses sebagai pengembalian.'
            );
        }

        // Pastikan belum pernah memiliki data pengembalian
        if ($peminjaman->pengembalian()->exists()) {
            throw new \Exception(
                'Peminjaman ini sudah memiliki data pengembalian.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Hitung keterlambatan
        |--------------------------------------------------------------------------
        */

        $tglRencana = \Carbon\Carbon::parse(
            $peminjaman->tgl_kembali_plan
        );

        $tglKembali = \Carbon\Carbon::parse(
            $request->tgl_kembali
        );

        $hariTerlambat = 0;

        if ($tglKembali->greaterThan($tglRencana)) {
            $hariTerlambat = $tglRencana->diffInDays($tglKembali);
        }

        // Rp1.000 per hari
        $dendaKeterlambatan = $hariTerlambat * 1000;

        // Denda kerusakan ditentukan admin/petugas
        $dendaKerusakan = $request->denda_kerusakan ?? 0;

        // Total denda
        $totalDenda = $dendaKeterlambatan + $dendaKerusakan;

        /*
        |--------------------------------------------------------------------------
        | Simpan pengembalian
        |--------------------------------------------------------------------------
        */

        $pengembalian = \App\Models\Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => $request->tgl_kembali,
            'kondisi_kembali' => $request->kondisi_kembali,
            'denda' => $totalDenda,
            'petugas_id' => auth()->id(),
        ]);

        $this->catatAktivitas(
            "Mencatat pengembalian peminjaman #{$peminjaman->id}."
        );

        /*
        |--------------------------------------------------------------------------
        | Kembalikan stok alat
        |--------------------------------------------------------------------------
        */

        foreach ($peminjaman->detailPinjam as $detail) {
            $detail->alat->increment('stok', $detail->jumlah);
        }

        /*
        |--------------------------------------------------------------------------
        | Ubah status peminjaman
        |--------------------------------------------------------------------------
        */

        $peminjaman->update([
            'status' => 'dikembalikan',
        ]);

        $this->catatAktivitas(
            "Mengubah data pengembalian #{$pengembalian->id}."
        );

        DB::commit();

        return redirect()
            ->route('admin.pengembalian.index')
            ->with('success', 'Pengembalian berhasil dicatat.');

    } catch (\Exception $e) {

        DB::rollBack();

        return back()
            ->withInput()
            ->with('error', $e->getMessage());
    }
}

// 4. Menghapus data pengembalian
public function destroyPengembalian($id)
{
    DB::beginTransaction();

    try {

        $pengembalian = Pengembalian::with([
            'peminjaman.detailPinjam.alat'
        ])->findOrFail($id);

        $peminjaman = $pengembalian->peminjaman;

        // Karena pengembalian dibatalkan,
        // stok harus dikurangi kembali.
        foreach ($peminjaman->detailPinjam as $detail) {

            $alat = $detail->alat;

            if ($alat->stok < $detail->jumlah) {
                throw new \Exception(
                    "Stok alat '{$alat->nama_alat}' tidak mencukupi untuk membatalkan pengembalian."
                );
            }

            $alat->decrement('stok', $detail->jumlah);
        }

        // Kembalikan status peminjaman
        $peminjaman->update([
            'status' => 'dipinjam'
        ]);

        // Hapus data pengembalian
        $pengembalian->delete();

        $this->catatAktivitas(
            "Menghapus data pengembalian #{$pengembalian->id}."
        );

        DB::commit();

        return redirect()
            ->route('admin.pengembalian.index')
            ->with(
                'success',
                'Data pengembalian berhasil dihapus dan status peminjaman dikembalikan menjadi dipinjam.'
            );

    } catch (\Exception $e) {

        DB::rollBack();

        return back()->with(
            'error',
            $e->getMessage()
        );
    }
}

public function updatePengembalian(Request $request, $id)
{
    $request->validate([
        'tgl_kembali' => 'required|date',
        'kondisi_kembali' => 'required|in:Baik,Rusak Ringan,Rusak Berat',
        'denda_kerusakan' => 'nullable|integer|min:0',
    ]);

    DB::beginTransaction();

    try {

        $pengembalian = Pengembalian::with('peminjaman')
            ->findOrFail($id);

        $peminjaman = $pengembalian->peminjaman;

        $tglKembali = Carbon::parse($request->tgl_kembali);
        $tglRencana = Carbon::parse($peminjaman->tgl_kembali_plan);

        $hariTerlambat = 0;

        if ($tglKembali->greaterThan($tglRencana)) {
            $hariTerlambat = $tglRencana->diffInDays($tglKembali);
        }

        // Rp1.000 per hari
        $dendaKeterlambatan = $hariTerlambat * 1000;

        // Denda kerusakan manual
        $dendaKerusakan = (int) ($request->denda_kerusakan ?? 0);

        // Total
        $totalDenda = $dendaKeterlambatan + $dendaKerusakan;

        $pengembalian->update([
            'tgl_kembali' => $request->tgl_kembali,
            'kondisi_kembali' => $request->kondisi_kembali,
            'denda_kerusakan' => $dendaKerusakan,
            'denda' => $totalDenda,
        ]);

        DB::commit();

        return redirect()
            ->route('admin.pengembalian.index')
            ->with('success', 'Data pengembalian berhasil diperbarui.');

    } catch (\Exception $e) {

        DB::rollBack();

        return back()
            ->withInput()
            ->with('error', $e->getMessage());
    }
}

public function editPengembalian($id)
{
    $pengembalian = Pengembalian::with([
        'peminjaman.user',
        'peminjaman.detailPinjam.alat'
    ])->findOrFail($id);

    return view(
        'admin.pengembalian.edit',
        compact('pengembalian')
    );
}

// 7. Memproses pengembalian
public function prosesPengembalian($id)
{
    $peminjaman = Peminjaman::with('detailPinjam.alat')
        ->findOrFail($id);

    // Pastikan hanya peminjaman yang sedang dipinjam atau telat
    // yang bisa diproses pengembaliannya
    if (!in_array($peminjaman->status, ['dipinjam', 'telat', 'pengajuan_kembali'])) {
        return back()->with(
            'error',
            'Peminjaman ini tidak dapat diproses sebagai pengembalian.'
        );
    }

    if (\App\Models\Pengembalian::where('peminjaman_id', $peminjaman->id)->exists()) {
        return back()->with(
            'error',
            'Pengembalian untuk peminjaman ini sudah dicatat.'
        );
    }

    DB::beginTransaction();

    try {
        \App\Models\Pengembalian::create([
            'peminjaman_id' => $peminjaman->id,
            'tgl_kembali' => now(),
            'kondisi_kembali' => 'baik',
            'denda' => 0,
            'denda_kerusakan' => 0,
            'petugas_id' => auth()->id(),
        ]);

        foreach ($peminjaman->detailPinjam as $detail) {
            $alat = \App\Models\Alat::lockForUpdate()->findOrFail($detail->alat_id);
            $alat->increment('stok', $detail->jumlah);
        }

        // Ubah status menjadi dikembalikan
        $peminjaman->update([
            'status' => 'dikembalikan'
        ]);

        DB::commit();

        return redirect()
            ->route('admin.pengembalian.index')
            ->with('success', 'Pengembalian berhasil diproses.');

    } catch (\Exception $e) {
        DB::rollBack();

        return back()->with(
            'error',
            'Pengembalian gagal diproses: ' . $e->getMessage()
        );
    }
}

    // CRUD User (Manajemen User Admin, Petugas, Peminjam)
public function indexUser(Request $request)
{
    $search = $request->input('search');
    $role = $request->input('role');
    $jenisKelamin = $request->input('jenis_kelamin');
    $status = $request->input('status');
    $sort = $request->input('sort', 'latest');

    $users = User::query()
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->when($role, function ($query, $role) {
            $query->where('role', $role);
        })
        ->when($jenisKelamin, function ($query, $jenisKelamin) {
            $query->where('jenis_kelamin', $jenisKelamin);
        })
        ->when($status, function ($query, $status) {
            $query->where('status', $status);
        });

    // Sort user
    if ($sort === 'oldest') {
        $users->oldest();
    } elseif ($sort === 'name_asc') {
        $users->orderBy('name', 'asc');
    } elseif ($sort === 'name_desc') {
        $users->orderBy('name', 'desc');
    } else {
        $users->latest();
    }

    $users = $users
        ->paginate(10)
        ->withQueryString();

    $totalUser = User::count();
    $totalAdmin = User::where('role', 'admin')->count();
    $totalPetugas = User::where('role', 'petugas')->count();
    $totalPeminjam = User::where('role', 'peminjam')->count();

    return view('admin.user.index', compact(
        'users',
        'search',
        'role',
        'jenisKelamin',
        'status',
        'sort',
        'totalUser',
        'totalAdmin',
        'totalPetugas',
        'totalPeminjam'
    ));
}

// Menampilkan Form Tambah User
public function createUser()
{
    return view('admin.user.create');
}

// Menyimpan User Baru
public function storeUser(Request $request)
{
    $request->validate([
        'name'          => 'required|string|max:255',
        'jenis_kelamin' => 'nullable|in:L,P',
        'email'         => 'required|string|email|max:255|unique:users',
        'password'      => 'required|string|min:6',
        'role'          => 'required|in:admin,petugas,peminjam',
        'no_hp'         => 'nullable|string|max:20',
        'foto_profile'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    $data = [
        'name'          => $request->name,
        'jenis_kelamin' => $request->jenis_kelamin,
        'email'         => $request->email,
        'password'      => Hash::make($request->password),
        'role'          => $request->role,
        'status'        => 'aktif',
        'no_hp'         => $request->no_hp,
    ];

    // Upload foto profil jika ada
    if ($request->hasFile('foto_profile')) {

        $file = $request->file('foto_profile');

        $filename = time() . '_' . $file->getClientOriginalName();

        $file->move(
            public_path('storage/profile'),
            $filename
        );

        $data['foto_profile'] = 'storage/profile/' . $filename;
    }

    User::create($data);

    $this->catatAktivitas(
        "Menambahkan user '{$request->name}' dengan role {$request->role}."
    );

    return redirect()
        ->route('admin.user.index')
        ->with('success', 'User berhasil ditambahkan.');
}

// Menampilkan Form Edit User
public function editUser($id)
{
    $user = User::findOrFail($id);

    return view('admin.user.edit', compact('user'));
}

// Detail User
public function showUser($id) {
    $user = User::findOrFail($id);
    $aktivitas = LogAktivitas::where('user_id', $user->id)
        ->latest()
        ->paginate(5);
    return view('admin.user.show', compact('user', 'aktivitas'));
}

// Memperbarui Data User
public function updateUser(Request $request, $id)
{
    $user = User::findOrFail($id);

    $request->validate([
        'name'          => 'required|string|max:255',
        'jenis_kelamin' => 'nullable|in:L,P',
        'email'         => 'required|string|email|max:255|unique:users,email,' . $id,
        'role'          => 'required|in:admin,petugas,peminjam',
        'status'        => 'required|in:aktif,nonaktif',
        'no_hp'         => 'nullable|string|max:20',
        'foto_profile'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    $data = [
        'name'          => $request->name,
        'jenis_kelamin' => $request->jenis_kelamin,
        'email'         => $request->email,
        'role'          => $request->role,
        'status'        => $request->status,
        'no_hp'         => $request->no_hp,
    ];

    // Update password jika diisi
    if ($request->filled('password')) {
        $data['password'] = Hash::make($request->password);
    }

    // Upload foto baru jika ada
    if ($request->hasFile('foto_profile')) {

        // Hapus foto lama
        if (
            $user->foto_profile &&
            file_exists(public_path($user->foto_profile))
        ) {
            unlink(public_path($user->foto_profile));
        }

        $file = $request->file('foto_profile');

        $filename = time() . '_' . $file->getClientOriginalName();

        $file->move(
            public_path('storage/profile'),
            $filename
        );

        $data['foto_profile'] = 'storage/profile/' . $filename;
    }

    $user->update($data);

    $this->catatAktivitas(
        "Mengubah data user '{$user->name}'."
    );

    return redirect()
        ->route('admin.user.index')
        ->with('success', 'Data user berhasil diperbarui.');
}

// Menghapus User
public function destroyUser($id)
{
    $user = User::findOrFail($id);

    // Hapus file foto profil jika ada
    if (
        $user->foto_profile &&
        file_exists(public_path($user->foto_profile))
    ) {
        unlink(public_path($user->foto_profile));
    }

    $user->delete();

    $this->catatAktivitas(
        "Menghapus user '{$user->name}'."
    );

    return redirect()
        ->route('admin.user.index')
        ->with('success', 'User berhasil dihapus.');
}
//KATEGORI

public function indexKategori(Request $request)
{
    $search = $request->input('search');
    $sort = $request->input('sort', 'latest');

    $kategoris = Kategori::withCount('alat')
        ->when($search, function ($query, $search) {
            return $query->where('nama_kategori', 'like', "%{$search}%");
        });

    if ($sort === 'oldest') {
        $kategoris->oldest();
    } elseif ($sort === 'name_asc') {
        $kategoris->orderBy('nama_kategori', 'asc');
    } elseif ($sort === 'name_desc') {
        $kategoris->orderBy('nama_kategori', 'desc');
    } else {
        $kategoris->latest();
    }

    $kategoris = $kategoris
        ->paginate(5)
        ->withQueryString();

    return view('admin.kategori.index', compact(
        'kategoris',
        'search',
        'sort'
    ));
}

public function createKategori()
{
    return view('admin.kategori.create');
}

public function storeKategori(Request $request)
{
    $request->validate([
        'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori',
    ]);

    Kategori::create([
        'nama_kategori' => $request->nama_kategori,
    ]);

    $this->catatAktivitas(
        "Menambahkan kategori '{$request->nama_kategori}'."
    );

    return redirect()
        ->route('admin.kategori.index')
        ->with('success', 'Kategori berhasil ditambahkan.');
}

public function editKategori($id)
{
    $kategori = Kategori::findOrFail($id);

    return view('admin.kategori.edit', compact('kategori'));
}

public function updateKategori(Request $request, $id)
{
    $kategori = Kategori::findOrFail($id);

    $request->validate([
        'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori,' . $id,
    ]);

    $kategori->update([
        'nama_kategori' => $request->nama_kategori,
    ]);

    $this->catatAktivitas(
        "Mengubah data kategori '{$kategori->nama_kategori}'."
    );

    return redirect()
        ->route('admin.kategori.index')
        ->with('success', 'Kategori berhasil diperbarui.');
}

public function destroyKategori($id)
{
    $kategori = Kategori::findOrFail($id);

    // Cek apakah kategori masih digunakan oleh alat
    $jumlahAlat = Alat::where('kategori_id', $kategori->id)->count();

    if ($jumlahAlat > 0) {
        return redirect()
            ->route('admin.kategori.index')
            ->with(
                'error',
                "Kategori '{$kategori->nama_kategori}' tidak dapat dihapus karena masih digunakan oleh {$jumlahAlat} alat."
            );
    }

    $namaKategori = $kategori->nama_kategori;

    $kategori->delete();

    // Catat aktivitas
    $this->catatAktivitas(
        "Menghapus kategori '{$namaKategori}'"
    );

    return redirect()
        ->route('admin.kategori.index')
        ->with('success', 'Kategori berhasil dihapus.');
}

public function showKategori($id)
{
    $kategori = Kategori::findOrFail($id);

    $alats = Alat::where('kategori_id', $kategori->id)
        ->latest()
        ->paginate(10);

    return view('admin.kategori.show', compact(
        'kategori',
        'alats'
    ));
}

// 1. Menampilkan daftar peminjaman
public function indexPeminjaman(Request $request)
{
    $search = $request->input('search');
    $status = $request->input('status');
    $sort = $request->input('sort', 'latest');

    $peminjamans = Peminjaman::with([
        'user',
        'detailPinjam.alat'
    ])
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {

                $q->where('status', 'like', "%{$search}%")

                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    })

                    ->orWhereHas('detailPinjam.alat', function ($q) use ($search) {
                        $q->where('nama_alat', 'like', "%{$search}%");
                    });
            });
        })
        ->when($status, function ($query, $status) {
            $query->where('status', $status);
        });

    // Sorting
    if ($sort === 'oldest') {
        $peminjamans->oldest();
    } elseif ($sort === 'name_asc') {
        $peminjamans->orderBy(
            User::select('name')
                ->whereColumn('users.id', 'peminjaman.user_id'),
            'asc'
        );
    } elseif ($sort === 'name_desc') {
        $peminjamans->orderBy(
            User::select('name')
                ->whereColumn('users.id', 'peminjaman.user_id'),
            'desc'
        );
    } else {
        $peminjamans->latest();
    }

    $peminjamans = $peminjamans
        ->paginate(10)
        ->withQueryString();

    return view('admin.peminjaman.index', compact(
        'peminjamans',
        'search',
        'status',
        'sort'
    ));
}

// 2. Menampilkan form tambah peminjaman
public function createPeminjaman()
{
    $users = User::where('role', 'peminjam')->get(); // Atau ambil semua user jika bebas
    $alats = Alat::where('stok', '>', 0)->get();

    return view('admin.peminjaman.create', compact('users', 'alats'));
}

// 3. Menyimpan data peminjaman baru
public function storePeminjaman(Request $request)
{
    $request->validate([
        'user_id' => [
            'required',
            'exists:users,id',
            function ($attribute, $value, $fail) {
                $user = User::find($value);

                if (!$user || $user->role !== 'peminjam') {
                    $fail('User yang dipilih harus memiliki role peminjam.');
                }

                if ($user && $user->status !== 'aktif') {
                    $fail('User yang dipilih sedang nonaktif.');
                }
            },
        ],

        'tgl_pinjam' => 'required|date',

        'tgl_kembali_plan' => [
            'required',
            'date',
            'after_or_equal:tgl_pinjam',
        ],

        'alat_id' => 'required|array|min:1',

        // Mencegah alat yang sama dipilih lebih dari sekali
        'alat_id.*' => [
            'required',
            'exists:alat,id',
            'distinct',
        ],

        'jumlah' => 'required|array|min:1',

        'jumlah.*' => [
            'required',
            'integer',
            'min:1',
        ],
    ]);

    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | Buat transaksi utama
        |--------------------------------------------------------------------------
        */

        $peminjaman = Peminjaman::create([
            'user_id' => $request->user_id,
            'tgl_pinjam' => $request->tgl_pinjam,
            'tgl_kembali_plan' => $request->tgl_kembali_plan,
            'status' => 'diajukan',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Simpan detail alat
        |--------------------------------------------------------------------------
        */

        foreach ($request->alat_id as $index => $alatId) {

            $jumlahPinjam = (int) $request->jumlah[$index];

            $alat = Alat::findOrFail($alatId);

            /*
            |--------------------------------------------------------------------------
            | Validasi stok
            |--------------------------------------------------------------------------
            */

            if ($alat->stok <= 0) {
                throw new \Exception(
                    "Alat '{$alat->nama_alat}' sedang habis."
                );
            }

            if ($alat->stok < $jumlahPinjam) {
                throw new \Exception(
                    "Stok alat '{$alat->nama_alat}' tidak mencukupi. " .
                    "Stok tersedia: {$alat->stok}."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Simpan detail
            |--------------------------------------------------------------------------
            */

            DetailPinjam::create([
                'peminjaman_id' => $peminjaman->id,
                'alat_id' => $alatId,
                'jumlah' => $jumlahPinjam,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Commit transaksi
        |--------------------------------------------------------------------------
        */

        DB::commit();

        /*
        |--------------------------------------------------------------------------
        | Catat aktivitas
        |--------------------------------------------------------------------------
        */

        $user = User::find($request->user_id);

        $this->catatAktivitas(
            "Menambahkan peminjaman baru untuk user '{$user->name}' (Peminjaman #{$peminjaman->id})."
        );

        return redirect()
            ->route('admin.peminjaman.index')
            ->with(
                'success',
                'Data peminjaman berhasil diajukan.'
            );

    } catch (\Exception $e) {

        DB::rollBack();

        return back()
            ->withInput()
            ->with(
                'error',
                $e->getMessage()
            );
    }
}

public function updateStatusPeminjaman(Request $request, $id)
{
    $peminjaman = Peminjaman::with('detailPinjam.alat')
        ->findOrFail($id);

    $request->validate([
        'status' => 'required|in:diajukan,dipinjam,telat,dikembalikan,pengajuan_kembali',
    ]);

    DB::beginTransaction();

    try {

        $statusLama = $peminjaman->status;
        $statusBaru = $request->status;

        $transisiValid = [
            'diajukan' => ['dipinjam', 'ditolak'],
            'dipinjam' => ['telat', 'dikembalikan', 'pengajuan_kembali'],
            'telat' => ['dikembalikan', 'pengajuan_kembali'],
            'pengajuan_kembali' => ['dikembalikan', 'dipinjam'],
            'dikembalikan' => [],
            'ditolak' => [],
        ];

        if (! in_array($statusBaru, $transisiValid[$statusLama] ?? [], true)) {
            throw new \Exception("Transisi status dari '{$statusLama}' ke '{$statusBaru}' tidak diizinkan.");
        }

        /*
        |--------------------------------------------------------------------------
        | Diajukan → Dipinjam
        |--------------------------------------------------------------------------
        */

        if (
            $statusLama !== 'dipinjam' &&
            $statusBaru === 'dipinjam'
        ) {

            foreach ($peminjaman->detailPinjam as $detail) {

                $alat = $detail->alat;

                if ($alat->stok < $detail->jumlah) {
                    throw new \Exception(
                        "Stok alat '{$alat->nama_alat}' tidak mencukupi untuk dipinjam."
                    );
                }

                $alat->decrement('stok', $detail->jumlah);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Dipinjam / Telat → Dikembalikan
        |--------------------------------------------------------------------------
        */

        if (
            in_array($statusLama, ['dipinjam', 'telat']) &&
            $statusBaru === 'dikembalikan'
        ) {

            // Kembalikan stok
            foreach ($peminjaman->detailPinjam as $detail) {
                $detail->alat->increment('stok', $detail->jumlah);
            }

            /*
            |--------------------------------------------------------------
            | Buat data pengembalian otomatis
            |--------------------------------------------------------------
            */

            if (!$peminjaman->pengembalian()->exists()) {

                $tglKembali = now()->toDateString();

                $tglRencana = \Carbon\Carbon::parse(
                    $peminjaman->tgl_kembali_plan
                );

                $tanggalKembali = \Carbon\Carbon::parse(
                    $tglKembali
                );

                $hariTerlambat = 0;

                if ($tanggalKembali->greaterThan($tglRencana)) {
                    $hariTerlambat = $tglRencana->diffInDays(
                        $tanggalKembali
                    );
                }

                $dendaKeterlambatan = $hariTerlambat * 1000;

                \App\Models\Pengembalian::create([
                    'peminjaman_id' => $peminjaman->id,
                    'tgl_kembali' => $tglKembali,
                    'kondisi_kembali' => 'Baik',
                    'denda' => $dendaKeterlambatan,
                    'petugas_id' => auth()->id(),
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Update status
        |--------------------------------------------------------------------------
        */

        $peminjaman->update([
            'status' => $statusBaru
        ]);

        $this->catatAktivitas(
            "Mengubah status peminjaman #{$peminjaman->id} dari '{$statusLama}' menjadi '{$statusBaru}'."
        );

        DB::commit();

        return redirect()
            ->route('admin.peminjaman.index')
            ->with(
                'success',
                'Status peminjaman berhasil diperbarui.'
            );

    } catch (\Exception $e) {

        DB::rollBack();

        return back()->with(
            'error',
            $e->getMessage()
        );
    }
}

// 5. Menghapus data peminjaman
public function destroyPeminjaman($id)
{
    $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($id);

    // Jika statusnya sedang dipinjam, kembalikan stok terlebih dahulu sebelum dihapus
    if (in_array($peminjaman->status, ['dipinjam', 'telat', 'pengajuan_kembali'], true)) {
        foreach ($peminjaman->detailPinjam as $detail) {
            $detail->alat->increment('stok', $detail->jumlah);
        }
    }

    $peminjaman->delete();

    return redirect()
        ->route('admin.peminjaman.index')
        ->with('success', 'Data peminjaman berhasil dihapus.');
}

public function showPeminjaman($id)
{
    $peminjaman = Peminjaman::with([
        'user',
        'detailPinjam.alat',
        'pengembalian.petugas'
    ])->findOrFail($id);

    return view('admin.peminjaman.show', compact('peminjaman'));
}

public function searchUser(Request $request)
{
    $keyword = $request->input('search');

    if (strlen($keyword) < 3) {
        return response()->json([]);
    }

    $users = User::where('role', 'peminjam')
        ->where(function ($query) use ($keyword) {
            $query->where('name', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%");
        })
        ->limit(10)
        ->get(['id', 'name', 'email']);

    return response()->json($users);
}

public function searchAlat(Request $request)
{
    $keyword = $request->input('search');

    if (strlen($keyword) < 3) {
        return response()->json([]);
    }

    $alats = Alat::where('stok', '>', 0)
        ->where('nama_alat', 'like', "%{$keyword}%")
        ->limit(10)
        ->get(['id', 'nama_alat', 'stok']);

    return response()->json($alats);
}

public function logAktivitas() {
    $logs = LogAktivitas::with('user')
        ->latest()
        ->paginate(15);

    return view('admin.log.index', compact('logs'));
}

private function catatAktivitas($aktivitas) {
    LogAktivitas::create([
        'user_id' => auth()->id(),
        'aktivitas' => $aktivitas,
    ]);
}

public function indexLaporan(Request $request)
{
    // Query dasar peminjaman
    $query = Peminjaman::with([
        'user',
        'detailPinjam.alat'
    ]);

    // Filter tanggal mulai
    if ($request->filled('tanggal_mulai')) {
        $query->whereDate(
            'tanggal_pinjam',
            '>=',
            $request->tanggal_mulai
        );
    }

    // Filter tanggal akhir
    if ($request->filled('tanggal_akhir')) {
        $query->whereDate(
            'tanggal_pinjam',
            '<=',
            $request->tanggal_akhir
        );
    }

    // Filter status
    if ($request->filled('status')) {
        $query->where(
            'status',
            $request->status
        );
    }

    // Filter kategori alat
    if ($request->filled('kategori_id')) {
        $query->whereHas('detailPinjam.alat', function ($q) use ($request) {
            $q->where('kategori_id', $request->kategori_id);
        });
    }

    // Data tabel
    $peminjaman = $query
        ->latest('created_at')
        ->paginate(10);

    // =========================
    // STATISTIK
    // =========================

    $totalPeminjaman = Peminjaman::count();

    $totalDipinjam = Peminjaman::where(
        'status',
        'dipinjam'
    )->count();

    $totalSelesai = Peminjaman::where(
        'status',
        'selesai'
    )->count();

    // Total denda dari pengembalian
    $totalDenda = Pengembalian::sum('denda');

    // User
    $totalUser = User::count();

    $totalPeminjam = User::where(
        'role',
        'peminjam'
    )->count();

    // Alat
    $totalAlat = Alat::count();

    $totalKategori = Kategori::count();

    // Data kategori untuk filter
    $kategoris = Kategori::orderBy(
        'nama_kategori'
    )->get();

    return view('admin.laporan.index', compact(
        'peminjaman',
        'kategoris',
        'totalPeminjaman',
        'totalDipinjam',
        'totalSelesai',
        'totalDenda',
        'totalUser',
        'totalPeminjam',
        'totalAlat',
        'totalKategori'
    ));
}


public function cetakLaporan(Request $request)
{
    $query = Peminjaman::with([
        'user',
        'detailPinjam.alat'
    ]);

    if ($request->filled('tanggal_mulai')) {
        $query->whereDate('created_at', '>=', $request->tanggal_mulai);
    }

    if ($request->filled('tanggal_akhir')) {
        $query->whereDate('created_at', '<=', $request->tanggal_akhir);
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('kategori_id')) {
        $query->whereHas('detailPinjam.alat', function ($q) use ($request) {
            $q->where('kategori_id', $request->kategori_id);
        });
    }

    $peminjaman = $query
        ->latest('created_at')
        ->get();

    $totalPeminjaman = $peminjaman->count();

    $totalDipinjam = $peminjaman
        ->where('status', 'dipinjam')
        ->count();

    $totalSelesai = $peminjaman
        ->where('status', 'selesai')
        ->count();

    $totalDenda = Pengembalian::sum('denda');

    $tanggalMulai = $request->tanggal_mulai;
    $tanggalAkhir = $request->tanggal_akhir;

    $pdf = Pdf::loadView(
        'admin.laporan.pdf',
        compact(
            'peminjaman',
            'totalPeminjaman',
            'totalDipinjam',
            'totalSelesai',
            'totalDenda',
            'tanggalMulai',
            'tanggalAkhir'
        )
    );

    $pdf->setPaper('a4', 'landscape');

    return $pdf->stream('laporan-peminjaman.pdf');
}

}