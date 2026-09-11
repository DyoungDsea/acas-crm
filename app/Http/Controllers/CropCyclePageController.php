<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCropCycleRequest;
use App\Models\Company;
use App\Models\CropCycle;

// Available to farms AND agric_services — a consultancy managing a
// client's crop on their behalf produces the same shape of data
// (planted, expected harvest, yield) as a farm managing its own; the
// company_id scope is what keeps GreenAcre's rows separate from an
// actual farm's rows, not the company type. Sidebar filtering via
// config/menus.php's 'types' array is a UX convenience only, so
// every method here also checks $company->type directly.
//
// No destroy() on purpose, same reasoning as every other operational
// record in this app: a crop cycle is history — what was planted,
// what came of it — not a draft. Correcting a mistake means editing
// it (including setting status to "closed"), not removing it.
class CropCyclePageController extends Controller
{
    private const ALLOWED_TYPES = ['farms', 'agric_services'];

    public function index(Company $company)
    {
        abort_unless(in_array($company->type, self::ALLOWED_TYPES), 404);

        $cropCycles = CropCycle::where('company_id', $company->id)
            ->orderByDesc('planted_at')
            ->get();

        return view('company.crop-cycles.index', [
            'company' => $company,
            'cropCycles' => $cropCycles,
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreCropCycleRequest $request, Company $company)
    {
        abort_unless(in_array($company->type, self::ALLOWED_TYPES), 404);

        $cycle = CropCycle::create([
            ...$request->validated(),
            'company_id' => $company->id,
        ]);

        return redirect()->route('company.crop-cycles.index', ['company' => $company->slug])
            ->with('status', "Crop cycle for {$cycle->plot_label} added.");
    }

    public function update(StoreCropCycleRequest $request, Company $company, CropCycle $crop_cycle)
    {
        abort_unless(in_array($company->type, self::ALLOWED_TYPES) && $crop_cycle->company_id === $company->id, 404);

        $crop_cycle->update($request->validated());

        return redirect()->route('company.crop-cycles.index', ['company' => $company->slug])
            ->with('status', "Crop cycle for {$crop_cycle->plot_label} updated.");
    }
}
