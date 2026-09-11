<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLivestockBatchRequest;
use App\Models\Company;
use App\Models\LivestockBatch;

// Available to farms AND agric_services — same reasoning as
// CropCyclePageController. Sidebar filtering is a UX convenience
// only, so every method here also checks $company->type directly.
//
// No destroy() on purpose — a livestock batch is reared-animal
// history (start count, current count, outcome), not a draft.
class LivestockBatchPageController extends Controller
{
    private const ALLOWED_TYPES = ['farms', 'agric_services'];

    public function index(Company $company)
    {
        abort_unless(in_array($company->type, self::ALLOWED_TYPES), 404);

        $livestockBatches = LivestockBatch::where('company_id', $company->id)
            ->orderByDesc('start_date')
            ->get();

        return view('company.livestock-batches.index', [
            'company' => $company,
            'livestockBatches' => $livestockBatches,
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StoreLivestockBatchRequest $request, Company $company)
    {
        abort_unless(in_array($company->type, self::ALLOWED_TYPES), 404);

        $batch = LivestockBatch::create([
            ...$request->validated(),
            'company_id' => $company->id,
            // Always starts equal to quantity_start — see the
            // comment on StoreLivestockBatchRequest for why this
            // isn't taken from the form on create.
            'quantity_current' => $request->validated('quantity_start'),
        ]);

        return redirect()->route('company.livestock-batches.index', ['company' => $company->slug])
            ->with('status', "Livestock batch {$batch->batch_label} added.");
    }

    public function update(StoreLivestockBatchRequest $request, Company $company, LivestockBatch $livestock_batch)
    {
        abort_unless(in_array($company->type, self::ALLOWED_TYPES) && $livestock_batch->company_id === $company->id, 404);

        $livestock_batch->update($request->validated());

        return redirect()->route('company.livestock-batches.index', ['company' => $company->slug])
            ->with('status', "Livestock batch {$livestock_batch->batch_label} updated.");
    }
}
