<div class="space-y-6">
    @php $hour = now()->hour; $greet = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam')); @endphp

    <x-ui.page-header
        eyebrow="Dasbor"
        title="{{ $greet }}, {{ Str::before($user->name, ' ') }}"
        :description="collect($user->roles())->map(fn ($r) => $r->label())->join(' · ').' — '.now()->translatedFormat('l, d F Y')" />

    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($stats as $stat)
            <x-ui.stat
                :label="$stat['label']"
                :value="$stat['value']"
                :tone="$stat['tone'] ?? 'default'"
                :href="$stat['href'] ?? null" />
        @endforeach
    </dl>

    @if (! empty($distribution))
        <x-ui.card title="Sebaran siklus per status"
            :subtitle="($user->isAdminDinas() ? 'Seluruh dinas' : 'Siklus yang Anda ikuti').' — '.array_sum(array_column($distribution, 'value')).' siklus'">
            <x-ui.bar-distribution :segments="$distribution" unit="siklus" />
        </x-ui.card>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card title="Langkah cepat" class="lg:col-span-2">
            <div class="flex flex-wrap gap-2.5">
                @if ($user->isSupervisor())
                    <x-ui.button as="a" href="{{ route('cycles.create') }}">Buat siklus</x-ui.button>
                    <x-ui.button as="a" href="{{ route('cycles.index') }}" variant="secondary">Siklus saya</x-ui.button>
                @elseif ($user->isGuru())
                    <x-ui.button as="a" href="{{ route('cycles.index') }}">Siklus supervisi saya</x-ui.button>
                @endif
                @can(\App\Support\Enums\Permission::ViewAggregateReport->value)
                    <x-ui.button as="a" href="{{ route('reports.aggregate') }}" variant="secondary">Pelaporan agregat</x-ui.button>
                @endcan
                @can(\App\Support\Enums\Permission::ManageUsers->value)
                    <x-ui.button as="a" href="{{ route('admin.users.index') }}" variant="secondary">Kelola pengguna</x-ui.button>
                @endcan
                @can(\App\Support\Enums\Permission::SubmitExpertReview->value)
                    <x-ui.button as="a" href="{{ route('evaluation.index') }}">Panel evaluasi</x-ui.button>
                @endcan
                <x-ui.button as="a" href="{{ route('profile.edit') }}" variant="ghost">Perbarui profil</x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card title="Butuh bantuan?">
            <p class="text-sm text-[var(--text-muted)]">Baca panduan penggunaan enam tahap siklus, atau laporkan kendala teknis ke tim pendukung.</p>
            <div class="mt-3 flex gap-2.5">
                <x-ui.button as="a" href="{{ route('help.index') }}" variant="secondary" size="sm">Panduan</x-ui.button>
                <x-ui.button as="a" href="{{ route('support.tickets') }}" variant="ghost" size="sm">Lapor kendala</x-ui.button>
            </div>
        </x-ui.card>
    </div>
</div>
