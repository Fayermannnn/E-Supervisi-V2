@props(['reviewed' => false])

@if (! $reviewed)
    <div role="note" {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-md border border-status-progress/30 bg-status-progress/10 px-3.5 py-2.5 text-sm text-status-progress']) }}>
        <svg class="mt-0.5 size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div>
            <p class="font-semibold">DRAFT / SARAN AI — belum ditinjau</p>
            <p class="mt-0.5 opacity-90">{{ $slot->isEmpty() ? 'Keputusan akhir tetap pada supervisor. Tinjau & sunting sebelum digunakan.' : $slot }}</p>
        </div>
    </div>
@endif
