<?php

namespace App\Support;

class SiteUrl
{
    public static function to(string $path = '/', array $query = []): string
    {
        $url = rtrim(config('app.url'), '/').'/'.ltrim($path, '/');

        return $query ? $url.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986) : $url;
    }
}
