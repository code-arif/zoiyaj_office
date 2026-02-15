<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ServiceReview;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ShopSearchController extends Controller
{
    use ApiResponse;

    /**
     * Combined search across brands, products, and deals
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->input('query', '');
        $limit = $request->input('limit', 10);

        $results = [
            'brands' => $this->getBrands($query, $limit),
            'products' => $this->getProducts($query, $limit, false),
            'deals' => $this->getProducts($query, $limit, true),
        ];

        return $this->success($results, 'Search results fetched successfully');
    }

    /**
     * Search and filter brands
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchBrands(Request $request)
    {
        $query = $request->input('query', '');
        $perPage = $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');

        $brands = Brand::query()
            ->when($query, function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%");
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);

        $brands->getCollection()->transform(function ($brand) {
            return $this->formatBrand($brand);
        });

        return $this->success($brands, 'Brands fetched successfully');
    }

    /**
     * Search and filter products
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchProducts(Request $request)
    {
        $query = $request->input('query', '');
        $perPage = $request->input('per_page', 15);
        $categoryId = $request->input('category_id');
        $brandName = $request->input('brand_name');
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $availability = $request->input('availability');

        $products = Product::query()
            ->when($query, function ($q) use ($query) {
                $q->where(function ($subQuery) use ($query) {
                    $subQuery->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('product_name', 'LIKE', "%{$query}%")
                        ->orWhere('brand_name', 'LIKE', "%{$query}%")
                        ->orWhere('description', 'LIKE', "%{$query}%")
                        ->orWhere('sku', 'LIKE', "%{$query}%")
                        ->orWhere('upc', 'LIKE', "%{$query}%");
                });
            })
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->when($brandName, function ($q) use ($brandName) {
                $q->where('brand_name', 'LIKE', "%{$brandName}%");
            })
            ->when($minPrice, function ($q) use ($minPrice) {
                $q->where('price', '>=', $minPrice);
            })
            ->when($maxPrice, function ($q) use ($maxPrice) {
                $q->where('price', '<=', $maxPrice);
            })
            ->when($availability, function ($q) use ($availability) {
                $q->where('availability', $availability);
            })
            ->where('is_active', true)
            ->with('category')
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);

        $products->getCollection()->transform(function ($product) {
            return $this->formatProduct($product);
        });

        return $this->success($products, 'Products fetched successfully');
    }

    /**
     * Search and filter deals (products with discounts)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchDeals(Request $request)
    {
        $query = $request->input('query', '');
        $perPage = $request->input('per_page', 15);
        $categoryId = $request->input('category_id');
        $brandName = $request->input('brand_name');
        $minDiscount = $request->input('min_discount'); // Minimum discount percentage
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $products = Product::query()
            ->where(function ($q) {
                // Products with original_price greater than price (discounted)
                $q->whereNotNull('original_price')
                    ->whereNotNull('price')
                    ->whereColumn('original_price', '>', 'price');
            })
            ->when($query, function ($q) use ($query) {
                $q->where(function ($subQuery) use ($query) {
                    $subQuery->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('product_name', 'LIKE', "%{$query}%")
                        ->orWhere('brand_name', 'LIKE', "%{$query}%")
                        ->orWhere('description', 'LIKE', "%{$query}%");
                });
            })
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->when($brandName, function ($q) use ($brandName) {
                $q->where('brand_name', 'LIKE', "%{$brandName}%");
            })
            ->when($minDiscount, function ($q) use ($minDiscount) {
                // Filter by minimum discount percentage
                $q->whereRaw('((original_price - price) / original_price * 100) >= ?', [$minDiscount]);
            })
            ->where('is_active', true)
            ->with('category')
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);

        $products->getCollection()->transform(function ($product) {
            return $this->formatDeal($product);
        });

        return $this->success($products, 'Deals fetched successfully');
    }

    /**
     * Get categories for filtering
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategories()
    {
        $categories = Category::select('id', 'title', 'slug', 'image')
            ->where('status', true)
            ->orderBy('title', 'asc')
            ->get();

        return $this->success($categories, 'Categories fetched successfully');
    }

    /**
     * Get all available brand names for filtering
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBrandNames()
    {
        $brands = Brand::select('id', 'name', 'thumb')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'thumb' => $brand->thumb ? url($brand->thumb) : null,
                ];
            });

        return $this->success($brands, 'Brand names fetched successfully');
    }

    /**
     * Helper: Get brands for combined search
     */
    private function getBrands($query, $limit)
    {
        return Brand::query()
            ->when($query, function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(function ($brand) {
                return $this->formatBrand($brand);
            });
    }

    /**
     * Helper: Get products for combined search
     */
    private function getProducts($query, $limit, $dealsOnly = false)
    {
        return Product::query()
            ->when($dealsOnly, function ($q) {
                $q->whereNotNull('original_price')
                    ->whereNotNull('price')
                    ->whereColumn('original_price', '>', 'price');
            })
            ->when($query, function ($q) use ($query) {
                $q->where(function ($subQuery) use ($query) {
                    $subQuery->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('product_name', 'LIKE', "%{$query}%")
                        ->orWhere('brand_name', 'LIKE', "%{$query}%");
                });
            })
            ->where('is_active', true)
            ->with('category')
            ->limit($limit)
            ->get()
            ->map(function ($product) use ($dealsOnly) {
                return $dealsOnly ? $this->formatDeal($product) : $this->formatProduct($product);
            });
    }

    /**
     * Format brand response
     */
    private function formatBrand($brand)
    {
        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'thumb' => $brand->thumb ? url($brand->thumb) : null,
            'promo_code' => $brand->promo_code,
            'redirect_url' => $brand->redirect_url,
        ];
    }

    /**
     * Format product response
     */
    private function formatProduct($product)
    {
        $hasDiscount = $product->original_price && $product->price && $product->original_price > $product->price;
        $discountPercentage = $hasDiscount
            ? round((($product->original_price - $product->price) / $product->original_price) * 100)
            : 0;

        return [
            'id' => $product->id,
            'name' => $product->product_name ?? $product->name,
            'brand_name' => $product->brand_name,
            'sku' => $product->sku,
            'upc' => $product->upc,
            'price' => $product->price,
            'original_price' => $product->original_price,
            'currency' => $product->currency ?? 'USD',
            'has_discount' => $hasDiscount,
            'discount_percentage' => $discountPercentage,
            'availability' => $product->availability,
            'summary' => $product->summary,
            'description' => $product->description,
            'primary_image_url' => $product->primary_image_url ? url($product->primary_image_url) : ($product->image_url ? url($product->image_url) : null),
            'category' => $product->category ? [
                'id' => $product->category->id,
                'title' => $product->category->title,
                'slug' => $product->category->slug,
            ] : null,
        ];
    }

    /**
     * Format deal response (product with discount info highlighted)
     */
    private function formatDeal($product)
    {
        $discountAmount = $product->original_price - $product->price;
        $discountPercentage = round(($discountAmount / $product->original_price) * 100);

        return [
            'id' => $product->id,
            'name' => $product->product_name ?? $product->name,
            'brand_name' => $product->brand_name,
            'price' => $product->price,
            'original_price' => $product->original_price,
            'currency' => $product->currency ?? 'USD',
            'discount_amount' => round($discountAmount, 2),
            'discount_percentage' => $discountPercentage,
            'savings_text' => "Save {$discountPercentage}%",
            'availability' => $product->availability,
            'summary' => $product->summary,
            'primary_image_url' => $product->primary_image_url ? url($product->primary_image_url) : ($product->image_url ? url($product->image_url) : null),
            'category' => $product->category ? [
                'id' => $product->category->id,
                'title' => $product->category->title,
                'slug' => $product->category->slug,
            ] : null,
        ];
    }

    /**
     * Get reviews list with filtering and pagination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReviews(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $professionalId = $request->input('professional_id');
        $minRating = $request->input('min_rating');
        $maxRating = $request->input('max_rating');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $reviews = ServiceReview::query()
            ->when($professionalId, function ($q) use ($professionalId) {
                $q->where('professional_id', $professionalId);
            })
            ->when($minRating, function ($q) use ($minRating) {
                $q->where('rating', '>=', $minRating);
            })
            ->when($maxRating, function ($q) use ($maxRating) {
                $q->where('rating', '<=', $maxRating);
            })
            ->with(['client', 'professional', 'booking'])
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);

        $reviews->getCollection()->transform(function ($review) {
            return $this->formatReview($review);
        });

        return $this->success($reviews, 'Reviews fetched successfully');
    }

    /**
     * Get reviews for a specific professional
     *
     * @param Request $request
     * @param int $professionalId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfessionalReviews(Request $request, $professionalId)
    {
        $perPage = $request->input('per_page', 15);
        $minRating = $request->input('min_rating');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $reviews = ServiceReview::query()
            ->where('professional_id', $professionalId)
            ->when($minRating, function ($q) use ($minRating) {
                $q->where('rating', '>=', $minRating);
            })
            ->with(['client', 'booking'])
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);

        // Calculate statistics
        $stats = ServiceReview::where('professional_id', $professionalId)
            ->selectRaw('
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            ')
            ->first();

        $reviews->getCollection()->transform(function ($review) {
            return $this->formatReview($review);
        });

        return $this->success([
            // 'statistics' => [
            //     'total_reviews' => (int) $stats->total_reviews,
            //     'average_rating' => round($stats->average_rating, 1),
            //     'rating_breakdown' => [
            //         '5' => (int) $stats->five_star,
            //         '4' => (int) $stats->four_star,
            //         '3' => (int) $stats->three_star,
            //         '2' => (int) $stats->two_star,
            //         '1' => (int) $stats->one_star,
            //     ],
            // ],
            'reviews' => $reviews,
        ], 'Professional reviews fetched successfully');
    }

    /**
     * Format review response
     */
    private function formatReview($review)
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'created_at' => $review->created_at->format('Y-m-d H:i:s'),
            'created_at_human' => $review->created_at->diffForHumans(),
            'client' => $review->client ? [
                'id' => $review->client->id,
                'name' => $review->client->name,
                'avatar' => $review->client->avatar ? url($review->client->avatar) : null,
            ] : null,
            'professional' => $review->professional ? [
                'id' => $review->professional->id,
                'name' => $review->professional->name,
                'avatar' => $review->professional->avatar ? url($review->professional->avatar) : null,
            ] : null,
            'booking' => $review->booking ? [
                'id' => $review->booking->id,
            ] : null,
        ];
    }
}
