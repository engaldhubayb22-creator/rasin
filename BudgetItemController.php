<?php

namespace App\Http\Controllers;

use App\Models\BudgetItem;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BudgetItemController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validated($request);
        $data['order'] = (int) $project->budgetItems()->max('order') + 10;

        $project->budgetItems()->create($data);

        return back()->with('success', __('app.budget_item_created'));
    }

    public function update(Request $request, BudgetItem $budgetItem): RedirectResponse
    {
        $budgetItem->update($this->validated($request, true));

        return back()->with('success', __('app.budget_item_updated'));
    }

    public function destroy(BudgetItem $budgetItem): RedirectResponse
    {
        $budgetItem->delete();

        return back()->with('success', __('app.budget_item_deleted'));
    }

    protected function validated(Request $request, bool $partial = false): array
    {
        $rule = fn (array $r) => $partial ? array_merge(['sometimes'], $r) : $r;

        return $request->validate([
            'item_code' => $rule(['nullable', 'string', 'max:20']),
            'category' => $rule(['nullable', 'string', 'max:120']),
            'name' => $rule(['required', 'string', 'max:255']),
            'name_en' => $rule(['nullable', 'string', 'max:255']),
            'budgeted_amount' => $rule(['nullable', 'numeric', 'min:0', 'max:9999999999999']),
            'committed_amount' => $rule(['nullable', 'numeric', 'min:0', 'max:9999999999999']),
            'actual_amount' => $rule(['nullable', 'numeric', 'min:0', 'max:9999999999999']),
            'notes' => $rule(['nullable', 'string', 'max:1000']),
        ]);
    }
}
