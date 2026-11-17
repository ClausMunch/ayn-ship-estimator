<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use App\Services\GuideMarkdown;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);
        $query = Guide::published();
        if ($search = trim($filters['q'] ?? '')) {
            $query->where(fn ($query) => $query->where('title', 'like', "%{$search}%")->orWhere('excerpt', 'like', "%{$search}%"));
        }
        if ($category = $filters['category'] ?? null) {
            $query->where('category', $category);
        }

        return view('guides.index', [
            'guides' => $query->orderByDesc('published_at')->orderByDesc('id')->paginate(12)->withQueryString(),
            'categories' => Guide::published()->select('category')->distinct()->orderBy('category')->pluck('category'),
            'filters' => $filters,
        ]);
    }

    public function show(string $slug, GuideMarkdown $markdown): View
    {
        $guide = Guide::published()->where('slug', $slug)->firstOrFail();

        return view('guides.show', [
            'guide' => $guide,
            'content' => $markdown->render($guide->body),
            'preview' => false,
            'related' => Guide::published()->whereKeyNot($guide->id)->where('category', $guide->category)->latest('published_at')->limit(3)->get(),
        ]);
    }
}
