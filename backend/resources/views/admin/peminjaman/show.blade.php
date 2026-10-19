@extends('layouts.app')

@section('title', 'Detail Peminjaman - Panel Admin')
@section('header-title', 'Detail Peminjaman')

@section('content')

    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">

        <div>
            <h2 class="text-xl font-bold text-gray-800">
                Detail Peminjaman
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Informasi lengkap transaksi peminjaman
            </p>
        </div>

        <a
            href="{{ route('admin.peminjaman.index') }}"
            class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition"
        >
            ← Kembali
        </a>

    </div>


    <!-- Informasi Peminjaman -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 mb-6">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <!-- Peminjam -->
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">

                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    Peminjam
                </p>

                <p class="text-lg font-bold text-gray-800 mt-1">
                    {{ $peminjaman->user->name ?? 'User Dihapus' }}
                </p>

                @if($peminjaman->user)
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $peminjaman->user->email }}
                    </p>
                @endif

            </div>


            <!-- Status -->
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">

                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    Status
                </p>

                <div class="mt-2">

                    <span
                        class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full
                        @if($peminjaman->status === 'diajukan')
                            bg-yellow-100 text-yellow-800
                        @elseif($peminjaman->status === 'dipinjam')
                            bg-blue-100 text-blue-800
                        @elseif($peminjaman->status === 'dikembalikan')
                            bg-emerald-100 text-emerald-800
                        @elseif($peminjaman->status === 'telat')
                            bg-red-100 text-red-800
                        @else
                            bg-gray-100 text-gray-700
                        @endif
                        "
                    >
                        {{ ucfirst($peminjaman->status) }}
                    </span>

                </div>

            </div>


            <!-- Tanggal Pinjam -->
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">

                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    Tanggal Pinjam
                </p>

                <p class="text-base font-semibold text-gray-800 mt-1">
                    {{ $peminjaman->tgl_pinjam?->format('d F Y') ?? '-' }}
                </p>

            </div>


            <!-- Rencana Kembali -->
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">

                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    Rencana Kembali
                </p>

                <p class="text-base font-semibold text-gray-800 mt-1">
                    {{ $peminjaman->tgl_kembali_plan?->format('d F Y') ?? '-' }}
                </p>

            </div>

        </div>

    </div>


    <!-- Daftar Alat -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden mb-6">

        <div class="p-5 border-b border-gray-200 bg-gray-50">

            <h3 class="text-lg font-bold text-gray-800">
                Alat yang Dipinjam
            </h3>

            <p class="text-sm text-gray-500 mt-1">
                Daftar alat dan jumlah yang dipinjam.
            </p>

        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-left border-collapse">

                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">

                        <th class="py-3 px-4 border-b w-16 text-center">
                            No
                        </th>

                        <th class="py-3 px-4 border-b">
                            Nama Alat
                        </th>

                        <th class="py-3 px-4 border-b text-center">
                            Jumlah
                        </th>

                        <th class="py-3 px-4 border-b text-center">
                            Kondisi
                        </th>

                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">

                    @forelse($peminjaman->detailPinjam as $index => $detail)

                        <tr class="hover:bg-gray-50 transition">

                            <td class="py-3 px-4 border-b text-center">
                                {{ $index + 1 }}
                            </td>

                            <td class="py-3 px-4 border-b font-semibold text-gray-900">
                                {{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}
                            </td>

                            <td class="py-3 px-4 border-b text-center">
                                {{ $detail->jumlah }} pcs
                            </td>

                            <td class="py-3 px-4 border-b text-center">

                                @php
                                    $kondisi = strtolower(trim($detail->alat->status_kondisi ?? ''));
                                @endphp

                                <span
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full
                                    @if($kondisi === 'baik')
                                        bg-emerald-100 text-emerald-800
                                    @elseif($kondisi === 'rusak ringan')
                                        bg-amber-100 text-amber-800
                                    @elseif($kondisi === 'rusak berat')
                                        bg-red-100 text-red-800
                                    @else
                                        bg-gray-100 text-gray-700
                                    @endif
                                    "
                                >
                                    {{ $detail->alat->status_kondisi ?? 'Tidak diketahui' }}
                                </span>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-500">
                                Tidak ada alat dalam transaksi ini.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    <!-- Informasi Pengembalian -->
    @if($peminjaman->pengembalian)

        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">

            <h3 class="text-lg font-bold text-gray-800 mb-4">
                Informasi Pengembalian
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase">
                        Tanggal Kembali
                    </p>

                    <p class="font-semibold text-gray-800 mt-1">
                        {{ $peminjaman->pengembalian->tgl_kembali ?? '-' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase">
                        Petugas
                    </p>

                    <p class="font-semibold text-gray-800 mt-1">
                        {{ $peminjaman->pengembalian->petugas->name ?? '-' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase">
                        Denda
                    </p>

                    <p class="font-semibold text-gray-800 mt-1">
                        Rp {{ number_format($peminjaman->pengembalian->denda ?? 0, 0, ',', '.') }}
                    </p>
                </div>

            </div>

        </div>

    @endif

@endsection