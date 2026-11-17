<?php

namespace App\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Node\StringContainerHelper;

class GuideMarkdown
{
    public function render(string $markdown): array
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 30,
            'heading_permalink' => [
                'insert' => 'none',
                'apply_id_to_heading' => true,
                'min_heading_level' => 2,
                'max_heading_level' => 3,
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new HeadingPermalinkExtension());
        $result = (new MarkdownConverter($environment))->convert($markdown);
        $headings = [];

        foreach ($result->getDocument()->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if ($node instanceof Heading && in_array($node->getLevel(), [2, 3], true)) {
                $headings[] = [
                    'id' => $node->data->get('attributes/id'),
                    'text' => StringContainerHelper::getChildText($node),
                    'level' => $node->getLevel(),
                ];
            }
        }

        return ['html' => (string) $result, 'headings' => $headings];
    }
}
