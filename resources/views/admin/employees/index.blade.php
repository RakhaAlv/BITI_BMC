<x-app-layout>
    <div x-data="{ addOpen: false }">
        <div class="mb-6 flex items-end justify-between">
            <x-page-head title="Karyawan" :subtitle="$jumlah.' dari '.$setting->kuota_karyawan.' akun terpakai'"/>
            <button @click="addOpen = true" class="mb-7 rounded bg-pink-500 px-4 py-2 text-sm font-semibold text-white" {{ $penuh ? 'disabled' : '' }}>+ Tambah karyawan</button>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm">
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b"><th class="p-2">Nama</th><th class="p-2">Jabatan</th><th class="p-2">Skor</th><th class="p-2 text-right">Riwayat</th></tr></thead>
                <tbody>
                    @foreach($rows as $r)
                        <tr class="border-b" x-data="{ detailOpen: false }">
                            <td class="p-2"><a class="text-pink-500" href="{{ route('admin.employees.show', $r['user']) }}">{{ $r['user']->name }}</a></td>
                            <td class="p-2">{{ $r['user']->jabatan }}</td>
                            <td class="p-2"><x-score-pill :score="$r['skor']['akhir']"/></td>
                            <td class="p-2 text-right"><button @click="detailOpen = true" class="rounded border border-pink-200 bg-pink-50 px-3 py-1.5 text-xs font-semibold text-pink-500 hover:bg-pink-100">Detail</button></td>
                            <td aria-hidden="true">
                                <div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/30 p-4">
                                    <div @click.outside="detailOpen = false" class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl">
                                        <div class="flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wider text-pink-500">Transparansi penilaian</p><h2 class="mt-1 text-xl font-semibold">{{ $r['user']->name }}</h2><p class="text-xs text-slate-400">Rincian aspek skor enam bulan terakhir.</p></div><button @click="detailOpen = false" class="text-slate-400">✕</button></div>
                                        <div class="mt-5 overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr><th class="p-2">Periode</th><th class="p-2">Kehadiran</th><th class="p-2">Target</th><th class="p-2">Peer 360°</th><th class="p-2">Skor akhir</th></tr></thead><tbody>@forelse($r['user']->monthlyScores as $skor)<tr class="border-t border-slate-100"><td class="p-2">{{ $skor->periode }}</td><td class="p-2">{{ number_format($skor->skor_kehadiran,1) }}</td><td class="p-2">{{ number_format($skor->skor_target,1) }}</td><td class="p-2">{{ $skor->skor_peer === null ? '—' : number_format($skor->skor_peer,1) }}</td><td class="p-2"><x-score-pill :score="$skor->skor_akhir"/></td></tr>@empty<tr><td class="p-3 text-slate-400" colspan="5">Belum ada riwayat skor.</td></tr>@endforelse</tbody></table></div>
                                        <div class="mt-5 flex justify-end"><button @click="detailOpen = false" class="rounded border border-slate-200 px-4 py-2 text-sm">Tutup</button></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div x-show="addOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/30 p-4"><div @click.outside="addOpen = false" class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl"><div class="flex items-center justify-between"><h2 class="font-semibold">Tambah karyawan</h2><button @click="addOpen = false" class="text-slate-400">✕</button></div><form method="POST" action="{{ route('admin.employees.store') }}" class="mt-5 grid gap-3">@csrf<input class="rounded border border-slate-200 p-2" name="name" placeholder="Nama lengkap" required><input class="rounded border border-slate-200 p-2" name="email" type="email" placeholder="Email" required><input class="rounded border border-slate-200 p-2" name="jabatan" placeholder="Jabatan" required><input class="rounded border border-slate-200 p-2" name="divisi" placeholder="Divisi"><input class="rounded border border-slate-200 p-2" name="password" type="password" placeholder="Password minimal 8 karakter" required><div class="mt-2 flex justify-end gap-2"><button type="button" @click="addOpen = false" class="rounded border border-slate-200 px-4 py-2 text-sm">Batal</button><button class="rounded bg-pink-500 px-4 py-2 text-sm font-semibold text-white">Simpan karyawan</button></div></form></div></div>
    </div>
</x-app-layout>
