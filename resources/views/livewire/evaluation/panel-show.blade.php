<div class="space-y-6">
    <x-ui.page-header :title="$panel->judul" :description="$panel->artefak_versi">
        <x-slot:actions>
            <x-ui.status-badge :status="match ($panel->status) { 'selesai' => 'done', 'berjalan' => 'progress', default => 'draft' }" :label="ucfirst($panel->status)" />
            <x-ui.button as="a" href="{{ route('evaluation.index') }}" variant="ghost" wire:navigate>Kembali</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @error('expertEmail') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    @if ($panel->deskripsi)
        <x-ui.card><p class="whitespace-pre-line text-sm text-[var(--text-muted)]">{{ $panel->deskripsi }}</p></x-ui.card>
    @endif

    <x-ui.card title="Panel ahli" :subtitle="$submittedCount.' dari '.$experts->count().' penilaian terkirim'">
        <ul class="divide-y divide-[var(--border)] text-sm">
            @forelse ($experts as $pe)
                <li class="flex items-center justify-between py-2">
                    <span>
                        {{ $pe->user?->name }}
                        <span class="text-xs text-[var(--text-muted)]">· {{ str($pe->rumpun)->headline() }}{{ $pe->afiliasi ? ' · '.$pe->afiliasi : '' }}</span>
                    </span>
                    <x-ui.status-badge :status="$pe->review && $pe->review->isSubmitted() ? 'done' : 'draft'"
                        :label="$pe->review && $pe->review->isSubmitted() ? 'Terkirim' : 'Menunggu'" />
                </li>
            @empty
                <li class="py-2 text-[var(--text-muted)]">Belum ada ahli yang ditugaskan.</li>
            @endforelse
        </ul>

        @if ($canManage && $panel->status !== 'selesai')
            <form wire:submit="assignExpert" class="mt-4 grid gap-3 border-t border-[var(--border)] pt-4 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <x-ui.input label="Email ahli" name="expertEmail" wire:model="expertEmail" hint="Akun ahli harus sudah dibuat oleh Admin Sistem." />
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium">Rumpun</label>
                    <select wire:model="expertRumpun" class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]">
                        <option value="manajemen_pendidikan">Manajemen Pendidikan</option>
                        <option value="sistem_informasi">Sistem Informasi</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <x-ui.button type="submit" variant="secondary">Tugaskan</x-ui.button>
                </div>
            </form>
        @endif
    </x-ui.card>

    @if ($canManage && $panel->status === 'berjalan')
        <x-ui.button wire:click="close" wire:confirm="Tutup panel dan hitung CVR/CVI, Aiken's V, dan SUS? Butuh minimal satu penilaian terkirim.">
            Tutup panel &amp; hitung
        </x-ui.button>
    @endif

    @if ($panel->status === 'selesai' && $panel->stats)
        @php $s = $panel->stats; @endphp
        <x-ui.card title="Hasil validasi" :subtitle="$s['n_ahli'].' ahli · nilai kritis CVR '.($s['nilai_kritis_cvr'] ?? '—')">
            <dl class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-md border border-[var(--border)] px-3 py-2">
                    <dt class="text-xs text-[var(--text-muted)]">CVI (rata-rata CVR)</dt>
                    <dd class="text-lg font-semibold">{{ $s['cvi'] ?? '—' }}</dd>
                </div>
                <div class="rounded-md border border-[var(--border)] px-3 py-2">
                    <dt class="text-xs text-[var(--text-muted)]">Aiken's V rata-rata</dt>
                    <dd class="text-lg font-semibold">{{ $s['aiken_v_rata'] ?? '—' }}</dd>
                </div>
                <div class="rounded-md border border-[var(--border)] px-3 py-2">
                    <dt class="text-xs text-[var(--text-muted)]">SUS rata-rata</dt>
                    <dd class="text-lg font-semibold">{{ $s['sus']['rata'] ?? '—' }}</dd>
                    <dd class="text-xs text-[var(--text-muted)]">{{ $s['sus']['interpretasi'] ?? '' }}</dd>
                </div>
            </dl>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-[var(--border)] text-left text-xs text-[var(--text-muted)]">
                        <tr><th class="py-2 pr-3">Aspek</th><th class="px-3 py-2">n esensial</th><th class="px-3 py-2">CVR</th><th class="px-3 py-2">Signifikan</th><th class="px-3 py-2">Aiken's V</th></tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @foreach ($aspects as $key => $label)
                            <tr>
                                <td class="max-w-md py-2 pr-3">{{ $label }}</td>
                                <td class="px-3 py-2">{{ $s['cvr'][$key]['n_esensial'] ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $s['cvr'][$key]['cvr'] ?? '—' }}</td>
                                <td class="px-3 py-2">{{ ($s['cvr'][$key]['signifikan'] ?? false) ? '✓' : '—' }}</td>
                                <td class="px-3 py-2">{{ $s['aiken'][$key]['v'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (! empty($s['per_rumpun']))
                <div class="mt-4 text-sm">
                    <p class="mb-1 font-medium">Per rumpun ahli</p>
                    <ul class="space-y-1 text-[var(--text-muted)]">
                        @foreach ($s['per_rumpun'] as $rumpun => $r)
                            <li>{{ str($rumpun)->headline() }} (n={{ $r['n'] }}): Aiken's V {{ $r['aiken_v_rata'] ?? '—' }} · SUS {{ $r['sus_rata'] ?? '—' }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-ui.card>
    @endif
</div>
