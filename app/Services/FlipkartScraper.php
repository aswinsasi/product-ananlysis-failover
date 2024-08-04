<?php

namespace App\Services;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

class FlipkartScraper
{
    public function fetchProductData($productName)
    {
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://www.flipkart.com/search?q=' . urlencode($productName));
        $content = $response->getContent();

        $crawler = new Crawler($content);
        
        $products = [];
        $crawler->filter('._1AtVbE')->each(function (Crawler $node) use (&$products) {
            try {
                if ($node->filter('._4rR01T')->count() > 0) {
                    $title = $node->filter('._4rR01T')->text();
                    $rating = $node->filter('._3LWZlK')->count() > 0 ? (float) filter_var($node->filter('._3LWZlK')->text(), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
                    $reviewCount = $node->filter('._2_R_DZ')->count() > 0 ? (int) filter_var($node->filter('._2_R_DZ span')->eq(1)->text(), FILTER_SANITIZE_NUMBER_INT) : null;
                    $price = $node->filter('._30jeq3')->count() > 0 ? (float) filter_var($node->filter('._30jeq3')->text(), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
                    $features = $node->filter('.fMghEO')->count() > 0 ? $node->filter('.fMghEO')->text() : null;
                    $url = 'https://www.flipkart.com' . $node->filter('a._1fQZEK')->attr('href'); // URL extraction

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
