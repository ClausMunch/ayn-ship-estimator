@extends('guides.layout', [
    'title' => 'About our shipping data and guides',
    'pageTitle' => 'About & Methodology — AYN Thor Shipping & Guides',
    'description' => 'How our independent AYN Thor shipping estimator uses batch data, what its limitations are, and how we approach sources and setup guides.',
    'canonical' => \App\Support\SiteUrl::to('/about'),
    'structuredData' => [['@type' => 'AboutPage', 'url' => \App\Support\SiteUrl::to('/about'), 'name' => 'About our shipping data and guides', 'isPartOf' => ['@id' => \App\Support\SiteUrl::to().'#website']]],
])
@section('content')
    <header class="max-w-3xl"><p class="mb-4 font-mono text-xs uppercase tracking-widest text-[#a5b4fc]">About this project</p><h1 class="text-3xl font-semibold leading-tight text-white sm:text-5xl">Clear sources.<br>Honest limitations.</h1><p class="mt-6 text-lg leading-8 text-[#a8a8bc]">AYN Thor Shipping &amp; Guides is an independent community project for people waiting for, setting up or playing on an AYN Thor. It is not affiliated with AYN or Blizzard.</p></header>
    <article class="guide-prose mt-10 max-w-3xl">
        <h2>Where the shipping data comes from</h2>
        <p>The <a href="/">shipping estimator</a> uses reported batches from <a href="https://www.ayntec.com/pages/shipment-dashboard">AYN’s shipment dashboard</a>. The public summary shows the most recent batch we have stored for each model and the time of the last successful import. That timestamp describes our import, not a guarantee that AYN has updated its source.</p>
        <h2>What the estimator calculates</h2>
        <p>For each model, we group batches by date, keep the highest order-prefix endpoint for each date and sort the resulting progression by endpoint. The calculator interpolates between known endpoints or projects beyond them using that model’s overall shipping pace. It needs at least two usable points to project forward.</p>
        <p>Order numbers are shared across models. We do not know your position in AYN’s production queue. Batch gaps, changes in manufacturing and incomplete source data limit the accuracy of a projection.</p>
        <h2>Dispatch is different from delivery</h2>
        <p>A shipping estimate concerns dispatch from AYN, not the date a parcel reaches your home. Your individual order confirmation, shipping email and carrier tracking are the authoritative sources for your order.</p>
        <h2>How to evaluate a guide</h2>
        <p>Our <a href="{{ route('guides.index') }}">guide library</a> shows an author and update date for each article. A setup guide should distinguish the project maintainer’s documentation from our own observations and identify the device, app version and test date when reporting a tested result.</p>
        <p>Compatibility and performance can change with firmware and application updates. An article’s update date alone is not evidence that every step has been retested. Check the stated test conditions and linked original sources before following version-sensitive instructions.</p>
        <h2>Corrections and original sources</h2>
        <p>If our shipping summary differs from AYN’s current dashboard, use the original dashboard and your order emails. For a launcher or game setup, consult the original project’s current documentation and issue tracker linked by the guide.</p>
        <h2>Sponsors and editorial independence</h2>
        <p>Commercial placements, when enabled, are labelled Sponsored or Advertisement and kept separate from guide instructions. A sponsorship is not evidence of compatibility or performance. Product recommendations should explain the relevant test conditions and limitations.</p>
    </article>
@endsection
