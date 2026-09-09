<div class="space-y-6">
    <x-ui.page-header
        title="Selamat datang, {{ Str::before($user->name, ' ') }}"
        :description="collect($user->roles())->map(fn ($r) => $r->label())->join(', ')" />

    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($stats as $stat)
            <x-ui.stat
                :label="$stat['label']"
                :value="$stat['value']"
                :tone="$stat['tone'] ?? 'default'"
                :href="$stat['href'] ?? null" />
        @endforeach
    </dl>

    @if ($user->isGuru() || $user->isSupervisor())
        <x-ui.alert variant="info" title="Modul siklus supervisi">
            Perencanaan, observasi, analisis, umpan balik, tindak lanjut, dan pelaporan
            hadir bertahap pada Fase 2–3. Fondasi identitas, peran, dan notifikasi sudah aktif.
        </x-ui.alert>
    @endif

    <x-ui.card title="Langkah cepat">
        <div class="flex flex-wrap gap-3">
            <x-ui.button as="a" href="{{ route('profile.edit') }}" variant="secondary">Perbarui profil</x-ui.button>
            <x-ui.button as="a" href="{{ route('help.index') }}" variant="secondary">Baca panduan</x-ui.button>
            @can(\App\Support\Enums\Permission::ManageUsers->value)
                <x-ui.button as="a" href="{{ route('admin.users.index') }}" variant="secondary">Kelola pengguna</x-ui.button>
            @endcan
        </div>
    </x-ui.card>
</div>
