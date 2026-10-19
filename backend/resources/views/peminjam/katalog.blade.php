@extends('layouts.peminjam')

@section('title', 'Katalog Alat')
@section('header-title', 'Katalog Alat')

@section('content')

<div class="max-w-6xl mx-auto">

    {{-- Notifikasi --}}
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

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            Katalog Alat Tersedia
        </h1>

        <p class="text-gray-500 mt-1">
            Pilih alat yang ingin kamu pinjam.
        </p>
    </div>

    <form action="{{ route('peminjam.peminjaman.ajukan') }}" method="POST">
        @csrf

        {{-- Tanggal kembali --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-5">

            <label for="tgl_kembali_plan"
                   class="block text-sm font-semibold text-gray-700 mb-2">
                Rencana Tanggal Kembali
            </label>

            <input
                type="date"
                name="tgl_kembali_plan"
                id="tgl_kembali_plan"
                min="{{ now()->addDay()->format('Y-m-d') }}"
                class="w-full md:w-80 px-4 py-2.5 border border-gray-300 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
            >

        </div>

        {{-- Daftar alat --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">
                    Daftar Alat
                </h2>
            </div>

            <div class="overflow-x-auto">

                <table class="w-full text-sm">

                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-center py-3 px-4 text-gray-500 font-semibold">
                                Pilih
                            </th>

                            <th class="text-left py-3 px-4 text-gray-500 font-semibold">
                                Nama Alat
                            </th>

                            <th class="text-left py-3 px-4 text-gray-500 font-semibold">
                                Kategori
                            </th>

                            <th class="text-center py-3 px-4 text-gray-500 font-semibold">
                                Stok
                            </th>

                            <th class="text-center py-3 px-4 text-gray-500 font-semibold">
                                Jumlah
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($alats as $alat)

                            <tr class="border-t border-gray-100 hover:bg-gray-50">

                                {{-- Checkbox --}}
                                <td class="py-4 px-4 text-center">
                                    <input
                                        type="checkbox"
                                        name="alat_id[]"
                                        value="{{ $alat->id }}"
                                        class="w-4 h-4 text-blue-600 rounded border-gray-300
                                               focus:ring-blue-500"
                                    >
                                </td>

                                {{-- Nama --}}
                                <td class="py-4 px-4 font-medium text-gray-800">
                                    {{ $alat->nama_alat }}
                                </td>

                                {{-- Kategori --}}
                                <td class="py-4 px-4 text-gray-600">
                                    {{ $alat->kategori->nama_kategori ?? '-' }}
                                </td>

                                {{-- Stok --}}
                                <td class="py-4 px-4 text-center text-gray-600">
                                    {{ $alat->stok }}
                                </td>

                                {{-- Jumlah --}}
                                <td class="py-4 px-4">

                                    <input
                                        type="number"
                                        name="jumlah[{{ $alat->id }}]"
                                        value="1"
                                        min="1"
                                        max="{{ $alat->stok }}"
                                        class="w-24 mx-auto block px-3 py-2 border border-gray-300
                                               rounded-lg text-center
                                               focus:outline-none focus:ring-2
                                               focus:ring-blue-500"
                                    >

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="5"
                                    class="py-10 text-center text-gray-500">
                                    Tidak ada alat yang tersedia saat ini.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            {{-- Tombol --}}
            @if($alats->count())

                <div class="px-6 py-4 border-t border-gray-100 flex justify-end">

                    <button
                        type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white
                               font-semibold px-5 py-2.5 rounded-lg transition">
                        Ajukan Peminjaman
                    </button>

                </div>

            @endif

        </div>

    </form>

</div>

@endsection