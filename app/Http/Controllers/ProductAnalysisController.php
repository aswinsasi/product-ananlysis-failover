<?php

// app/Http/Controllers/ProductAnalysisController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProductAnalysisService;

class ProductAnalysisController extends Controller
{
    protected $productAnalysisService;

    public function __construct(ProductAnalysisService $productAnalysisService)
    {
        $this->productAnalysisService = $productAnalysisService;
    }

    public function analyze(Request $request)
    { 
            // Force the request to accept JSON responses
            $request->headers->set('Accept', 'application/json');

            // Validate the input
            $validated = $request->validate([
                'product_name' => 'required|string',
            ]);
    
            // Get the product name from the request
            $productName = $request->input('product_name');
    
            // Perform the product analysis
            $analysis = $this->productAnalysisService->analyze($productName);
    
            // Return the analysis as a JSON response
            return response()->json($analysis);
    
    }
}
