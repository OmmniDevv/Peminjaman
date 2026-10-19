@extends('layouts.peminjam')

<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - Peminjam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

{{-- Navbar --}}
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="{{ route('peminjam.katalog') }}">
            Panel Peminjam
        </a>

        <div class="d-flex">
            <a href="{{ route('peminjam.katalog') }}"
               class="btn btn-outline-light btn-sm me-2">
                Katalog Alat
            </a>

            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-light btn-sm text-primary">
                    Logout
                </button>
            </form>
        </div>
    </div>
</nav>

<div class="container">

    {{-- Flash Message --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Riwayat Peminjaman</h3>
            <p class="text-muted mb-0">
                Daftar riwayat peminjaman alat kamu.
            </p>
        </div>
    </div>

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">

                    <thead class="table-primary">
                        <tr>
                            <th width="50">No</th>
                            <th>Alat</th>
                            <th>Jumlah</th>
                            <th>Tanggal Pinjam</th>
                            <th>Rencana Kembali</th>
                            <th>Tanggal Kembali</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($peminjamans as $peminjaman)

                            @foreach($peminjaman->detailPinjam as $detail)

                                <tr>
                                    <td>
                                        {{ $loop->parent->iteration }}
                                    </td>

                                    <td>
                                        {{ $detail->alat->nama_alat ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $detail->jumlah }}
                                    </td>

                                    <td>
                                        {{ $peminjaman->tgl_pinjam
                                            ? \Carbon\Carbon::parse($peminjaman->tgl_pinjam)->format('d-m-Y')
                                            : '-' }}
                                    </td>

                                    <td>
                                        {{ $peminjaman->tgl_kembali_plan
                                            ? \Carbon\Carbon::parse($peminjaman->tgl_kembali_plan)->format('d-m-Y')
                                            : '-' }}
                                    </td>

                                    <td>
                                        {{ $peminjaman->tgl_kembali
                                            ? \Carbon\Carbon::parse($peminjaman->tgl_kembali)->format('d-m-Y')
                                            : '-' }}
                                    </td>

                                    <td>
                                        @if($peminjaman->status === 'menunggu')
                                            <span class="badge bg-warning text-dark">
                                                Menunggu
                                            </span>

                                        @elseif($peminjaman->status === 'disetujui')
                                            <span class="badge bg-primary">
                                                Disetujui
                                            </span>

                                        @elseif($peminjaman->status === 'dipinjam')
                                            <span class="badge bg-info text-dark">
                                                Sedang Dipinjam
                                            </span>

                                        @elseif($peminjaman->status === 'dikembalikan')
                                            <span class="badge bg-success">
                                                Dikembalikan
                                            </span>

                                        @elseif($peminjaman->status === 'ditolak')
                                            <span class="badge bg-danger">
                                                Ditolak
                                            </span>

                                        @else
                                            <span class="badge bg-secondary">
                                                {{ ucfirst($peminjaman->status ?? 'Tidak Diketahui') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>

                            @endforeach

                        @empty

                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Belum ada riwayat peminjaman.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>
            </div>

        </div>

    </div>

</div>
```

</body>
</html>
