@extends('guides.layout', [
    'title' => 'Setup, play and get more from your Thor',
    'description' => 'Explore AYN Thor setup guides, gaming walkthroughs and troubleshooting. From first boot to your next adventure.',
    'canonical' => route('guides.index'),
])

@section('content')
    <section class="mb-12 grid items-center gap-8 md:grid-cols-[1fr_240px]">
        <div>
            <p class="mb-4 font-mono text-xs uppercase tracking-widest text-[#a5b4fc]">The next step after delivery</p>
            <h1 class="max-w-2xl text-4xl font-semibold leading-tight tracking-tight text-white sm:text-5xl">Your Thor.<br>A little more possibility.</h1>
            <p class="mt-5 max-w-xl text-base leading-8 text-[#a8a8bc]">Setup guides, gaming walkthroughs and help when something gets in the way. Find your next step here.</p>
        </div>
        <img src="/images/ayn-thor-optimized.webp" alt="AYN Thor dual-screen handheld" width="640" height="640" class="hidden w-60 object-contain md:block">
    </section>
    <x-guide-placement placement="index_top" />
    <form method="get" action="{{ route('guides.index') }}" class="mb-8 flex flex-wrap items-end gap-3 rounded-xl border border-[#2a2a3a] bg-[#15151f] p-4" role="search" aria-label="Find a guide">
        <div class="min-w-0 flex-1 basis-56">
            <label for="q" class="mb-2 block text-xs text-[#a8a8bc]">Search guides</label>
            <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="What would you like to do?" class="guide-input w-full">
        </div>
        <div class="flex-1 basis-40">
            <label for="category" class="mb-2 block text-xs text-[#a8a8bc]">Topic</label>
            <select id="category" name="category" class="guide-input w-full">
                <option value="">All topics</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                @endforeach
            </select>
        </div>
        <button class="guide-button" type="submit">Find guides</button>
        @if (!empty($filters['q']) || !empty($filters['category']))
            <a href="{{ route('guides.index') }}" class="px-2 py-3 text-sm text-[#a8a8bc] hover:text-white">Clear filters</a>
        @endif
    </form>
    <div class="mb-5 flex items-center justify-between gap-4">
        <h2 class="text-lg font-semibold text-white">{{ (!empty($filters['q']) || !empty($filters['category'])) ? 'Search results' : 'Explore the guides' }}</h2>
        <span class="font-mono text-xs text-[#a8a8bc]">{{ $guides->total() }} {{ Str::plural('guide', $guides->total()) }}</span>
    </div>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($guides as $item)
            @include('guides.card', ['item' => $item])
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-[#343449] px-6 py-14 text-center">
                <h3 class="text-xl font-semibold text-white">{{ $guides->total() === 0 && empty($filters['q']) && empty($filters['category']) ? 'The next chapter is on its way.' : 'No guides found.' }}</h3>
                <p class="mx-auto mt-3 max-w-md text-sm leading-7 text-[#a8a8bc]">{{ empty($filters['q']) && empty($filters['category']) ? 'We’re preparing our first guides. In the meantime, check when your Thor is expected to ship.' : 'Try another search or clear the filters to explore all guides.' }}</p>
                <a href="{{ empty($filters['q']) && empty($filters['category']) ? '/' : route('guides.index') }}" class="mt-5 inline-block text-sm text-[#a5b4fc]">{{ empty($filters['q']) && empty($filters['category']) ? 'Open shipping estimator' : 'View all guides' }} <span aria-hidden="true">→</span></a>
            </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $guides->links('guides.pagination') }}</div>
@endsection
