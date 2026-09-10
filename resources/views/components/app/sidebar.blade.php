@php
    use App\Support\Enums\Permission;
    $user = auth()->user();
@endphp

<div x-data="{ open: false }" @toggle-sidebar.window="open = !open">
    <div x-show="open" x-transition.opacity x-cloak class="fixed inset-0 z-40 bg-ink-900/50 lg:hidden" @click="open = false"></div>

    <aside :class="open ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 flex w-64 transform flex-col border-r border-[var(--border)] bg-[var(--surface)] transition-transform lg:translate-x-0">

        <div class="flex h-16 items-center gap-2.5 border-b border-[var(--border)] px-5">
            <span class="flex size-8 items-center justify-center rounded-lg bg-brand-900 text-white">
                <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                    <path d="M12 3 4 7v6c0 4.5 3.4 7.3 8 8 4.6-.7 8-3.5 8-8V7l-8-4Z" stroke-linejoin="round"/>
                    <path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <div class="leading-tight">
                <p class="text-sm font-bold tracking-tight text-ink-900 dark:text-ink-50">E-Supervisi</p>
                <p class="text-[10px] font-medium uppercase tracking-[0.08em] text-[var(--text-muted)]">Klinis Pendidikan</p>
            </div>
        </div>

        <nav class="flex flex-1 flex-col gap-0.5 overflow-y-auto p-3 text-sm">
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

            @if ($user && ($user->isGuru() || $user->isSupervisor() || $user->can(Permission::ManagePkbCatalog->value)) || $user?->isAdminDinas())
                <p class="mt-5 px-3 pb-1 text-[10px] font-bold uppercase tracking-[0.08em] text-[var(--text-muted)]">Pengembangan Profesional</p>
                @if ($user->isGuru() || $user->isSupervisor() || $user->can(Permission::ManagePkbCatalog->value))
                    <x-app.nav-link :href="route('pkb.catalog')" icon="stack">Katalog PKB</x-app.nav-link>
                    <x-app.nav-link :href="route('best-practices.index')" icon="stack">Praktik Baik</x-app.nav-link>
                @endif
                @can(Permission::ViewAccountabilityReport->value)
                    <x-app.nav-link :href="route('accountability.index')" icon="chart">Akuntabilitas 360°</x-app.nav-link>
                @endcan
                @canany([Permission::ManageCalibration->value, Permission::ParticipateCalibration->value])
                    <x-app.nav-link :href="route('calibration.index')" icon="shield">Kalibrasi Penilai</x-app.nav-link>
                @endcanany
            @endif

            @canany([Permission::ManageEvaluationPanel->value, Permission::SubmitExpertReview->value])
                <p class="mt-5 px-3 pb-1 text-[10px] font-bold uppercase tracking-[0.08em] text-[var(--text-muted)]">Evaluasi</p>
                <x-app.nav-link :href="route('evaluation.index')" icon="chart">Evaluasi Ahli</x-app.nav-link>
            @endcanany

            @canany([Permission::ManageUsers->value, Permission::ManageOrganization->value, Permission::ManageAssignments->value, Permission::ManagePolicySettings->value, Permission::ViewAuditLog->value])
                <p class="mt-5 px-3 pb-1 text-[10px] font-bold uppercase tracking-[0.08em] text-[var(--text-muted)]">Administrasi</p>
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

            <div class="mt-auto pt-4">
                <p class="px-3 pb-1 text-[10px] font-bold uppercase tracking-[0.08em] text-[var(--text-muted)]">Bantuan</p>
                <x-app.nav-link :href="route('help.index')" icon="help">Panduan</x-app.nav-link>
                <x-app.nav-link :href="route('support.tickets')" icon="ticket">Lapor Kendala</x-app.nav-link>
            </div>
        </nav>
    </aside>
</div>
