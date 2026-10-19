<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Alat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class PetugasController extends Controller
{
    // Menampilkan daftar pengajuan peminjaman dari siswa/peminjam
public function indexPeminjaman(Request $request)
{
    $search = $request->input('search');

    $peminjamans = Peminjaman::with([
        'user',
        'detailPinjam.alat'
    ])
    ->when($search, function ($query, $search) {
        $query->whereHas('user', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    })
    ->latest()
    ->paginate(10)
    ->withQueryString();

    return view(
        'petugas.peminjaman.index',
        compact('peminjamans', 'search')
    );
}

    // Menyetujui Peminjaman
    public function setujuiPeminjaman($id)
    {
        DB::beginTransaction();

        try {
            $peminjaman = Peminjaman::with('detailPinjam')->lockForUpdate()->findOrFail($id);

            if ($peminjaman->status !== 'diajukan') {
                DB::rollBack();
                return back()->with('error', 'Peminjaman ini sudah diproses sebelumnya.');
            }

            foreach ($peminjaman->detailPinjam as $detail) {
                $alat = Alat::lockForUpdate()->findOrFail($detail->alat_id);
                if ($alat->stok < $detail->jumlah) {
                    DB::rollBack();
                    return back()->with('error', "Stok {$alat->nama_alat} tidak mencukupi.");
                }
            }

            $peminjaman->update([
                'status' => 'dipinjam'
            ]);

            foreach ($peminjaman->detailPinjam as $detail) {
                $alat = Alat::lockForUpdate()->findOrFail($detail->alat_id);
                $alat->stok -= $detail->jumlah;
                $alat->save();
            }

            DB::commit();

            return redirect()->back()->with(
                'success',
                'Peminjaman disetujui dan stok alat dikurangi.'
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with(
                'error',
                'Terjadi kesalahan: ' . $e->getMessage()
            );
        }
    }

    // Menolak Peminjaman (Menghapus pengajuan agar siswa bisa mengajukan ulang)
    public function tolakPeminjaman($id)
    {
        try {
            $peminjaman = Peminjaman::findOrFail($id);

            // Pastikan statusnya memang masih diajukan
            if ($peminjaman->status == 'diajukan') {
                $peminjaman->delete();

                return redirect()->back()->with(
                    'success',
                    'Pengajuan peminjaman berhasil ditolak.'
                );
            }

            return redirect()->back()->with(
                'error',
                'Status peminjaman sudah berubah.'
            );

        } catch (\Exception $e) {
            return redirect()->back()->with(
                'error',
                'Terjadi kesalahan: ' . $e->getMessage()
            );
        }
    }

    public function indexPengembalian(Request $request)
{
    $search = $request->input('search');

    $peminjamans = Peminjaman::with([
        'user',
        'detailPinjam.alat'
    ])
    ->whereIn('status', ['dipinjam', 'pengajuan_kembali'])
    ->when($search, function ($query, $search) {
        $query->whereHas('user', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    })
    ->orderByRaw("FIELD(status, 'pengajuan_kembali', 'dipinjam')")
    ->latest('tgl_pinjam')
    ->get();

    return view(
        'petugas.pengembalian.index',
        compact('peminjamans', 'search')
    );
}

    public function indexLaporan(Request $request)
{
    $tanggalMulai = $request->input('tanggal_mulai');
    $tanggalSelesai = $request->input('tanggal_selesai');

    $query = Pengembalian::with([
        'peminjaman.user',
        'peminjaman.detailPinjam.alat',
        'petugas'
    ]);

    // Filter dari tanggal
    if (!empty($tanggalMulai)) {
        $query->whereDate('tgl_kembali', '>=', $tanggalMulai);
    }

    // Filter sampai tanggal
    if (!empty($tanggalSelesai)) {
        $query->whereDate('tgl_kembali', '<=', $tanggalSelesai);
    }

    $pengembalians = $query
        ->orderBy('tgl_kembali', 'desc')
        ->get();

    return view('petugas.laporan.index', [
        'pengembalians' => $pengembalians,
        'tanggalMulai' => $tanggalMulai,
        'tanggalSelesai' => $tanggalSelesai,
    ]);
}

public function cetakLaporan(Request $request)
{
    $request->validate([
        'tanggal_mulai' => 'nullable|date',
        'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
    ]);

    $tanggalMulai = $request->input('tanggal_mulai');
    $tanggalSelesai = $request->input('tanggal_selesai');

    $query = Pengembalian::with([
        'peminjaman.user',
        'peminjaman.detailPinjam.alat',
        'petugas'
    ]);

    if ($tanggalMulai) {
        $query->whereDate('tgl_kembali', '>=', $tanggalMulai);
    }

    if ($tanggalSelesai) {
        $query->whereDate('tgl_kembali', '<=', $tanggalSelesai);
    }

    $pengembalians = $query
        ->orderBy('tgl_kembali', 'desc')
        ->get();

    $pdf = Pdf::loadView('petugas.laporan.pdf', [
        'pengembalians' => $pengembalians,
        'tanggalMulai' => $tanggalMulai,
        'tanggalSelesai' => $tanggalSelesai,
    ]);

    $pdf->setPaper('a4', 'landscape');

    return $pdf->stream('laporan-pengembalian-alat.pdf');
}

    public function prosesPengembalian(Request $request, $peminjamanId)
{
    $request->validate([
        'kondisi_kembali' => 'required|string',
        'denda' => 'nullable|integer|min:0',
        'denda_kerusakan' => 'nullable|integer|min:0',
    ]);

    DB::beginTransaction();

    try {

        $peminjaman = Peminjaman::with('detailPinjam')
            ->lockForUpdate()
            ->findOrFail($peminjamanId);

        if (! in_array($peminjaman->status, ['dipinjam', 'pengajuan_kembali', 'telat'])) {
            DB::rollBack();
            return back()->with('error', 'Peminjaman ini sudah dikembalikan sebelumnya.');
        }

        if (Pengembalian::where('peminjaman_id', $peminjaman->id)->exists()) {
            DB::rollBack();
            return back()->with('error', 'Pengembalian untuk peminjaman ini sudah dicatat.');
        }

        // Simpan data pengembalian
        Pengembalian::create([
            'peminjaman_id'   => $peminjaman->id,
            'tgl_kembali'     => now(),
            'kondisi_kembali' => $request->kondisi_kembali,
            'denda'           => $request->denda ?? 0,
            'denda_kerusakan' => $request->denda_kerusakan ?? 0,
            'petugas_id'      => auth()->id(),
        ]);

        // Update status menjadi dikembalikan
        $peminjaman->update([
            'status' => 'dikembalikan'
        ]);

        // Kembalikan stok alat
        foreach ($peminjaman->detailPinjam as $detail) {

            $alat = Alat::lockForUpdate()->findOrFail($detail->alat_id);

            $alat->stok += $detail->jumlah;

            $alat->save();
        }

        DB::commit();

        return redirect()->back()->with(
            'success',
            'Pengembalian berhasil dicatat dan stok alat dipulihkan.'
        );

    } catch (\Exception $e) {

        DB::rollBack();

        return redirect()->back()->with(
            'error',
            'Terjadi kesalahan: ' . $e->getMessage()
        );
    }
}
}