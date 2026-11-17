<?php

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\ModelVariant;
use App\Models\ScrapeLog;
use App\Models\ShippingBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.url' => 'https://thor.example']);
    }

    public function test_estimator_exposes_real_content_and_batches_in_initial_html(): void
    {
        $variant = ModelVariant::create(['name' => 'Black Pro', 'slug' => 'black-pro', 'display_order' => 1, 'color_config' => []]);
        foreach ([['2026-11-10', 1200, 1300], ['2026-11-14', 1250, 1280]] as [$date, $start, $end]) {
            ShippingBatch::create(['model_variant_id' => $variant->id, 'ship_date' => $date, 'order_range_start' => $start, 'order_range_end' => $end, 'scraped_at' => now()]);
        }
        ScrapeLog::create(['status' => 'success', 'created_at' => now(), 'records_found' => 2, 'records_new' => 2]);
        $this->guide('Visible guide');
        $this->guide('Hidden guide', ['status' => 'draft']);

        $response = $this->get('/')->assertOk();
        $dom = $this->document($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame('When will your AYN Thor ship?', $xpath->evaluate('string(//h1)'));
        $this->assertStringContainsString('Black Pro', $xpath->evaluate('string(//table)'));
        $this->assertStringContainsString('14 Nov 2026', $xpath->evaluate('string(//table)'));
        $this->assertStringContainsString('1250–1280', $xpath->evaluate('string(//table)'));
        $this->assertStringNotContainsString('10 Nov 2026', $xpath->evaluate('string(//table)'));
        $this->assertGreaterThan(0, $xpath->query('//a[@href="http://localhost/guides"]')->length);
        $this->assertStringContainsString('Visible guide', $xpath->evaluate('string(//main)'));
        $this->assertStringNotContainsString('Hidden guide', $xpath->evaluate('string(//main)'));
        $this->assertStringContainsString('not delivery', $xpath->evaluate('string(//main)'));
        $this->assertStringContainsString('average shipping pace', $xpath->evaluate('string(//main)'));
        $graph = $this->schemas($dom);
        $this->assertContains('WebApplication', array_column($graph, '@type'));
        $this->assertContains('WebSite', array_column($graph, '@type'));
        $this->assertSame('https://thor.example/', $xpath->evaluate('string(//link[@rel="canonical"]/@href)'));
    }

    public function test_estimator_handles_no_shipping_history_honestly(): void
    {
        $this->get('/')->assertOk()->assertSee('No shipping data is available yet.')
            ->assertSee('No successful import recorded yet.');
    }

    public function test_home_inertia_responses_still_supply_calculator_props(): void
    {
        $version = app(\Inertia\Middleware::class)->version(\Illuminate\Http\Request::create('/'));
        $this->get('/', ['X-Inertia' => 'true', 'X-Inertia-Version' => $version])->assertOk()->assertJsonPath('component', 'Home')
            ->assertJsonStructure(['props' => ['variants', 'lastUpdated', 'csrfToken', 'signedTs']]);
    }

    public function test_pagination_has_self_canonical_and_filters_are_noindex(): void
    {
        for ($index = 1; $index <= 13; $index++) {
            $this->guide('Setup guide '.$index);
        }
        $html = $this->get('/guides?page=2&utm_source=anything')->assertOk()->getContent();
        $xpath = new \DOMXPath($this->document($html));
        $this->assertSame('https://thor.example/guides?page=2', $xpath->evaluate('string(//link[@rel="canonical"]/@href)'));
        $this->assertStringContainsString('Page 2', $xpath->evaluate('string(//title)'));
        $this->assertStringStartsWith('index, follow', $xpath->evaluate('string(//meta[@name="robots"]/@content)'));
        $this->assertStringNotContainsString('utm_source', $xpath->evaluate('string(//nav[@aria-label="Guide pages"])'));
        foreach (['/guides?q=Setup', '/guides?category=Gaming', '/guides?q=Setup&category=Gaming&page=2'] as $url) {
            $xpath = new \DOMXPath($this->document($this->get($url)->assertOk()->getContent()));
            $this->assertSame('noindex, follow', $xpath->evaluate('string(//meta[@name="robots"]/@content)'));
        }
        $this->get('/guides?page=99')->assertNotFound();
    }

    public function test_article_schema_is_safe_and_matches_breadcrumbs_and_canonical_host(): void
    {
        $guide = $this->guide('A title </script><script>bad()</script>');
        $response = $this->get('/guides/'.$guide->slug)->assertOk();
        $dom = $this->document($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame('https://thor.example/guides/'.$guide->slug, $xpath->evaluate('string(//link[@rel="canonical"]/@href)'));
        $this->assertSame(0, $xpath->query('//script[not(@type="application/ld+json")]')->length);
        $schemas = collect($this->schemas($dom))->keyBy('@type');
        $this->assertSame($guide->title, $schemas['Article']['headline']);
        $this->assertSame('Organization', $schemas['Article']['author']['@type']);
        $this->assertSame('https://thor.example/about', $schemas['Article']['publisher']['url']);
        $this->assertCount(3, $schemas['BreadcrumbList']['itemListElement']);
        $this->assertStringContainsString('Home', $xpath->evaluate('string(//nav[@aria-label="Breadcrumb"])'));
        $this->assertSame('https://thor.example/images/ayn-thor.png', $xpath->evaluate('string(//meta[@property="og:image"]/@content)'));
    }

    public function test_robots_and_sitemap_use_configured_domain_and_exclude_private_pages(): void
    {
        $visible = $this->guide('Visible');
        $draft = $this->guide('Secret', ['status' => 'draft']);
        $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap: https://thor.example/sitemap.xml')->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /api')->assertDontSee('GPTBot')->assertDontSee('Disallow: /guides');
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $sitemap = simplexml_load_string($xml);
        $this->assertNotFalse($sitemap);
        $urls = array_map(fn ($url) => (string) $url->loc, iterator_to_array($sitemap->url, false));
        $this->assertContains('https://thor.example/', $urls);
        $this->assertContains('https://thor.example/about', $urls);
        $this->assertContains('https://thor.example/guides/'.$visible->slug, $urls);
        $this->assertNotContains('https://thor.example/guides/'.$draft->slug, $urls);
    }

    public function test_about_page_is_readable_and_administration_is_noindex(): void
    {
        $this->get('/about')->assertOk()->assertSee('Where the shipping data comes from')->assertSee('not affiliated');
        $this->get('/admin/login')->assertOk()->assertSee('noindex, nofollow')->assertDontSee('WebApplication');
        $guide = $this->guide('Preview', ['status' => 'draft']);
        $this->actingAs(User::factory()->create())->get('/admin/guides/'.$guide->id.'/preview')
            ->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertDontSee('"@type":"Article"', false);
    }

    private function guide(string $title, array $attributes = []): Guide
    {
        return Guide::create(array_merge([
            'title' => $title, 'slug' => 'guide-'.fake()->unique()->numerify('########'),
            'excerpt' => 'Setup requirements and steps.', 'body' => "## Requirements\n\nA tested device.",
            'category' => 'Gaming', 'author' => 'AYN Thor Guides', 'status' => 'published', 'published_at' => now()->subDay(),
        ], $attributes));
    }

    private function document(string $html): \DOMDocument
    {
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }

    private function schemas(\DOMDocument $dom): array
    {
        $xpath = new \DOMXPath($dom);
        $scripts = $xpath->query('//script[@type="application/ld+json"]');
        $this->assertSame(1, $scripts->length);

        return json_decode($scripts->item(0)->textContent, true, flags: JSON_THROW_ON_ERROR)['@graph'];
    }
}
