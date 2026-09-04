<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePipelineRequest;
use App\Http\Requests\StorePipelineStageRequest;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Only for companies where a negotiated, multi-stage sale actually
// fits the business — see Company::PIPELINE_ENABLED_TYPES. The
// sidebar already hides this for excluded types (e.g. Pharmacy), but
// that's a UX convenience, not enforcement — a URL typed directly
// bypasses the menu entirely, so every method here checks the
// company's actual type too.
class PipelinePageController extends Controller
{
    public function index(Company $company)
    {
        abort_unless(in_array($company->type, Company::PIPELINE_ENABLED_TYPES, true), 404);

        $pipelines = Pipeline::where('company_id', $company->id)
            ->with(['stages' => fn($q) => $q->withCount('deals')->orderBy('order')])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('company.pipelines.index', [
            'company' => $company,
            'pipelines' => $pipelines,
            'menu' => config('menus.company'),
            'contextLabel' => $company->name,
        ]);
    }

    public function store(StorePipelineRequest $request, Company $company)
    {
        abort_unless(in_array($company->type, Company::PIPELINE_ENABLED_TYPES, true), 404);

        $pipeline = Pipeline::create([
            'company_id' => $company->id,
            'name' => $request->validated('name'),
            'is_default' => $request->boolean('is_default'),
        ]);

        foreach ($request->validated('stages', []) as $i => $stage) {
            $pipeline->stages()->create([
                'name' => $stage['name'],
                'order' => $stage['order'] ?? $i,
            ]);
        }

        return redirect()->route('company.pipelines.index', ['company' => $company->slug])->with('status', "{$pipeline->name} created.");
    }

    public function update(StorePipelineRequest $request, Company $company, Pipeline $pipeline)
    {
        abort_unless(in_array($company->type, Company::PIPELINE_ENABLED_TYPES, true), 404);
        abort_unless($pipeline->company_id === $company->id, 404);

        $pipeline->update([
            'name' => $request->validated('name'),
            'is_default' => $request->boolean('is_default'),
        ]);

        return redirect()->route('company.pipelines.index', ['company' => $company->slug])->with('status', "{$pipeline->name} updated.");
    }

    // Deleting a pipeline cascades to its stages (they're just
    // configuration, not records worth protecting on their own) —
    // but if any deal sits in one of those stages, this blocks the
    // whole thing with a clear message rather than letting it fail
    // as a raw database error from the restrictOnDelete() constraint.
    public function destroy(Company $company, Pipeline $pipeline)
    {
        abort_unless(in_array($company->type, Company::PIPELINE_ENABLED_TYPES, true), 404);
        abort_unless($pipeline->company_id === $company->id, 404);

        $dealCount = Deal::whereIn('pipeline_stage_id', $pipeline->stages()->pluck('id'))->count();

        if ($dealCount > 0) {
            return redirect()->route('company.pipelines.index', ['company' => $company->slug])
                ->with('status', "{$pipeline->name} has {$dealCount} deal(s) in it. Move or resolve those deals first.");
        }

        $pipeline->delete();

        return redirect()->route('company.pipelines.index', ['company' => $company->slug])->with('status', "{$pipeline->name} deleted.");
    }

    public function storeStage(StorePipelineStageRequest $request, Company $company, Pipeline $pipeline)
    {
        abort_unless(in_array($company->type, Company::PIPELINE_ENABLED_TYPES, true), 404);
        abort_unless($pipeline->company_id === $company->id, 404);

        $nextOrder = ($pipeline->stages()->max('order') ?? -1) + 1;

        $pipeline->stages()->create([
            'name' => $request->validated('name'),
            'order' => $nextOrder,
        ]);

        return redirect()->route('company.pipelines.index', ['company' => $company->slug])->with('status', 'Stage added.');
    }

    public function updateStage(StorePipelineStageRequest $request, Company $company, Pipeline $pipeline, PipelineStage $stage)
    {
        abort_unless(in_array($company->type, Company::PIPELINE_ENABLED_TYPES, true), 404);
        abort_unless($pipeline->company_id === $company->id && $stage->pipeline_id === $pipeline->id, 404);

        $stage->update(['name' => $request->validated('name')]);

        return redirect()->route('company.pipelines.index', ['company' => $company->slug])->with('status', 'Stage updated.');
    }

    public function destroyStage(Company $company, Pipeline $pipeline, PipelineStage $stage)
    {
        abort_unless(in_array($company->type, Company::PIPELINE_ENABLED_TYPES, true), 404);
        abort_unless($pipeline->company_id === $company->id && $stage->pipeline_id === $pipeline->id, 404);

        $dealCount = $stage->deals()->count();

        if ($dealCount > 0) {
            return redirect()->route('company.pipelines.index', ['company' => $company->slug])
                ->with('status', "\"{$stage->name}\" has {$dealCount} deal(s) in it. Move them to another stage first.");
        }

        $stage->delete();

        return redirect()->route('company.pipelines.index', ['company' => $company->slug])->with('status', 'Stage deleted.');
    }

    // No drag-and-drop library — just swaps this stage's `order`
    // with its neighbor's. Simple, no JS dependency, good enough for
    // a handful of stages per pipeline.
    public function moveStage(Request $request, Company $company, Pipeline $pipeline, PipelineStage $stage)
    {
        abort_unless(in_array($company->type, Company::PIPELINE_ENABLED_TYPES, true), 404);
        abort_unless($pipeline->company_id === $company->id && $stage->pipeline_id === $pipeline->id, 404);

        $direction = $request->input('direction');
        $siblings = $pipeline->stages()->orderBy('order')->get();
        $index = $siblings->search(fn($s) => $s->id === $stage->id);
        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index !== false && $swapIndex >= 0 && $swapIndex < $siblings->count()) {
            $swapWith = $siblings[$swapIndex];

            DB::transaction(function () use ($stage, $swapWith) {
                $originalOrder = $stage->order;
                $stage->update(['order' => $swapWith->order]);
                $swapWith->update(['order' => $originalOrder]);
            });
        }

        return redirect()->route('company.pipelines.index', ['company' => $company->slug]);
    }
}
