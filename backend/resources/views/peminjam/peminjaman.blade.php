@extends('layouts.peminjam')

@section('title', 'Peminjaman Saya')
@section('header-title', 'Peminjaman Saya')

@section('content')

<div class="max-w-6xl mx-auto">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            Peminjaman Saya
        </h1>
        <p class="text-gray-500 mt-1">
            Daftar peminjaman yang sedang diajukan atau sedang dipinjam.
        </p>
    </div>

    @if(session('success'))
        <div class="mb-5 bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    @forelse($peminjamans as $peminjaman)

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-5">

            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="font-bold text-gray-800">
                        Peminjaman #{{ $peminjaman->id }}
                    </h2>

                    <p class="text-sm text-gray-500 mt-1">
                        Diajukan:
                        {{ $peminjaman->tgl_pinjam
                            ? \Carbon\Carbon::parse($peminjaman->tgl_pinjam)->format('d M Y H:i')
                            : '-' }}
                    </p>
                </div>

                @if($peminjaman->status === 'diajukan')
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-700">
                        Menunggu Persetujuan
                    </span>
                @elseif($peminjaman->status === 'dipinjam')
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-700">
                        Sedang Dipinjam
                    </span>
                @endif
            </div>

            <div class="border-t border-gray-100 pt-4">

                <h3 class="font-semibold text-gray-700 mb-3">
                    Alat yang Dipinjam
                </h3>

                <div class="space-y-3">
                    @foreach($peminjaman->detailPinjam as $detail)

                        <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3">

                            <div>
                                <p class="font-medium text-gray-800">
                                    {{ $detail->alat->nama_alat ?? 'Alat tidak ditemukan' }}
                                </p>
                            </div>

                            <span class="text-sm text-gray-600">
                                Jumlah: {{ $detail->jumlah }}
                            </span>

                        </div>

                    @endforeach
                </div>

            </div>

            <div class="mt-5 pt-4 border-t border-gray-100 text-sm text-gray-600">
                <span class="font-semibold">Rencana Pengembalian:</span>

                {{ $peminjaman->tgl_kembali_plan
                    ? \Carbon\Carbon::parse($peminjaman->tgl_kembali_plan)->format('d M Y')
                    : '-' }}
            </div>

        </div>

    @empty

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-10 text-center">
            <div class="text-5xl mb-4">📦</div>

            <h2 class="text-lg font-semibold text-gray-800">
                Belum Ada Peminjaman
            </h2>

            <p class="text-gray-500 mt-1 mb-5">
                Kamu belum memiliki peminjaman yang sedang diajukan atau berlangsung.
            </p>

            <a href="{{ route('peminjam.katalog') }}"
               class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-lg transition">
                Lihat Katalog
            </a>
        </div>

    @endforelse

</div>

@endsection