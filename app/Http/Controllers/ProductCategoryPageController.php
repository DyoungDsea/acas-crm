<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductCategoryRequest;
use App\Models\Company;
use App\Models\ProductCategory;

class ProductCategoryPageController extends Controller
{
    public function index(Company $company)
    {
        $categories = ProductCategory::where('company_id', $company->id)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return view('company.product-categories.index', [
            'company' => $company,
            'categories' => $categories,
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreProductCategoryRequest $request, Company $company)
    {
        $category = ProductCategory::create([
            'company_id' => $company->id,
            'name' => $request->validated('name'),
        ]);

        return redirect()->route('company.product-categories.index', ['company' => $company->slug])
            ->with('status', "{$category->name} added.");
    }

    public function update(StoreProductCategoryRequest $request, Company $company, ProductCategory $product_category)
    {
        abort_unless($product_category->company_id === $company->id, 404);

        $product_category->update(['name' => $request->validated('name')]);

        return redirect()->route('company.product-categories.index', ['company' => $company->slug])
            ->with('status', "{$product_category->name} updated.");
    }

    public function destroy(Company $company, ProductCategory $product_category)
    {
        abort_unless($product_category->company_id === $company->id, 404);

        $name = $product_category->name;
        $affected = $product_category->products()->count();

        $product_category->delete();

        $message = $affected > 0
            ? "{$name} deleted. {$affected} product(s) are now uncategorized."
            : "{$name} deleted.";

        return redirect()->route('company.product-categories.index', ['company' => $company->slug])->with('status', $message);
    }
}
