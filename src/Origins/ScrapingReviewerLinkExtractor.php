<?php

declare(strict_types=1);

namespace PhpCfdi\SatCatalogosPopulate\Origins;

use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;

final class ScrapingReviewerLinkExtractor
{
    public function __construct(private readonly Crawler $crawler)
    {
    }

    public static function fromUrlResponse(UrlResponse $response): self
    {
        if (empty($response->body())) {
            throw new RuntimeException('Content is empty');
        }
        $crawler = new Crawler($response->body(), $response->url());
        return new self($crawler);
    }

    public function search(string $search): string
    {
        $link = $this->selectLink($search);
        $downloadUrl = $link->getUri();

        if (empty($downloadUrl)) {
            throw new RuntimeException('The link was found but it does not contains the url to download');
        }

        return $downloadUrl;
    }

    public function selectLink(string $search): Link
    {
        $elements = $this->crawler->filterXPath('//a')->reduce(
            fn (Crawler $linkElement): bool =>
                ('' !== $text = $linkElement->text('')) && fnmatch($search, $text, FNM_CASEFOLD)
        );

        if ($elements->count() > 0) {
            return $elements->first()->link();
        }

        throw new RuntimeException(sprintf('Link text "%s" was not found', $search));
    }
}
