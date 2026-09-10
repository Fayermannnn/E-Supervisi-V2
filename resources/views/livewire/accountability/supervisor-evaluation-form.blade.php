<div class="mx-auto max-w-2xl space-y-6">
    <x-ui.page-header title="Penilaian Proses Supervisi (360°)"
        :description="'Siklus: '.$cycle->judul.' — Anda menilai proses supervisi yang Anda terima, bukan performa mengajar Anda.'" />

    <x-ui.alert variant="info">
        Jawaban Anda bersifat rahasia. Supervisor dan Admin Dinas hanya melihat rata-rata gabungan bila jumlah responden memenuhi ambang anonimitas — tidak pernah jawaban individual.
    </x-ui.alert>

    @error('jawaban') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    <x-ui.card>
        <form wire:submit="submit" class="space-y-5">
            @foreach ($dimensions as $key => $label)
                <div>
                    <p class="text-sm font-medium">{{ $label }}</p>
                    <div class="mt-2 flex gap-2">
                        @foreach ([1 => 'Kurang', 2 => 'Cukup', 3 => 'Baik', 4 => 'Sangat baik'] as $val => $vlabel)
                            <label class="flex flex-1 cursor-pointer items-center justify-center rounded-md border px-2 py-2 text-xs
                                {{ ($jawaban[$key] ?? 0) === $val ? 'border-brand-600 bg-brand-50 text-brand-700 dark:bg-brand-900/40' : 'border-[var(--border)]' }}">
                                <input type="radio" wire:model="jawaban.{{ $key }}" value="{{ $val }}" class="sr-only">
                                {{ $val }} · {{ $vlabel }}
                            </label>
                        @endforeach
                    </div>
                    @error("jawaban.{$key}") <p class="mt-1 text-xs text-status-overdue">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <div class="space-y-1.5">
                <label class="block text-sm font-medium">Komentar <span class="text-[var(--text-muted)]">(opsional)</span></label>
                <textarea wire:model="komentar" rows="3" class="block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm ring-1 ring-inset ring-[var(--border)]"></textarea>
            </div>

            <div class="flex items-center gap-3">
                <x-ui.button type="submit">{{ $submitted ? 'Perbarui penilaian' : 'Kirim penilaian' }}</x-ui.button>
                @if ($submitted)
                    <span class="text-xs text-status-done">✓ Sudah dikirim — dapat disunting hingga siklus dilaporkan.</span>
                @endif
            </div>
        </form>
    </x-ui.card>
</div>
