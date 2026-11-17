# Guide system

## Set up

Run `php artisan migrate` and `bun run build` after checking out this change. The new migration only adds the `guides` table. It does not publish or seed articles. Existing deployment instructions still apply.

- Public library: `/guides`
- Editor and guide list: `/admin/guides` (existing admin login)
- Individual public guide: `/guides/{slug}`

## Write and publish

1. Open **Guides** in the admin navigation and choose **New guide**.
2. Enter title, summary, topic, author and Markdown content. Leave the slug empty to generate it from the title on the first save.
3. Keep **Draft · private** selected and save. Use **Preview saved version** to review the actual article layout. Preview requires admin authentication and sends noindex directives. Save again before previewing further edits.
4. Set **Published · public** and save when ready. The guide appears in the library, related articles and sitemap immediately. The first publication date is retained on later edits.
5. Set the guide back to **Draft · private** and save to remove it from public pages and the sitemap. Its publication history is retained.

A slug is unique across drafts and published guides. Changing it changes the URL; old URLs are not automatically redirected. Keep published slugs stable.

The system stores English articles to match the existing site. Topic names are free text; use consistent spelling to group guides together. The public library supports topic filtering, title/summary search and pagination.

## Markdown and images

The article title supplies the page H1. Use `## Section` and `### Subsection` in the body for the automatic contents navigation. Standard Markdown and GitHub-style tables are supported. Raw HTML is stripped and unsafe URL protocols are disabled; scripts and ad code do not belong in article content.

Use `![Descriptive alternative text](/images/your-image.webp)` for an image deployed in `public/images/`, or use an HTTPS image URL. There is no media uploader in this version. Add images to the repository or use your existing media hosting workflow.

Article content, navigation, canonical links, social metadata and Article JSON-LD are rendered by Laravel in the initial HTML response. Public guide pages do not require React or JavaScript to read. They share the site's existing palette and typography.

Before publishing setup instructions, record the actual device model, firmware/app versions, test date, original download sources and known limitations in the article. No gameplay content is seeded as tested.

## Future sponsors and advertisements

All new commercial placements are **off by default**. No empty ad boxes or provider scripts are added to the guide pages. The existing estimator's AdSense integration is unchanged.

The single integration point is `resources/views/components/guide-placement.blade.php`, with configuration in `config/guides.php`. Available placements:

| Key | Location |
| --- | --- |
| `index_top` | Library, between the introduction and search |
| `article_top` | Article, between the header and reading area |
| `article_bottom` | Article, after the reading area |

Replace a `null` placement with a creative:

```php
'article_bottom' => [
    'type' => 'sponsor', // or 'advertisement'
    'name' => 'Partner name',
    'description' => 'A short partner message.',
    'url' => 'https://partner.example/campaign',
    'cta' => 'Visit partner',
],
```

The component labels each creative as **Sponsored** or **Advertisement**, escapes its text and adds `rel="sponsored noopener noreferrer"` to its link. Invalid or non-HTTP(S) links suppress the creative. Editorial previews omit commercial placements.

For a future ad network, extend this component at the same locations with its rendering code, load any provider script once in the guide layout, and connect the chosen consent integration there. No network-specific advertising or consent provider has been added. No affiliate account, sponsor agreement, impression/click tracking or automatic revenue reporting is included.

If configuration is cached, refresh it using the normal deployment process after changing placements.

## Verification

`php artisan test` covers visibility rules, draft previews, create/update/publication, search/filtering/pagination, safe Markdown, unique heading anchors, article metadata, sitemap, and disabled/active sponsor placements. `bun run build` compiles the editor and styles.
