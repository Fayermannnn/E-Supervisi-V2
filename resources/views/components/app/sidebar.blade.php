@php
    use App\Support\Enums\Permission;
    $user = auth()->user();
@endphp

<div x-data="{ open: false }" @toggle-sidebar.window="open = !open">
    <div x-show="open" x-transition.opacity class="fixed inset-0 z-40 bg-ink-900/40 lg:hidden" @click="open = false"></div>

    <aside :class="open ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 w-64 transform border-r border-[var(--border)] bg-[var(--surface)] transition-transform lg:translate-x-0">
        <div class="flex h-16 items-center gap-2 border-b border-[var(--border)] px-5 text-brand-600">
            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path d="M12 3 4 7v6c0 4.5 3.4 7.3 8 8 4.6-.7 8-3.5 8-8V7l-8-4Z" stroke-linejoin="round"/>
                <path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="font-semibold tracking-tight">E-Supervisi</span>
        </div>

        <nav class="flex flex-col gap-1 p-3 text-sm">
            <x-app.nav-link :href="route('dashboard')" icon="home">Dasbor</x-app.nav-link>

            @if ($user?->isGuru() || $user?->isSupervisor() || $user?->isAdminDinas())
                <x-app.nav-link :href="route('cycles.index')" icon="clipboard">Siklus Supervisi</x-app.nav-link>
            @endif

            @if ($user?->isSupervisor() || $user?->can(Permission::ManageInstruments->value))
                <x-app.nav-link :href="route('instruments.index')" icon="stack">Bank Instrumen</x-app.nav-link>
            @endif

            @can(Permission::ViewAggregateReport->value)
                <x-app.nav-link :href="route('reports.aggregate')" icon="chart">Pelaporan Agregat</x-app.nav-link>
            @endcan

            @can(Permission::ManageAnnualProgram->value)
                <x-app.nav-link :href="route('programs.index')" icon="clipboard">Program Tahunan</x-app.nav-link>
            @endcan

            @if ($user?->isGuru() || $user?->isSupervisor() || $user?->can(Permission::ManagePkbCatalog->value))
                <x-app.nav-link :href="route('pkb.catalog')" icon="stack">Katalog PKB</x-app.nav-link>
                <x-app.nav-link :href="route('best-practices.index')" icon="stack">Praktik Baik</x-app.nav-link>
            @endif

            @can(Permission::ViewAccountabilityReport->value)
                <x-app.nav-link :href="route('accountability.index')" icon="chart">Akuntabilitas 360°</x-app.nav-link>
            @endcan

            @canany([Permission::ManageCalibration->value, Permission::ParticipateCalibration->value])
                <x-app.nav-link :href="route('calibration.index')" icon="shield">Kalibrasi Penilai</x-app.nav-link>
            @endcanany

            @canany([Permission::ManageUsers->value, Permission::ManageOrganization->value, Permission::ManageAssignments->value, Permission::ManagePolicySettings->value, Permission::ViewAuditLog->value])
                <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">Administrasi</p>

                @can(Permission::ManageUsers->value)
                    <x-app.nav-link :href="route('admin.users.index')" icon="users">Pengguna</x-app.nav-link>
                @endcan
                @can(Permission::ManageOrganization->value)
                    <x-app.nav-link :href="route('admin.organizations.index')" icon="building">Organisasi</x-app.nav-link>
                @endcan
                @can(Permission::ManageAssignments->value)
                    <x-app.nav-link :href="route('admin.assignments.index')" icon="link">Penugasan</x-app.nav-link>
                @endcan
                @can(Permission::ManagePolicySettings->value)
                    <x-app.nav-link :href="route('admin.policies.index')" icon="cog">Kebijakan</x-app.nav-link>
                @endcan
                @can(Permission::ViewAuditLog->value)
                    <x-app.nav-link :href="route('admin.audit.index')" icon="shield">Audit Log</x-app.nav-link>
                @endcan
            @endcanany

            <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">Lainnya</p>
            <x-app.nav-link :href="route('help.index')" icon="help">Bantuan</x-app.nav-link>
            <x-app.nav-link :href="route('support.tickets')" icon="ticket">Lapor Kendala</x-app.nav-link>
        </nav>
    </aside>
</div>
