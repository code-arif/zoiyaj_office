<?php
namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class PlanController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function index()
    {
        $plans = Plan::all();
        return view('backend.layouts.plans.index', compact('plans'));
    }

    // Update existing plan
    public function update(Request $request, $id)
    {

        $request->validate([
            'name'     => 'required|string|max:255',
            'price'    => 'required|numeric|min:0',
            'interval' => 'required|in:day,week,month,year',
        ]);

        try {
            $plan = Plan::findOrFail($id);

            $amountInCents = $request->price * 100;

            // Update Stripe Product
            if ($plan->stripe_product_id) {
                $product       = Product::retrieve($plan->stripe_product_id);
                $product->name = $request->name;
                $product->save();
            }

            // Create new Stripe Price
            $stripePrice = Price::create([
                'product'     => $plan->stripe_product_id,
                'unit_amount' => $amountInCents,
                'currency'    => 'usd',
                'recurring'   => ['interval' => $request->interval],
            ]);

            // Update DB
            $plan->update([
                'name'            => $request->name,
                'slug'            => Str::slug($request->name),
                'price'           => $request->price,
                'interval'        => $request->interval,
                'stripe_price_id' => $stripePrice->id,
            ]);

            if ($request->has('features')) {
                // Optional: delete old features first
                $plan->features()->delete();

                foreach ($request->features as $f) {
                    $plan->features()->create([
                        'feature'     => $f['text'],
                    ]);
                }
            }

            return redirect()->back()->with('success', "Plan '{$request->name}' updated successfully.");
        } catch (Exception $e) {
            Log::error('Plan update failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    // Create new plan
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'price'    => 'required|numeric|min:0',
            'interval' => 'required|in:day,week,month,year',
        ]);

        try {
            $amountInCents = $request->price * 100;

            // Create Stripe Product
            $product = Product::create([
                'name' => $request->name,
            ]);

            // Create Stripe Price
            $stripePrice = Price::create([
                'product'     => $product->id,
                'unit_amount' => $amountInCents,
                'currency'    => 'usd',
                'recurring'   => ['interval' => $request->interval],
            ]);

            // Save in DB
            $plan = Plan::create([
                'name'              => $request->name,
                'slug'              => Str::slug($request->name),
                'price'             => $request->price,
                'interval'          => $request->interval,
                'stripe_product_id' => $product->id,
                'stripe_price_id'   => $stripePrice->id,
            ]);

            if ($request->has('features')) {
                foreach ($request->features as $f) {
                    $plan->features()->create([
                        'feature'     => $f['text'],
                    ]);
                }
            }

            return redirect()->back()->with('success', "Plan '{$request->name}' created successfully.");
        } catch (Exception $e) {
            Log::error('Plan creation failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Plan creation failed: ' . $e->getMessage());
        }
    }
}
