<?php

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideEditorToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_tools_require_admin_authentication(): void
    {
        foreach (['render', 'clean-paste'] as $tool) {
            $this->post('/admin/guides/'.$tool, ['body' => 'Example'])->assertRedirect('/admin/login');
        }
    }

    public function test_preview_renders_unsaved_markdown_safely_without_persisting(): void
    {
        $this->actingAs(User::factory()->create());
        $response = $this->postJson('/admin/guides/render', [
            'body' => "## Test heading\n\n| Model | Status |\n| --- | --- |\n| Thor | Sample |\n\n![Screenshot](/images/screenshot.webp)\n\n<script>alert(1)</script>\n\n[Bad](javascript:alert(1))",
        ])->assertOk()->assertJsonPath('headings.0.text', 'Test heading');
        $this->assertStringContainsString('<table>', $response->json('html'));
        $this->assertStringContainsString('src="/images/screenshot.webp"', $response->json('html'));
        $this->assertStringNotContainsString('<script>', $response->json('html'));
        $this->assertStringNotContainsString('href="javascript:', $response->json('html'));
        $this->assertSame(0, Guide::count());
        $this->postJson('/admin/guides/render', ['body' => ''])->assertOk()->assertJsonPath('html', '');
    }

    public function test_cleanup_returns_reviewable_markdown_and_warnings_without_saving(): void
    {
        $this->actingAs(User::factory()->create());
        $this->postJson('/admin/guides/clean-paste', [
            'body' => "[image](https://www.google.com/s2/favicons?domain=github.com)\n\nGitHub+1\n\n[Photo](https://images.openai.com/static-rsc-4/example)",
        ])->assertOk()->assertJsonPath('body', '![Photo](https://images.openai.com/static-rsc-4/example)')
            ->assertJsonCount(1, 'warnings');
        $this->assertSame(0, Guide::count());
    }

    public function test_preview_and_saving_preserve_leading_markdown_code_indentation(): void
    {
        $this->actingAs(User::factory()->create());
        $body = "    echo 'hello';";
        $response = $this->postJson('/admin/guides/render', ['body' => $body])->assertOk();
        $this->assertStringContainsString('<pre><code>', $response->json('html'));
        $this->post('/admin/guides', [
            'title' => 'Code example', 'slug' => 'code-example', 'excerpt' => 'A code example.',
            'body' => $body, 'category' => 'Getting started', 'author' => 'Editors', 'status' => 'draft',
        ])->assertRedirect();
        $this->assertSame($body, Guide::firstOrFail()->body);
    }

    public function test_editor_tools_validate_payloads(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['render', 'clean-paste'] as $tool) {
            $this->postJson('/admin/guides/'.$tool, ['body' => ['invalid']])->assertUnprocessable()->assertJsonValidationErrors('body');
            $this->postJson('/admin/guides/'.$tool, ['body' => str_repeat('a', 200001)])->assertUnprocessable()->assertJsonValidationErrors('body');
        }
    }
}
