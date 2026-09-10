<div class="space-y-6">
    <x-ui.page-header eyebrow="Bantuan" title="Lapor Kendala" description="Sampaikan masalah teknis atau pertanyaan penggunaan." />

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Buat laporan baru">
            <form wire:submit="submit" class="space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium">Kategori</label>
                    <select wire:model="category" class="field-input">
                        <option value="umum">Umum</option>
                        <option value="akun">Akun & akses</option>
                        <option value="teknis">Teknis / error</option>
                        <option value="data">Data</option>
                    </select>
                </div>
                <x-ui.input label="Subjek" name="subject" wire:model="subject" required />
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium">Pesan</label>
                    <textarea wire:model="message" rows="5" class="field-input"></textarea>
                    @error('message') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
                </div>
                <x-ui.button type="submit">Kirim laporan</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card :title="$canManage ? 'Semua tiket' : 'Tiket saya'" flush>
            <div class="divide-y divide-[var(--border)]">
                @forelse ($tickets as $ticket)
                    <div class="px-5 py-4" wire:key="ticket-{{ $ticket->id }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-ink-900 dark:text-ink-50">{{ $ticket->subject }}</p>
                                <p class="mt-0.5 text-xs text-[var(--text-muted)]">
                                    {{ $canManage ? $ticket->user?->name.' · ' : '' }}{{ $ticket->created_at?->diffForHumans() }} · {{ Str::title($ticket->category) }}
                                </p>
                            </div>
                            <x-ui.status-badge :status="match($ticket->status) { 'resolved','closed' => 'done', 'in_progress' => 'progress', default => 'scheduled' }" :label="Str::title(str_replace('_',' ',$ticket->status))" />
                        </div>
                        <p class="mt-2 text-sm text-ink-600 dark:text-ink-300">{{ Str::limit($ticket->message, 160) }}</p>
                        @if ($canManage && ! in_array($ticket->status, ['resolved', 'closed'], true))
                            <button wire:click="resolve('{{ $ticket->id }}')" class="mt-2 text-xs font-medium text-status-done">Tandai selesai</button>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-[var(--text-muted)]">Belum ada tiket.</p>
                @endforelse
            </div>
            <div class="border-t border-[var(--border)] p-4">{{ $tickets->links() }}</div>
        </x-ui.card>
    </div>
</div>
