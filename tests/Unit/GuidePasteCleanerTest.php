<?php

namespace Tests\Unit;

use App\Services\GuidePasteCleaner;
use PHPUnit\Framework\TestCase;

class GuidePasteCleanerTest extends TestCase
{
    public function test_it_repairs_exported_formatting_without_removing_original_sources(): void
    {
        $input = <<<'MARKDOWN'
## **Before you begin**

A paragraph.&#x20;

[image](https://www.google.com/s2/favicons?domain=https://github.com\&sz=32)

GitHub+1



[Official release](https://github.com/example/releases/latest?utm_source=chatgpt.com)

[Thor photo](https://images.openai.com/static-rsc-4/example?purpose=inline)

SCREENSHOT NEEDED — Both displays

Screenshot: The setup screen.
MARKDOWN;
        $result = (new GuidePasteCleaner)->clean($input);
        $this->assertStringContainsString('## Before you begin', $result['body']);
        $this->assertStringNotContainsString('&#x20;', $result['body']);
        $this->assertStringNotContainsString('favicons', $result['body']);
        $this->assertStringNotContainsString('GitHub+1', $result['body']);
        $this->assertStringContainsString('[Official release](https://github.com/example/releases/latest?utm_source=chatgpt.com)', $result['body']);
        $this->assertStringContainsString('![Thor photo](https://images.openai.com/static-rsc-4/example?purpose=inline)', $result['body']);
        $this->assertStringContainsString('> **Screenshot needed:** Both displays', $result['body']);
        $this->assertStringContainsString('> **Screenshot needed:** The setup screen.', $result['body']);
        $this->assertCount(2, $result['warnings']);
        $this->assertSame($result['body'], (new GuidePasteCleaner)->clean($result['body'])['body']);
    }

    public function test_code_normal_links_and_existing_images_are_preserved(): void
    {
        $input = <<<'MARKDOWN'
GitHub

[Documentation](https://example.com/docs)

![Existing screenshot](/images/setup.webp)

Inline [photo](https://images.openai.com/static-rsc-4/example) remains a link.

Keep `&#x20;` as literal inline code.

```markdown
## **Do not change code**


[image](https://www.google.com/s2/favicons?domain=github.com)
GitHub+1
SCREENSHOT NEEDED — literal example
[Photo](https://images.openai.com/static-rsc-4/code)
&#x20;
```

~~~text
Screenshot: literal example
~~~

    SCREENSHOT NEEDED — indented code
MARKDOWN;
        $this->assertSame($input, (new GuidePasteCleaner)->clean($input)['body']);
    }

    public function test_removing_favicon_does_not_remove_following_article_text(): void
    {
        $input = "[image](https://www.google.com/s2/favicons?domain=github.com)\n\nBefore you begin\n\nImportant instructions.";
        $result = (new GuidePasteCleaner)->clean($input);
        $this->assertStringContainsString('Before you begin', $result['body']);
        $this->assertStringContainsString('Important instructions.', $result['body']);
    }
}
