@extends('guides.layout', [
    'title' => 'AYN Thor Ship Date Estimator',
    'pageTitle' => 'AYN Thor Ship Date Estimator — Shipping Updates & Guides',
    'description' => 'Estimate when your AYN Thor order will ship. Check reported shipping batches by model, understand the estimates and explore setup guides.',
    'canonical' => \App\Support\SiteUrl::to(),
    'structuredData' => [[
        '@type' => 'WebApplication', '@id' => \App\Support\SiteUrl::to().'#estimator',
        'name' => 'AYN Thor Ship Date Estimator', 'url' => \App\Support\SiteUrl::to(),
        'description' => 'Estimate AYN Thor shipping dates from reported shipping batches and a four-digit order prefix.',
        'applicationCategory' => 'UtilityApplication', 'operatingSystem' => 'Any',
        'isAccessibleForFree' => true,
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
        'isPartOf' => ['@id' => \App\Support\SiteUrl::to().'#website'],
    ]],
])

@section('head')
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4645062662712175" crossorigin="anonymous"></script>
@endsection

@section('content')
    <header class="mb-10 max-w-3xl">
        <p class="mb-4 font-mono text-xs uppercase tracking-widest text-[#a5b4fc]">Independent shipping estimates</p>
        <h1 class="text-3xl font-semibold leading-tight tracking-tight text-white sm:text-5xl">When will your AYN Thor ship?</h1>
        <p class="mt-5 text-base leading-8 text-[#a8a8bc]">Choose your model and enter the first four digits of your order number to estimate its ship date using AYN’s reported shipping batches. This estimates dispatch, not delivery to your door.</p>
    </header>
    <div class="grid items-start gap-8 lg:grid-cols-[480px_minmax(0,1fr)] lg:gap-12">
        <section id="calculator" aria-label="Shipping date calculator">
            @inertia
            <noscript><p class="rounded-xl border border-[#2a2a3a] bg-[#15151f] p-6 text-sm leading-7 text-[#a8a8bc]">Enable JavaScript to calculate a personalised estimate. You can still read the reported shipping batches, our methodology and all guides below.</p></noscript>
        </section>
        <aside class="space-y-6">
            <section class="rounded-2xl border border-[#2a2a3a] bg-[#15151f] p-6">
                <h2 class="text-lg font-semibold text-white">Know what the date means</h2>
                <p class="mt-3 text-sm leading-7 text-[#a8a8bc]">Shipping estimates are based on reported batches for your specific model. They are not an order lookup or a promise from AYN. Production changes and batch gaps can change the result.</p>
                <dl class="mt-5 space-y-4 text-sm leading-7">
                    <div><dt class="font-semibold text-[#e0e0e8]">Source</dt><dd class="text-[#a8a8bc]"><a href="https://www.ayntec.com/pages/shipment-dashboard" class="text-[#a5b4fc] underline">AYN’s shipment dashboard</a></dd></div>
                    <div><dt class="font-semibold text-[#e0e0e8]">Last successful import</dt><dd class="text-[#a8a8bc]">@if ($lastUpdated)<time datetime="{{ $lastUpdated->toAtomString() }}">{{ $lastUpdated->utc()->format('j M Y, H:i') }} UTC</time>@else No successful import recorded yet.@endif</dd></div>
                </dl>
                <a href="#shipping-method" class="mt-5 inline-block text-sm text-[#a5b4fc]">How estimates work <span aria-hidden="true">↓</span></a>
            </section>
            <section class="rounded-2xl border border-[#2a2a3a] p-6">
                <h2 class="text-lg font-semibold text-white">Getting ready for your Thor?</h2>
                <p class="mt-3 text-sm leading-7 text-[#a8a8bc]">Find setup guides, gaming walkthroughs and troubleshooting in our guide library.</p>
                <a href="{{ route('guides.index') }}" class="mt-4 inline-block text-sm text-[#a5b4fc]">Explore AYN Thor guides <span aria-hidden="true">→</span></a>
                @if ($latestGuides->isNotEmpty())
                    <ul class="mt-5 space-y-3 border-t border-[#2a2a3a] pt-4 text-sm">
                        @foreach ($latestGuides as $item)<li><a href="{{ route('guides.show', $item->slug) }}" class="text-[#c1c1d2] hover:text-[#a5b4fc]">{{ $item->title }}</a></li>@endforeach
                    </ul>
                @endif
            </section>
        </aside>
    </div>
    <section class="mt-14" aria-labelledby="shipping-batches-title">
        <h2 id="shipping-batches-title" class="text-2xl font-semibold text-white">Latest reported AYN Thor shipping batches</h2>
        <p class="mt-3 max-w-3xl text-sm leading-7 text-[#a8a8bc]">The most recent batch stored for each model. Numbers below are four-digit order prefixes reported in batch data, not individual tracking numbers or counts of shipped devices.</p>
        <div class="mt-6 overflow-x-auto rounded-xl border border-[#2a2a3a]">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Latest reported batch by AYN Thor model, shipping date and order prefix range</caption>
                <thead class="bg-[#15151f] text-[#c1c1d2]"><tr><th scope="col" class="px-5 py-4">Model</th><th scope="col" class="px-5 py-4">Reported ship date</th><th scope="col" class="px-5 py-4">Order prefixes</th></tr></thead>
                <tbody>
                    @forelse ($variants as $variant)
                        @php $batch = $variant->shippingBatches->sortByDesc(fn ($batch) => $batch->ship_date->format('Y-m-d').'-'.str_pad($batch->order_range_end, 8, '0', STR_PAD_LEFT))->first(); @endphp
                        <tr class="border-t border-[#2a2a3a]">
                            <th scope="row" class="px-5 py-4 font-medium text-[#e0e0e8]">{{ $variant->name }}</th>
                            <td class="whitespace-nowrap px-5 py-4 text-[#a8a8bc]">@if ($batch)<time datetime="{{ $batch->ship_date->format('Y-m-d') }}">{{ $batch->ship_date->format('j M Y') }}</time>@else No batch recorded @endif</td>
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-[#a8a8bc]">@if ($batch){{ $batch->order_range_start ? $batch->order_range_start.'–'.$batch->order_range_end : 'Up to '.$batch->order_range_end }}@else — @endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-[#a8a8bc]">No shipping data is available yet. Check the <a href="https://www.ayntec.com/pages/shipment-dashboard" class="text-[#a5b4fc] underline">original shipment dashboard</a> for current reports.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section id="shipping-method" class="guide-prose mt-14 max-w-3xl" aria-labelledby="method-title">
        <h2 id="method-title">How are AYN Thor shipping dates estimated?</h2>
        <p>We collect model-specific batch dates and order-prefix ranges from AYN’s shipment dashboard. The calculator compares your four-digit order prefix with those data points.</p>
        <ul>
            <li><strong>Already shipped:</strong> the calculator places your prefix within reported shipping progress. Confirm your individual order using AYN’s email or order status.</li>
            <li><strong>Estimated:</strong> your prefix falls between two known batch endpoints. We interpolate between those dates.</li>
            <li><strong>Projected:</strong> your prefix is beyond the known data. We extend the model’s average shipping pace from its first to its latest data point. This is less certain and requires at least two usable points.</li>
        </ul>
        <p>Order numbers are shared across models. A prefix is not a queue position, and reported batches can be incomplete. A calculated date can change as new data arrives. <a href="{{ route('about') }}">Read our full methodology and limitations</a>.</p>
    </section>
    <section class="mt-12 max-w-3xl" aria-labelledby="shipping-questions">
        <h2 id="shipping-questions" class="mb-5 text-2xl font-semibold text-white">Shipping questions, answered</h2>
        @foreach ([
            ['Is this the official AYN order tracker?', 'No. This is an independent community tool based on AYN’s published batch data. It does not access your order or carrier account.'],
            ['Does the estimate include delivery time?', 'No. The estimate concerns dispatch from AYN. Customs, the carrier and your destination affect delivery after dispatch.'],
            ['Why did my estimated date change?', 'New reported batches can change the calculated shipping pace. A projection is an estimate based on available data, not a confirmed dispatch date.'],
            ['What if my model has no useful data?', 'The calculator may be unable to produce an estimate. Check the original AYN dashboard and your order emails; we do not invent a date when the data is insufficient.'],
        ] as [$question, $answer])
            <details class="border-b border-[#2a2a3a] py-4"><summary class="cursor-pointer text-sm font-semibold leading-7 text-[#e0e0e8]">{{ $question }}</summary><p class="mt-3 text-sm leading-7 text-[#a8a8bc]">{{ $answer }}</p></details>
        @endforeach
    </section>
@endsection
