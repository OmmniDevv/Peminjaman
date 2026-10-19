<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\DetailPinjam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamController extends Controller
{
    // Melihat daftar/katalog alat yang tersedia
    public function katalogAlat()
    {
        $alats = Alat::with('kategori')
            ->where('stok', '>', 0)
            ->get();

        return view('peminjam.katalog', compact('alats'));
    }

    public function ajukanPeminjaman(Request $request)
    {
        $request->validate([
            'tgl_kembali_plan' => 'required|date|after:today',
            'alat_id' => 'required|array|min:1',
            'alat_id.*' => 'exists:alat,id|distinct',
            'jumlah' => 'required|array',
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->alat_id as $alatId) {
                $jumlah = $request->jumlah[$alatId] ?? 1;
                $alat = Alat::lockForUpdate()->findOrFail($alatId);
                if ($alat->stok < $jumlah) {
                    DB::rollBack();
                    return back()->with('error', "Stok {$alat->nama_alat} tidak mencukupi (tersedia: {$alat->stok}).");
                }
            }

            // Buat header peminjaman
            $peminjaman = Peminjaman::create([
                'user_id' => auth()->id(),
                'tgl_pinjam' => now(),
                'tgl_kembali_plan' => $request->tgl_kembali_plan,
                'status' => 'diajukan',
            ]);

            // Masukkan daftar alat yang dipinjam ke detail_pinjam
            foreach ($request->alat_id as $alatId) {
                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id' => $alatId,
                    'jumlah' => $request->jumlah[$alatId] ?? 1,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('peminjam.riwayat')
                ->with('success', 'Pengajuan peminjaman berhasil dikirim.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Gagal mengajukan peminjaman: ' . $e->getMessage());
        }
    }

    public function peminjaman() { 
        $peminjamans = Peminjaman::with('detailPinjam.alat')
            ->where('user_id', auth()->id())
            ->whereIn('status', ['diajukan', 'dipinjam'])
            ->latest()
            ->get();
        return view('peminjam.peminjaman', compact('peminjamans'));
    }

    // Melihat daftar peminjaman yang sedang dipinjam dan perlu dikembalikan
    public function pengembalian()
    {
        $peminjamans = Peminjaman::with([
            'detailPinjam.alat',
            'pengembalian'
        ])
            ->where('user_id', auth()->id())
            ->whereIn('status', ['dipinjam', 'pengajuan_kembali'])
            ->latest()
            ->get();

        return view('peminjam.pengembalian', compact('peminjamans'));
    }

    public function ajukanPengembalian(Peminjaman $peminjaman)
    {
        if ($peminjaman->user_id !== auth()->id()) {
            return back()->with('error', 'Data peminjaman tidak ditemukan.');
        }

        if ($peminjaman->status !== 'dipinjam') {
            return back()->with('error', 'Hanya peminjaman berstatus dipinjam yang bisa diajukan pengembaliannya.');
        }

        $peminjaman->update(['status' => 'pengajuan_kembali']);

        return back()->with('success', 'Pengajuan pengembalian berhasil dikirim. Tunggu petugas memprosesnya.');
    }

    // Melihat riwayat peminjaman user yang sedang login
    public function riwayatPeminjaman()
    {
        $peminjamans = Peminjaman::with('detailPinjam.alat')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('peminjam.riwayat', compact('peminjamans'));
    }

    // Dashboard Peminjam
    public function dashboard()
    {
        $userId = auth()->id();

        $totalPeminjaman = Peminjaman::where('user_id', $userId)->count();

        $sedangDipinjam = Peminjaman::where('user_id', $userId)
            ->where('status', 'dipinjam')
            ->count();

        $menungguPersetujuan = Peminjaman::where('user_id', $userId)
            ->where('status', 'diajukan')
            ->count();

        $peminjamanTerbaru = Peminjaman::with('detailPinjam.alat')
            ->where('user_id', $userId)
            ->latest('created_at')
            ->take(5)
            ->get();

        return view('peminjam.dashboard', compact(
            'totalPeminjaman',
            'sedangDipinjam',
            'menungguPersetujuan',
            'peminjamanTerbaru'
        ));
    }
}