<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveGuideRequest;
use App\Models\Guide;
use App\Services\GuideMarkdown;
use App\Services\GuidePasteCleaner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class GuidesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Guides/Index', [
            'guides' => Guide::latest('updated_at')->paginate(20)->through(fn (Guide $guide) => $guide->only(['id', 'title', 'slug', 'category', 'status', 'published_at', 'updated_at'])),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Guides/Edit', ['guide' => null, 'csrfToken' => csrf_token()]);
    }

    public function store(SaveGuideRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['published_at'] = $data['status'] === 'published' ? now() : null;
        $guide = Guide::create($data);

        return redirect()->route('admin.guides.edit', $guide)->with('success', 'Guide created.');
    }

    public function edit(Guide $guide): Response
    {
        return Inertia::render('Admin/Guides/Edit', ['guide' => $guide, 'saved' => session()->has('success'), 'csrfToken' => csrf_token()]);
    }

    public function update(SaveGuideRequest $request, Guide $guide): RedirectResponse
    {
        $data = $request->validated();
        $data['published_at'] = $data['status'] === 'published' ? ($guide->published_at ?? now()) : $guide->published_at;
        $guide->update($data);

        return redirect()->route('admin.guides.edit', $guide)->with('success', 'Guide saved.');
    }

    public function renderContent(Request $request, GuideMarkdown $markdown): JsonResponse
    {
        $data = $request->validate(['body' => ['nullable', 'string', 'max:200000']]);

        return response()->json($markdown->render($data['body'] ?? ''))
            ->header('Cache-Control', 'private, no-store');
    }

    public function cleanPaste(Request $request, GuidePasteCleaner $cleaner): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:200000']]);

        return response()->json($cleaner->clean($data['body']))
            ->header('Cache-Control', 'private, no-store');
    }

    public function preview(Guide $guide, GuideMarkdown $markdown): HttpResponse
    {
        return response()->view('guides.show', [
            'guide' => $guide,
            'content' => $markdown->render($guide->body),
            'preview' => true,
            'related' => collect(),
        ])->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
