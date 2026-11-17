<article class="group flex flex-col rounded-2xl border border-[#2a2a3a] bg-[#15151f] p-6 transition-colors hover:border-[#6265a0]">
    <p class="font-mono text-[11px] uppercase tracking-widest text-[#a5b4fc]">{{ $item->category }}</p>
    <h3 class="mt-4 text-xl font-semibold leading-7 tracking-tight text-white">
        <a href="{{ route('guides.show', $item->slug) }}" class="group-hover:text-[#c7d2fe]">{{ $item->title }}</a>
    </h3>
    <p class="mt-3 flex-1 text-sm leading-7 text-[#a8a8bc]">{{ $item->excerpt }}</p>
    <div class="mt-6 flex items-center justify-between border-t border-[#2a2a3a] pt-4 text-xs text-[#a8a8bc]">
        <span>{{ $item->reading_minutes }} min read</span>
        <a href="{{ route('guides.show', $item->slug) }}" class="text-[#a5b4fc]" aria-label="Read {{ $item->title }}">Read guide <span aria-hidden="true">→</span></a>
    </div>
</article>
