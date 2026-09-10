<div class="space-y-6">
    <x-ui.page-header eyebrow="Akuntabilitas" title="Akuntabilitas Supervisor (360°)"
        :description="$isAdminDinas ? 'Rata-rata persepsi guru atas proses supervisi di dinas Anda.' : 'Rata-rata persepsi guru binaan atas proses supervisi Anda.'" />

    @php $fmt = fn ($v) => $v === null ? '—' : number_format((float) $v, 2); @endphp

    @if (! $data['cukup'])
        <x-ui.empty-state title="Data belum cukup"
            :description="'Diperlukan minimal '.$data['min'].' respons sebelum agregat ditampilkan (ambang anonimitas). Saat ini: '.$data['responden'].' respons.'" />
    @else
        <x-ui.card :title="'Rata-rata keseluruhan: '.$fmt($data['rata_keseluruhan']).' / 4'" :subtitle="$data['responden'].' respons'">
            <dl class="grid gap-3 sm:grid-cols-2">
                @foreach ($dimensions as $key => $label)
                    <div class="rounded-lg border border-[var(--border)] bg-[var(--surface-muted)]/50 px-3.5 py-2.5">
                        <dt class="text-xs text-[var(--text-muted)]">{{ $label }}</dt>
                        <dd class="text-lg font-semibold">{{ $fmt($data['dimensi'][$key] ?? null) }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-ui.card>
    @endif

    @if ($isAdminDinas && ! empty($data['per_supervisor']))
        <x-ui.card title="Per supervisor" flush>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-[var(--border)] text-left text-xs text-[var(--text-muted)]">
                        <tr>
                            <th class="px-5 py-2">Supervisor</th>
                            <th class="px-3 py-2">Respons</th>
                            <th class="px-3 py-2">Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @foreach ($data['per_supervisor'] as $row)
                            <tr>
                                <td class="px-5 py-2">{{ $row['supervisor'] }}</td>
                                <td class="px-3 py-2">{{ $row['responden'] }}</td>
                                <td class="px-3 py-2">{{ $row['cukup'] ? $fmt($row['rata_keseluruhan']) : 'data belum cukup' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif
</div>
