<?php

namespace App\Services;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

class AmazonScraper
{
    public function fetchProductData($productName)
    {
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://www.amazon.in/s?k=' . urlencode($productName));
        $content = $response->getContent();
        
        // Debug: Print the response content
        file_put_contents('debug.html', $content);

        $crawler = new Crawler($content);
        
        $products = [];
        $crawler->filter('.s-result-item')->each(function (Crawler $node) use (&$products) {
            try {
                if ($node->filter('.a-size-medium')->count() > 0) {
                    $title = $node->filter('.a-size-medium')->text();
                    $rating = $node->filter('.a-icon-alt')->count() > 0 ? (float) filter_var($node->filter('.a-icon-alt')->text(), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
                    $reviewCount = $node->filter('.a-size-small .a-size-base')->count() > 0 ? (int) filter_var($node->filter('.a-size-small .a-size-base')->text(), FILTER_SANITIZE_NUMBER_INT) : null;
                    $price = $node->filter('.a-price-whole')->count() > 0 ? (float) filter_var($node->filter('.a-price-whole')->text(), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
                    $features = $node->filter('.a-row.a-size-base')->count() > 0 ? $node->filter('.a-row.a-size-base')->text() : null;
                    $url = 'https://www.amazon.in' . $node->filter('.a-link-normal')->attr('href'); // URL extraction

                    $products[] = compact('title', 'rating', 'reviewCount', 'price', 'features', 'url');
                }
            } catch (\InvalidArgumentException $e) {
                // Debug: Print the error message
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
