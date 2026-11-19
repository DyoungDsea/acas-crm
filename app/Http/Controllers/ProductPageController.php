<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;

// Products is a shared top-level menu item for every company —
// a sack of rice, a drug, a car part, and a solar kit are all just
// "products", grouped by whatever categories that company has set
// up under Products > Categories.
class ProductPageController extends Controller
{
    public function index(Company $company)
    {
        $products = Product::where('company_id', $company->id)
            ->with('category')
            ->orderBy('name')
            ->get();

        return view('company.products.index', [
            'company' => $company,
            'products' => $products,
            'categories' => ProductCategory::where('company_id', $company->id)->orderBy('name')->get(),
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreProductRequest $request, Company $company)
    {
        $product = DB::transaction(function () use ($request, $company) {
            $data = $this->resolveCategory($request->validated(), $company);

            return Product::create([
                ...$data,
                'company_id' => $company->id,
                'is_active' => true,
            ]);
        });

        return redirect()->route('company.products.index', ['company' => $company->slug])
            ->with('status', "{$product->name} added.");
    }

    public function update(StoreProductRequest $request, Company $company, Product $product)
    {
        abort_unless($product->company_id === $company->id, 404);

        DB::transaction(function () use ($request, $company, $product) {
            $product->update($this->resolveCategory($request->validated(), $company));
        });

        return redirect()->route('company.products.index', ['company' => $company->slug])
            ->with('status', "{$product->name} updated.");
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

    /**
     * If a new category name was typed, create it (or reuse the existing one
     * with the same name) and swap its id into the data. Strips the helper field.
     */
    private function resolveCategory(array $data, Company $company): array
    {
        $newName = trim($data['new_category_name'] ?? '');

        if ($newName !== '') {
            $category = ProductCategory::firstOrCreate([
                'company_id' => $company->id,
                'name' => $newName,
            ]);

            $data['category_id'] = $category->id;
        }

        unset($data['new_category_name']);

        return $data;
    }
}
