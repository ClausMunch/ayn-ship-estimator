# SEO and AI search readiness

The public site makes its explanatory content and shipping summary available in the initial HTML response. The React estimator remains interactive. Content is the same for people and crawlers; there is no user-agent-specific rendering.

## Implemented

- Server-rendered estimator introduction, latest stored batch per model, original data source, import timestamp, methodology, limitations, questions and links to published guides.
- Server-rendered guide library, article content and about/methodology page.
- Shared escaped titles, descriptions, canonical URLs, social cards and safely encoded JSON-LD. Public URLs in metadata are based on `APP_URL` rather than the incoming host.
- WebSite and WebApplication schemas, Article and BreadcrumbList on published articles, CollectionPage/ItemList on the library, AboutPage for the methodology.
- Indexable guide pagination with a self-canonical URL for each page. Search and topic-filter results are `noindex, follow`, with normal crawlable article links. Out-of-range pages return 404.
- Sitemap includes home, guide library, about page and published articles. Draft and future-dated articles stay private.
- Laravel-served `/robots.txt` advertises the absolute sitemap URL and keeps admin, API and token-action routes out of crawling. The existing policy allowing public crawlers, including training crawlers, is unchanged. No special bot allowlist is necessary.
- Admin pages and previews are marked noindex. Previews also send an `X-Robots-Tag` response header.
- Lazy page imports separate the estimator from admin charts and editor code. Public guide/about pages do not load the React application.

## Deployment checks

1. Set `APP_URL` to the final HTTPS canonical origin, without a query string. Refresh configuration using the normal deployment process. Otherwise metadata and sitemap will point to the wrong domain.
2. Build with `bun run build`; no database migration is introduced by this SEO update.
3. Request `/robots.txt` from the real host. The tracked static `public/robots.txt` was removed so Laravel can insert the configured domain. Remove any stale deployed copy. If nginx has a static-only exact location for robots, forward it to Laravel, for example:

```nginx
location = /robots.txt {
    try_files $uri /index.php?$query_string;
}
```

4. Check `/sitemap.xml` on the real host, submit it to the applicable search webmaster tools, and inspect the rendered home page and a published article.
5. Verify that CDN/firewall rules do not challenge or block the intended search crawlers. Application robots rules alone cannot establish accessibility through external infrastructure.
6. Measure search impressions, clicks, AI referrals and crawler responses over time. Code-level readiness does not prove indexing, ranking or AI citations. Production accounts and firewall settings were not accessed during implementation.

## Publishing useful guides

Start with a concise answer to the reader’s question, followed by prerequisites and clear steps. State the device model, app/firmware versions, test date, limits and original sources for version-sensitive findings. An article’s modified date is not itself a test date. Use a real author name when appropriate; the default team byline is represented as an Organization in structured data.

Add links from related articles and keep established slugs stable. Do not publish invented tests, compatibility claims or performance figures. Original testing and clear provenance are more useful than repeating keywords. No articles or gameplay claims are added by this SEO update.

## Limits and references

This work improves accessibility and presentation to search systems; it does not guarantee search placement or citations. No `llms.txt` file, fabricated ratings or extra FAQ/HowTo rich-result promises are added.

Google says ordinary SEO practices apply to its AI search features and no special AI markup or AI text file is required: [AI features and your website](https://developers.google.com/search/docs/appearance/ai-features).

Server HTML reduces reliance on rendering JavaScript: [JavaScript SEO basics](https://developers.google.com/search/docs/crawling-indexing/javascript/javascript-seo-basics).

Pagination uses separate canonical URLs: [Pagination guidance](https://developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading).
