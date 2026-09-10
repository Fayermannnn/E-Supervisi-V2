<div class="space-y-6">
    @php use App\Support\Enums\CycleStatus; $a = $cycle->planningAgreement; @endphp

    <x-ui.page-header :title="$cycle->judul"
        :description="'Guru: '.$cycle->guru?->name.' · '.$cycle->tahun_ajaran.' ('.ucfirst($cycle->semester).')'">
        <x-slot:actions>
            <x-ui.status-badge :status="$cycle->status->tone()" :label="$cycle->status->label()" />
            @if ($isSupervisor && $cycle->isActive() && $cycle->status->value <= CycleStatus::FeedbackGiven->value)
                <x-ui.button variant="ghost" wire:click="$set('showCancel', true)">Batalkan</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card><x-ui.cycle-stepper :status="$cycle->status" /></x-ui.card>

    @error('consent') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    {{-- PERENCANAAN --}}
    <x-ui.card title="Perencanaan">
        @if ($a === null)
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm text-[var(--text-muted)]">Kesepakatan pra-observasi belum dibuat.</p>
                @if ($isSupervisor)
                    <x-ui.button as="a" href="{{ route('cycles.planning', $cycle) }}">Isi kesepakatan</x-ui.button>
                @endif
            </div>
        @else
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-[var(--text-muted)]">Fokus observasi</dt><dd>{{ $a->fokus_observasi }}</dd></div>
                <div><dt class="text-[var(--text-muted)]">Instrumen</dt><dd>{{ $a->instrumentVersion?->instrument?->nama }} v{{ $a->instrumentVersion?->version }}</dd></div>
                <div><dt class="text-[var(--text-muted)]">Jadwal</dt><dd>{{ $a->jadwal_mulai->translatedFormat('d M Y, H:i') }}</dd></div>
                <div><dt class="text-[var(--text-muted)]">Tipe</dt><dd>{{ $a->tipe_observasi->label() }}</dd></div>
                <div><dt class="text-[var(--text-muted)]">Kelas / Mapel</dt><dd>{{ $a->kelas ?? '—' }} / {{ $a->mata_pelajaran ?? '—' }}</dd></div>
            </dl>

            <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-[var(--border)] pt-4 text-sm">
                <span class="{{ $a->disepakati_supervisor_at ? 'text-status-done' : 'text-[var(--text-muted)]' }}">
                    {{ $a->disepakati_supervisor_at ? '✓' : '○' }} Supervisor
                </span>
                <span class="{{ $a->disepakati_guru_at ? 'text-status-done' : 'text-[var(--text-muted)]' }}">
                    {{ $a->disepakati_guru_at ? '✓' : '○' }} Guru
                </span>

                @if ($cycle->status === CycleStatus::Draft)
                    @if ($isSupervisor)
                        <x-ui.button as="a" href="{{ route('cycles.planning', $cycle) }}" variant="secondary">Sunting</x-ui.button>
                        @unless ($a->disepakati_supervisor_at)
                            <x-ui.button wire:click="consent">Setujui sebagai supervisor</x-ui.button>
                        @endunless
                    @endif
                    @if ($isGuru && ! $a->disepakati_guru_at)
                        <x-ui.button wire:click="consent">Setujui sebagai guru</x-ui.button>
                    @endif
                @endif
            </div>
        @endif
    </x-ui.card>

    {{-- REFLEKSI GURU --}}
    @if ($isGuru && $cycle->status->value <= CycleStatus::ObservationDone->value)
        <x-ui.card title="Refleksi pra-observasi" subtitle="Ditulis oleh guru sebelum observasi">
            <form wire:submit="submitReflection" class="space-y-3">
                <textarea wire:model="reflectionContent" rows="5" class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)] focus:ring-2 focus:ring-brand-600"
                    placeholder="Apa yang menjadi perhatian Anda pada pembelajaran ini? Apa yang ingin Anda tingkatkan?"></textarea>
                @error('reflectionContent') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
                <x-ui.button type="submit">Simpan refleksi</x-ui.button>
            </form>
        </x-ui.card>
    @elseif ($cycle->reflections->where('tahap', 'pra_observasi')->first())
        <x-ui.card title="Refleksi pra-observasi guru">
            <p class="whitespace-pre-line text-sm text-ink-700 dark:text-ink-200">{{ $cycle->reflections->where('tahap', 'pra_observasi')->first()?->konten }}</p>
        </x-ui.card>
    @endif

    {{-- OBSERVASI --}}
    <x-ui.card title="Observasi">
        @if ($cycle->status->value < CycleStatus::Scheduled->value)
            <p class="text-sm text-[var(--text-muted)]">Tersedia setelah jadwal disepakati.</p>
        @else
            <div class="flex items-center justify-between gap-4">
                <div class="text-sm">
                    @forelse ($cycle->observations as $o)
                        <div class="flex items-center gap-2">
                            <x-ui.status-badge :status="$o->status->value === 'final' ? 'done' : 'progress'" :label="$o->status->label()" />
                            <span class="text-[var(--text-muted)]">{{ $o->tipe->value }} · v{{ $o->version }}</span>
                        </div>
                    @empty
                        <span class="text-[var(--text-muted)]">Belum ada data observasi.</span>
                    @endforelse
                </div>
                @if ($isSupervisor && $cycle->status->value <= CycleStatus::ObservationDone->value)
                    <x-ui.button as="a" href="{{ route('cycles.observe', $cycle) }}">
                        {{ $cycle->status === CycleStatus::Scheduled ? 'Buka konsol observasi' : 'Lihat hasil' }}
                    </x-ui.button>
                @endif
            </div>
        @endif
    </x-ui.card>

    {{-- TAHAP PASCA-OBSERVASI --}}
    @if ($cycle->status->value >= CycleStatus::ObservationDone->value)
        <div class="grid gap-3 sm:grid-cols-2">
            <x-ui.stat label="Analisis" :value="$cycle->status->value > CycleStatus::AnalysisDone->value ? 'Selesai' : ($cycle->status === CycleStatus::ObservationDone ? 'Perlu dikerjakan' : 'Berlangsung')"
                :href="route('cycles.analysis', $cycle)" :tone="$cycle->status === CycleStatus::ObservationDone ? 'warning' : 'default'" />
            @if ($cycle->status->value >= CycleStatus::AnalysisDone->value)
                <x-ui.stat label="Umpan Balik" :value="$cycle->status->value > CycleStatus::FeedbackGiven->value ? 'Selesai' : ($cycle->status === CycleStatus::AnalysisDone ? 'Perlu dikerjakan' : 'Berlangsung')"
                    :href="route('cycles.feedback', $cycle)" :tone="$cycle->status === CycleStatus::AnalysisDone ? 'warning' : 'default'" />
            @endif
            @if ($cycle->status->value >= CycleStatus::FeedbackGiven->value)
                <x-ui.stat label="Tindak Lanjut (RTL)"
                    :value="$cycle->status === CycleStatus::FollowUpOverdue ? 'Terlambat' : ($cycle->status->value >= CycleStatus::Reported->value ? 'Selesai' : 'Berjalan')"
                    :href="route('cycles.follow-up', $cycle)" :tone="$cycle->status === CycleStatus::FollowUpOverdue ? 'danger' : 'default'" />
            @endif
            @if ($cycle->status->value >= CycleStatus::FollowUpActive->value)
                <x-ui.stat label="Laporan" :value="$cycle->status->value >= CycleStatus::Reported->value ? 'Tersedia' : 'Belum disusun'"
                    :href="route('cycles.report', $cycle)" :tone="$cycle->status->value >= CycleStatus::Reported->value ? 'success' : 'default'" />
            @endif
        </div>
    @endif

    <x-app.slide-over wire-model="showCancel" title="Batalkan siklus">
        <form wire:submit="cancel" class="space-y-4">
            <p class="text-sm text-[var(--text-muted)]">Pembatalan bersifat permanen dan tercatat di audit.</p>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium">Alasan pembatalan</label>
                <textarea wire:model="cancelReason" rows="4" class="block w-full rounded-md border-0 px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]"></textarea>
                @error('cancelReason') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
            </div>
            <x-ui.button type="submit" variant="danger">Batalkan siklus</x-ui.button>
        </form>
    </x-app.slide-over>
</div>
