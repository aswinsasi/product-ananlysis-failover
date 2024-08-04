<?php

namespace App\Services;

use App\Services\AmazonScraper;
use App\Services\FlipkartScraper;
use App\Services\eBayScraper;
use App\Services\WalmartScraper;
use App\Models\Product;
use App\Models\Competitor;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ProductAnalysisService
{
    protected $amazonScraper;
    protected $flipkartScraper;
    protected $eBayScraper;
    protected $walmartScraper;

    public function __construct(AmazonScraper $amazonScraper, FlipkartScraper $flipkartScraper, eBayScraper $eBayScraper, WalmartScraper $walmartScraper)
    {
        $this->amazonScraper = $amazonScraper;
        $this->flipkartScraper = $flipkartScraper;
        $this->eBayScraper = $eBayScraper;
        $this->walmartScraper = $walmartScraper;
    }

    public function analyze($productName)
    {
        $amazonProducts = $this->amazonScraper->fetchProductData($productName); 
        $flipkartProducts = $this->flipkartScraper->fetchProductData($productName); 
        $eBayProducts = $this->eBayScraper->fetchProductData($productName);
        $walmartProducts = $this->walmartScraper->fetchProductData($productName);

        // Combine the results
        $allProducts = array_merge($amazonProducts, $flipkartProducts, $eBayProducts, $walmartProducts);

        // Assuming the first product is the main product to be analyzed
        $mainProductData = $allProducts[0];
        $product = new Product($mainProductData['title'], $mainProductData['rating'] ?? null, $mainProductData['reviewCount'] ?? null, $mainProductData['price'], $mainProductData['features'] ?? null);

        // The remaining products are competitors
        foreach (array_slice($allProducts, 1) as $competitorData) {
            if (!isset($competitorData['url'])) {
                continue;
            }

            $competitor = new Competitor(
                $competitorData['title'],
                $competitorData['url'],
                $competitorData['rating'] ?? null,
                $competitorData['reviewCount'] ?? null,
                $competitorData['price'],
                $competitorData['features'] ?? null
            );

            $product->addCompetitor($competitor);
        }

        $isBetter = $this->isBetterProduct($product);
        $valueAnalysis = $this->valueAnalysis($product);
        $priceComparison = $this->priceComparison($product, $allProducts);
        $otherWebsites = $this->getAvailabilityOnOtherWebsites($productName);

        return [
            'product' => $product,
            'is_better' => $isBetter,
            'value_analysis' => $valueAnalysis,
            'price_comparison' => $priceComparison,
            'other_websites' => $otherWebsites
        ];
    }

    private function isBetterProduct($product)
    {
        foreach ($product->competitors as $competitor) {
            if (($competitor->rating ?? 0) > ($product->rating ?? 0) && ($competitor->reviewCount ?? 0) > ($product->reviewCount ?? 0)) {
                return false;
            }
        }
        return true;
    }

    private function valueAnalysis($product)
    {
        $value = ($product->rating ?? 0) / ($product->price ?? 1);
        foreach ($product->competitors as $competitor) {
            $competitorValue = ($competitor->rating ?? 0) / ($competitor->price ?? 1);
            if ($competitorValue > $value) {
                return 'Competitors offer better value';
            }
        }
        return 'This product offers good value';
    }

    private function priceComparison($product, $allProducts)
    {
        $priceComparison = [];
        foreach ($allProducts as $productData) {
            $priceComparison[] = [
                'website' => $productData['url'] ?? null,
                'price' => $productData['price']
            ];
        }
        return $priceComparison;
    }

    private function getAvailabilityOnOtherWebsites($productName)
    {
        return [
            'ebay' => 'https://www.ebay.com/sch/i.html?_nkw=' . urlencode($productName),
            'walmart' => 'https://www.walmart.com/search/?query=' . urlencode($productName)
        ];
    }

    public function calculateSimilarity($productName, $productTitles)
    {
        $process = new Process(['python', base_path('scripts/similarity_calculator.py'), $productName, ...$productTitles]);
        $process->setWorkingDirectory(base_path('public'));

        try {
            $process->mustRun();
            $output = $process->getOutput();
            return json_decode($output, true);
        } catch (ProcessFailedException $exception) {
            throw new \RuntimeException($exception->getMessage());
        }
    }
}
