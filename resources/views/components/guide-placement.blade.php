@props(['placement'])
@php
    $creative = config('guides.placements.'.$placement);
    $valid = is_array($creative)
        && in_array($creative['type'] ?? '', ['sponsor', 'advertisement'], true)
        && !empty($creative['name'])
        && filter_var($creative['url'] ?? '', FILTER_VALIDATE_URL)
        && in_array(strtolower(parse_url($creative['url'], PHP_URL_SCHEME) ?? ''), ['http', 'https'], true);
@endphp
@if ($valid)
    <aside aria-label="{{ $creative['type'] === 'sponsor' ? 'Sponsored placement' : 'Advertisement' }}" data-placement="{{ $placement }}" class="my-8 rounded-xl border border-[#393952] bg-[#171724] px-6 py-5">
        <p class="mb-2 font-mono text-[10px] uppercase tracking-widest text-[#a8a8bc]">{{ $creative['type'] === 'sponsor' ? 'Sponsored' : 'Advertisement' }}</p>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="font-semibold text-white">{{ $creative['name'] }}</p>
                @if (!empty($creative['description']))
                    <p class="mt-1 text-sm leading-7 text-[#a8a8bc]">{{ $creative['description'] }}</p>
                @endif
            </div>
            <a href="{{ $creative['url'] }}" rel="sponsored noopener noreferrer" class="text-sm text-[#a5b4fc]">{{ $creative['cta'] ?? 'Visit partner' }} <span aria-hidden="true">↗</span></a>
        </div>
    </aside>
@endif
