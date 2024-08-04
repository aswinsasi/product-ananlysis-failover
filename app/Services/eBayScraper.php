<?php

namespace App\Services;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

class eBayScraper
{
    public function fetchProductData($productName)
    {
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://www.ebay.com/sch/i.html?_nkw=' . urlencode($productName));
        $content = $response->getContent();

        $crawler = new Crawler($content);
        
        $products = [];
        $crawler->filter('.s-item')->each(function (Crawler $node) use (&$products) {
            try {
                if ($node->filter('.s-item__title')->count() > 0) {
                    $title = $node->filter('.s-item__title')->text();
                    $rating = $node->filter('.b-starrating__star span')->count() > 0 ? (float) filter_var($node->filter('.b-starrating__star span')->text(), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
                    $reviewCount = $node->filter('.s-item__reviews-count span')->count() > 0 ? (int) filter_var($node->filter('.s-item__reviews-count span')->text(), FILTER_SANITIZE_NUMBER_INT) : null;
                    $price = $node->filter('.s-item__price')->count() > 0 ? (float) filter_var($node->filter('.s-item__price')->text(), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
                    $features = $node->filter('.s-item__subtitle')->count() > 0 ? $node->filter('.s-item__subtitle')->text() : null;
                    $url = $node->filter('.s-item__link')->attr('href'); // URL extraction

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
