<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Company;
use App\Models\Product;

// Products is a shared top-level menu item for every company
// (unlike the type-restricted Operations branch) — a sack of rice,
// a drug, a car part, and a solar kit are all just "products" with
// a free-text category, per the original schema design.
class ProductPageController extends Controller
{
    public function index(Company $company)
    {
        $products = Product::where('company_id', $company->id)->orderBy('name')->get();

        return view('company.products.index', [
            'company' => $company,
            'products' => $products,
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreProductRequest $request, Company $company)
    {
        $product = Product::create([
            ...$request->validated(),
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        return redirect()->route('company.products.index', ['company' => $company->slug])->with('status', "{$product->name} added.");
    }

    public function update(StoreProductRequest $request, Company $company, Product $product)
    {
        abort_unless($product->company_id === $company->id, 404);

        $product->update($request->validated());

        return redirect()->route('company.products.index', ['company' => $company->slug])->with('status', "{$product->name} updated.");
    }

    public function destroy(Company $company, Product $product)
    {
        abort_unless($product->company_id === $company->id, 404);

        $product->update(['is_active' => false]);

        return redirect()->route('company.products.index', ['company' => $company->slug])->with('status', "{$product->name} deactivated.");
    }

    public function activate(Company $company, Product $product)
    {
        abort_unless($product->company_id === $company->id, 404);

        $product->update(['is_active' => true]);

        return redirect()->route('company.products.index', ['company' => $company->slug])->with('status', "{$product->name} reactivated.");
    }
}
