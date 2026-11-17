<?php

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_public_index_is_server_rendered_and_shows_only_published_guides(): void
    {
        $this->guide(['title' => 'Visible setup']);
        $this->guide(['title' => 'Secret draft', 'status' => 'draft']);
        $this->guide(['title' => 'Future guide', 'published_at' => now()->addDay()]);
        $this->guide(['title' => 'Missing publication date', 'published_at' => null]);

        $this->get('/guides')->assertOk()->assertSee('Visible setup')->assertDontSee('Secret draft')
            ->assertDontSee('Future guide')->assertDontSee('Missing publication date');
    }

    public function test_drafts_scheduled_and_missing_guides_return_404_even_for_admin(): void
    {
        $draft = $this->guide(['status' => 'draft']);
        $scheduled = $this->guide(['published_at' => now()->addDay()]);
        $undated = $this->guide(['published_at' => null]);
        foreach ([$draft->slug, $scheduled->slug, $undated->slug, 'does-not-exist'] as $slug) {
            $this->get('/guides/'.$slug)->assertNotFound();
        }
        $this->actingAs(User::factory()->create())->get('/guides/'.$draft->slug)->assertNotFound();
    }

    public function test_article_has_safe_markdown_unique_heading_anchors_and_its_own_metadata(): void
    {
        $guide = $this->guide([
            'title' => 'A guide </script><script>alert(1)</script>',
            'body' => "## Setup\n\nFirst steps.\n\n## Setup\n\n[Bad](javascript:alert(1))\n\n<script>alert(2)</script>\n\n<table><tr><td>raw</td></tr></table>\n\n```sh\necho hello\n```",
        ]);

        $response = $this->get('/guides/'.$guide->slug)->assertOk();
        $response->assertSee('<h2 id="content-setup">Setup</h2>', false)
            ->assertSee('<h2 id="content-setup-1">Setup</h2>', false)
            ->assertSee('href="#content-setup-1"', false)
            ->assertSee('<link rel="canonical" href="'.route('guides.show', $guide->slug).'">', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('echo hello')
            ->assertDontSee('<script>alert(', false)
            ->assertDontSee('href="javascript:', false)
            ->assertDontSee('<table>', false);
    }

    public function test_public_search_category_and_pagination_preserve_filters(): void
    {
        for ($i = 0; $i < 13; $i++) {
            $this->guide(['title' => 'Controller setup '.$i, 'category' => 'Gaming']);
        }
        $this->guide(['title' => 'Different topic', 'category' => 'Accessories']);
        $this->guide(['title' => 'Private controller', 'status' => 'draft', 'category' => 'Private topic']);

        $this->get('/guides?q=Controller&category=Gaming')->assertOk()
            ->assertSee('13 guides')->assertSee('page=2', false)->assertSee('category=Gaming', false)
            ->assertDontSee('Different topic')->assertDontSee('Private topic');
        $this->get('/guides?q=Controller&category=Gaming&page=2')->assertOk()->assertSee('Controller setup');
        $this->get('/guides?q=nothingmatches')->assertOk()->assertSee('No guides found.');
    }

    public function test_empty_public_index_is_useful(): void
    {
        $this->get('/guides')->assertOk()->assertSee('The next chapter is on its way.')->assertSee('Open shipping estimator');
    }

    public function test_sitemap_lists_only_public_guides(): void
    {
        $visible = $this->guide();
        $draft = $this->guide(['status' => 'draft']);
        $scheduled = $this->guide(['published_at' => now()->addHour()]);
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('guides.index'))->assertSee(route('guides.show', $visible->slug))
            ->assertDontSee($draft->slug)->assertDontSee($scheduled->slug)->assertSee('<lastmod>', false);
    }

    public function test_guests_cannot_access_authoring_or_preview(): void
    {
        $guide = $this->guide(['status' => 'draft']);
        foreach (['/admin/guides', '/admin/guides/create', '/admin/guides/'.$guide->id.'/edit', '/admin/guides/'.$guide->id.'/preview'] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
        $this->post('/admin/guides', $this->payload())->assertRedirect('/admin/login');
        $this->put('/admin/guides/'.$guide->id, $this->payload())->assertRedirect('/admin/login');
    }

    public function test_admin_can_create_preview_publish_edit_and_unpublish_a_guide(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/admin/guides', $this->payload(['slug' => '']))->assertRedirect();
        $guide = Guide::firstOrFail();
        $this->assertSame('first-setup', $guide->slug);
        $this->assertNull($guide->published_at);
        $this->get('/guides/'.$guide->slug)->assertNotFound();
        $this->get('/admin/guides/'.$guide->id.'/preview')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertSee('Preview of saved content')->assertSee('noindex, nofollow');
        $this->put('/admin/guides/'.$guide->id, $this->payload(['status' => 'published']))->assertRedirect();
        $guide->refresh();
        $this->assertNotNull($guide->published_at);
        $publishedAt = $guide->published_at->toAtomString();
        $this->get('/guides/'.$guide->slug)->assertOk()->assertSee('Before you begin');
        $this->travel(1)->days();
        $this->put('/admin/guides/'.$guide->id, $this->payload(['status' => 'published', 'title' => 'Updated setup']))->assertRedirect();
        $this->assertSame($publishedAt, $guide->fresh()->published_at->toAtomString());
        $this->put('/admin/guides/'.$guide->id, $this->payload(['status' => 'draft']))->assertRedirect();
        $this->get('/guides/'.$guide->slug)->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('/guides/'.$guide->slug);
    }

    public function test_admin_pages_render_and_edit_uses_numeric_binding(): void
    {
        $guide = $this->guide();
        $this->actingAs(User::factory()->create());
        $this->get('/admin/guides')->assertOk();
        $this->get('/admin/guides/create')->assertOk();
        $this->get('/admin/guides/'.$guide->id.'/edit')->assertOk();
        $this->get('/admin/guides/999999/edit')->assertNotFound();
    }

    public function test_duplicate_and_invalid_content_is_rejected(): void
    {
        $guide = $this->guide(['slug' => 'first-setup']);
        $this->actingAs(User::factory()->create());
        $this->post('/admin/guides', $this->payload())->assertSessionHasErrors('slug');
        $this->put('/admin/guides/'.$guide->id, $this->payload())->assertSessionDoesntHaveErrors()->assertRedirect();
        $this->post('/admin/guides', $this->payload(['slug' => 'different', 'status' => 'anything', 'body' => '', 'excerpt' => str_repeat('a', 501)]))
            ->assertSessionHasErrors(['status', 'body', 'excerpt']);
        $this->post('/admin/guides', $this->payload(['slug' => ['invalid'], 'title' => ['invalid']]))
            ->assertSessionHasErrors(['slug', 'title']);
    }

    public function test_commercial_placements_are_hidden_by_default_and_labelled_when_configured(): void
    {
        $guide = $this->guide();
        $this->get('/guides/'.$guide->slug)->assertDontSee('data-placement=', false)->assertDontSee('adsbygoogle', false);
        config(['guides.placements.article_top' => [
            'type' => 'sponsor', 'name' => 'Example partner', 'description' => '<script>bad()</script>',
            'url' => 'https://partner.example/test', 'cta' => 'Explore partner',
        ]]);
        $this->get('/guides/'.$guide->slug)->assertSee('Sponsored')->assertSee('Example partner')
            ->assertSee('rel="sponsored noopener noreferrer"', false)->assertDontSee('<script>bad()</script>', false);
        $this->actingAs(User::factory()->create())->get('/admin/guides/'.$guide->id.'/preview')->assertDontSee('Example partner');
        config(['guides.placements.article_top.url' => 'javascript:alert(1)']);
        $this->get('/guides/'.$guide->slug)->assertDontSee('Example partner');
    }

    private function guide(array $attributes = []): Guide
    {
        return Guide::create(array_merge($this->payload(), [
            'slug' => 'guide-'.fake()->unique()->numerify('########'),
            'status' => 'published', 'published_at' => now()->subDay(),
        ], $attributes));
    }

    private function payload(array $attributes = []): array
    {
        return array_merge([
            'title' => 'First setup', 'slug' => 'first-setup', 'excerpt' => 'Set up your new Thor.',
            'category' => 'Getting started', 'body' => "## Before you begin\n\nYour first steps.",
            'author' => 'Thor editors', 'status' => 'draft',
        ], $attributes);
    }
}
