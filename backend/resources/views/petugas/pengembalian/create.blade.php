@section('title', 'Pengembalian Alat')

@section('header-title', 'Pengembalian Alat')

@section('content')

<div class="max-w-4xl mx-auto">

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">

        <h2 class="text-xl font-semibold text-gray-800 mb-6">
            Proses Pengembalian
        </h2>

        {{-- Informasi Peminjaman --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

            <div>
                <label class="block text-sm font-medium text-gray-600">
                    Peminjam
                </label>

                <div class="mt-1 p-3 bg-gray-50 rounded-lg">
                    {{ $peminjaman->user->name }}
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600">
                    Tanggal Pinjam
                </label>

                <div class="mt-1 p-3 bg-gray-50 rounded-lg">
                    {{ $peminjaman->tgl_pinjam->format('d-m-Y') }}
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600">
                    Batas Pengembalian
                </label>

                <div class="mt-1 p-3 bg-gray-50 rounded-lg">
                    {{ $peminjaman->tgl_kembali_plan->format('d-m-Y') }}
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600">
                    Status
                </label>

                <div class="mt-1 p-3 bg-gray-50 rounded-lg">
                    {{ ucfirst($peminjaman->status) }}
                </div>
            </div>

        </div>

        {{-- Daftar Alat --}}
        <div class="mb-6">

            <h3 class="font-semibold text-gray-800 mb-3">
                Alat yang Dipinjam
            </h3>

            <div class="overflow-x-auto border rounded-lg">

                <table class="w-full text-sm">

                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                No
                            </th>

                            <th class="px-4 py-3 text-left">
                                Nama Alat
                            </th>

                            <th class="px-4 py-3 text-left">
                                Jumlah
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($peminjaman->detailPinjam as $index => $detail)

                            <tr class="border-t">

                                <td class="px-4 py-3">
                                    {{ $index + 1 }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $detail->alat->nama_alat }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $detail->jumlah }}
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

        {{-- Form --}}
        <form
            action="{{ route('petugas.pengembalian.proses', $peminjaman->id) }}"
            method="POST"
        >

            @csrf

            <div class="mb-4">

                <label
                    for="tgl_kembali"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Tanggal Kembali
                </label>

                <input
                    type="date"
                    name="tgl_kembali"
                    id="tgl_kembali"
                    value="{{ old('tgl_kembali', date('Y-m-d')) }}"
                    class="w-full border-gray-300 rounded-lg"
                    required
                >

                @error('tgl_kembali')
                    <p class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </p>
                @enderror

            </div>

            <div class="mb-6">

                <label
                    for="kondisi_kembali"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Kondisi Saat Dikembalikan
                </label>

                <select
                    name="kondisi_kembali"
                    id="kondisi_kembali"
                    class="w-full border-gray-300 rounded-lg"
                    required
                >

                    <option value="">
                        -- Pilih Kondisi --
                    </option>

                    <option value="Baik"
                        {{ old('kondisi_kembali') == 'Baik' ? 'selected' : '' }}>
                        Baik
                    </option>

                    <option value="Rusak Ringan"
                        {{ old('kondisi_kembali') == 'Rusak Ringan' ? 'selected' : '' }}>
                        Rusak Ringan
                    </option>

                    <option value="Rusak Berat"
                        {{ old('kondisi_kembali') == 'Rusak Berat' ? 'selected' : '' }}>
                        Rusak Berat
                    </option>

                    <option value="Hilang"
                        {{ old('kondisi_kembali') == 'Hilang' ? 'selected' : '' }}>
                        Hilang
                    </option>

                </select>

                @error('kondisi_kembali')
                    <p class="text-red-500 text-sm mt-1">
                        {{ $message }}
                    </p>
                @enderror

            </div>

            <div class="flex justify-end gap-3">

                <a
                    href="{{ route('petugas.peminjaman.index') }}"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                    Proses Pengembalian
                </button>

            </div>

        </form>

    </div>

</div>

@endsection