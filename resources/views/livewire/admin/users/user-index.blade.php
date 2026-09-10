<div class="space-y-6">
    <x-ui.page-header eyebrow="Administrasi" title="Manajemen Pengguna" description="Buat akun, atur peran, penempatan sekolah, dan status aktif.">
        <x-slot:actions>
            <x-ui.button wire:click="create">Tambah pengguna</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @error('form') <x-ui.alert variant="danger">{{ $message }}</x-ui.alert> @enderror

    <x-ui.card flush>
        <div class="flex flex-col gap-3 border-b border-[var(--border)] p-4 sm:flex-row">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau email…"
                class="field-input sm:max-w-xs">
            <select wire:model.live="roleFilter" class="field-input">
                <option value="">Semua peran</option>
                @foreach ($roles as $r) <option value="{{ $r->value }}">{{ $r->label() }}</option> @endforeach
            </select>
            <select wire:model.live="statusFilter" class="field-input">
                <option value="">Semua status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[var(--border)] text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Peran</th>
                        <th class="px-4 py-3">Sekolah</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    @forelse ($users as $u)
                        <tr wire:key="user-{{ $u->id }}">
                            <td class="px-4 py-3">
                                <div class="font-medium text-ink-900 dark:text-ink-50">{{ $u->name }}</div>
                                <div class="text-xs text-[var(--text-muted)]">{{ $u->email }}</div>
                            </td>
                            <td class="px-4 py-3">{{ collect($u->roles())->map(fn ($r) => $r->label())->join(', ') ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $u->sekolah?->nama ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-ui.status-badge :status="$u->is_active ? 'done' : 'archived'" :label="$u->is_active ? 'Aktif' : 'Nonaktif'" />
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="edit('{{ $u->id }}')" class="text-brand-700 hover:text-brand-800">Ubah</button>
                                    <button wire:click="sendReset('{{ $u->id }}')" class="text-ink-500 hover:text-ink-700">Reset sandi</button>
                                    <button wire:click="toggleActive('{{ $u->id }}')" class="{{ $u->is_active ? 'text-status-overdue' : 'text-status-done' }}">
                                        {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-[var(--text-muted)]">Tidak ada pengguna.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-[var(--border)] p-4">{{ $users->links() }}</div>
    </x-ui.card>

    <x-app.slide-over wire-model="showForm" :title="$editingId ? 'Ubah pengguna' : 'Tambah pengguna'">
        <form wire:submit="save" class="space-y-4">
            <x-ui.input label="Nama lengkap" name="name" wire:model="name" required />
            <x-ui.input label="Email" name="email" type="email" wire:model="email" required />
            <x-ui.input label="NIP" name="nip" wire:model="nip" />
            <x-ui.input label="Jabatan" name="jabatan" wire:model="jabatan" />

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-ink-800 dark:text-ink-100">Peran</label>
                <select wire:model.live="role" name="role" class="field-input">
                    <option value="">Pilih peran…</option>
                    @foreach ($roles as $r) <option value="{{ $r->value }}">{{ $r->label() }}</option> @endforeach
                </select>
                @error('role') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
            </div>

            @if ($role === 'supervisor')
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-ink-800 dark:text-ink-100">Jenis supervisor</label>
                    <select wire:model="supervisorType" name="supervisorType" class="field-input">
                        <option value="">Pilih…</option>
                        @foreach ($supervisorTypes as $t) <option value="{{ $t->value }}">{{ $t->label() }}</option> @endforeach
                    </select>
                    @error('supervisorType') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
                </div>
            @endif

            @if (in_array($role, ['guru', 'supervisor'], true))
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-ink-800 dark:text-ink-100">Sekolah</label>
                    <select wire:model="sekolahId" name="sekolahId" class="field-input">
                        <option value="">Pilih sekolah…</option>
                        @foreach ($sekolahOptions as $s) <option value="{{ $s->id }}">{{ $s->nama }}</option> @endforeach
                    </select>
                    @error('sekolahId') <p class="text-xs text-status-overdue">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="flex gap-2 pt-2">
                <x-ui.button type="submit">Simpan</x-ui.button>
                <x-ui.button type="button" variant="ghost" wire:click="$set('showForm', false)">Batal</x-ui.button>
            </div>
        </form>
    </x-app.slide-over>
</div>
