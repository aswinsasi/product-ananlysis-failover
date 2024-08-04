<?php

namespace App\Services;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

class WalmartScraper
{
    public function fetchProductData($productName)
    {
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://www.walmart.com/search/?query=' . urlencode($productName));
        $content = $response->getContent();

        $crawler = new Crawler($content);
        
        $products = [];
        $crawler->filter('.search-result-gridview-item')->each(function (Crawler $node) use (&$products) {
            try {
                if ($node->filter('.product-title-link')->count() > 0) {
                    $title = $node->filter('.product-title-link span')->text();
                    $rating = $node->filter('.stars-reviews-count-node')->count() > 0 ? (float) filter_var($node->filter('.stars-reviews-count-node')->text(), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
                    $reviewCount = $node->filter('.stars-reviews-count-node span')->count() > 0 ? (int) filter_var($node->filter('.stars-reviews-count-node span')->text(), FILTER_SANITIZE_NUMBER_INT) : null;
                    $price = $node->filter('.price-characteristic')->count() > 0 ? (float) filter_var($node->filter('.price-characteristic')->text(), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
                    $features = $node->filter('.search-result-product-title')->count() > 0 ? $node->filter('.search-result-product-title')->text() : null;
                    $url = 'https://www.walmart.com' . $node->filter('.product-title-link')->attr('href'); // URL extraction

                    $products[] = compact('title', 'rating', 'reviewCount', 'price', 'features', 'url');
                }
            } catch (\InvalidArgumentException $e) {
                file_put_contents('error.log', $e->getMessage() . PHP_EOL, FILE_APPEND);
            }
        });

        // Sort products by rating and review count
        usort($products, function($a, $b) {
            return ($b['rating'] ?? 0) - ($a['rating'] ?? 0) ?: ($b['reviewCount'] ?? 0) - ($a['reviewCount'] ?? 0);
        });

        return $products;
    }
}
