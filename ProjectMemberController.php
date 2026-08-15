<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'team_role' => ['nullable', 'string', 'max:120'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $project->members()->updateOrCreate(
            ['user_id' => $data['user_id']],
            [
                'team_role' => $data['team_role'] ?? null,
                'is_primary' => $request->boolean('is_primary'),
            ]
        );

        return back()->with('success', __('app.member_added'));
    }

    public function destroy(Project $project, ProjectMember $member): RedirectResponse
    {
        abort_unless($member->project_id === $project->id, 404);

        $member->delete();

        return back()->with('success', __('app.member_removed'));
    }
}
