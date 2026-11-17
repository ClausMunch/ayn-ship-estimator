@extends('guides.layout', [
    'title' => $guide->title,
    'description' => $guide->excerpt,
    'canonical' => \App\Support\SiteUrl::to('/guides/'.$guide->slug),
])

@section('content')
    @if ($preview)
        <aside class="mb-8 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-800 bg-amber-950/30 px-5 py-4 text-sm text-amber-200" aria-label="Editorial preview">
            <p>Preview of saved content · {{ ucfirst($guide->status) }} · Not indexed</p>
            <a href="{{ route('admin.guides.edit', $guide) }}" class="underline">Back to editor</a>
        </aside>
    @endif
    <nav aria-label="Breadcrumb" class="mb-8 text-sm text-[#a8a8bc]">
        <a href="/" class="hover:text-white">Home</a> <span aria-hidden="true" class="mx-2">/</span> <a href="{{ route('guides.index') }}" class="hover:text-white">Guides</a> <span aria-hidden="true" class="mx-2">/</span> <span>{{ $guide->title }}</span>
    </nav>
    <header class="max-w-3xl">
        <p class="mb-4 font-mono text-xs uppercase tracking-widest text-[#a5b4fc]">{{ $guide->category }}</p>
        <h1 class="text-3xl font-semibold leading-tight tracking-tight text-white sm:text-5xl">{{ $guide->title }}</h1>
        <p class="mt-6 text-lg leading-8 text-[#a8a8bc]">{{ $guide->excerpt }}</p>
        <div class="mt-6 flex flex-wrap gap-x-5 gap-y-2 text-xs leading-6 text-[#a8a8bc]">
            <span>By {{ $guide->author }}</span>
            <span>{{ $guide->reading_minutes }} min read</span>
            <span>Updated <time datetime="{{ $guide->updated_at->toAtomString() }}">{{ $guide->updated_at->format('j M Y') }}</time></span>
        </div>
    </header>
    @unless ($preview)
        <x-guide-placement placement="article_top" />
    @endunless
    <div class="mt-10 grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_250px] lg:gap-16">
        @if (count($content['headings']))
            <aside class="rounded-xl border border-[#2a2a3a] bg-[#15151f] p-5 lg:sticky lg:top-8 lg:col-start-2 lg:row-start-1">
                <nav aria-label="On this page">
                    <h2 class="mb-4 font-mono text-xs uppercase tracking-widest text-[#a8a8bc]">On this page</h2>
                    <ol class="space-y-3 text-sm leading-6">
                        @foreach ($content['headings'] as $heading)
                            <li @class(['pl-3' => $heading['level'] === 3])><a href="#{{ $heading['id'] }}" class="text-[#c1c1d2] hover:text-[#a5b4fc]">{{ $heading['text'] }}</a></li>
                        @endforeach
                    </ol>
                </nav>
            </aside>
        @endif
        <article class="guide-prose min-w-0 lg:col-start-1 lg:row-start-1" aria-label="{{ $guide->title }}">{!! $content['html'] !!}</article>
    </div>
    @unless ($preview)
        <x-guide-placement placement="article_bottom" />
    @endunless
    @if ($related->isNotEmpty())
        <section class="mt-16 border-t border-[#2a2a3a] pt-10" aria-labelledby="related-title">
            <h2 id="related-title" class="mb-6 text-2xl font-semibold text-white">Keep exploring</h2>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $item)
                    @include('guides.card', ['item' => $item])
                @endforeach
            </div>
        </section>
    @endif
@endsection
