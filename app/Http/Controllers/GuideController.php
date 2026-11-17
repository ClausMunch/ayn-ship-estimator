<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use App\Services\GuideMarkdown;
use App\Support\SiteUrl;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = Guide::published();
        $search = trim($filters['q'] ?? '');
        $category = trim($filters['category'] ?? '');
        if ($search !== '') {
            $query->where(fn ($query) => $query->where('title', 'like', "%{$search}%")->orWhere('excerpt', 'like', "%{$search}%"));
        }
        if ($category !== '') {
            $query->where('category', $category);
        }

        $filterQuery = array_filter([
            'q' => $search,
            'category' => $category,
        ], fn ($value) => $value !== '');
        $guides = $query->orderByDesc('published_at')->orderByDesc('id')->paginate(12)->appends($filterQuery);
        abort_if($guides->currentPage() > $guides->lastPage(), 404);
        $canonicalQuery = $filterQuery;
        if ($guides->currentPage() > 1) {
            $canonicalQuery['page'] = $guides->currentPage();
        }

        return view('guides.index', [
            'guides' => $guides,
            'indexCanonical' => SiteUrl::to('/guides', $canonicalQuery),
            'robots' => $filterQuery ? 'noindex, follow' : 'index, follow, max-image-preview:large',
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
