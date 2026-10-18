<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Requests\SkillRequest;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class SkillController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureUserIsAdmin::class);
    }

    public function index()
    {
        return Inertia::render('Skills/Index', [
            'skills' => Skill::withCount(['opportunities', 'candidates'])->orderBy('name')->paginate(20),
        ]);
    }

    public function create()
    {
        return Inertia::render('Skills/Edit');
    }

    public function store(SkillRequest $request)
    {
        Skill::create($request->validated());

        return Redirect::route('skills.index')->with('toast', [
            'type' => 'store',
            'message' => 'Skill created successfully.'
        ]);
    }

    public function show(Skill $skill)
    {
        return Redirect::route('skills.edit', $skill);
    }

    public function edit(Skill $skill)
    {
        return Inertia::render('Skills/Edit', [
            'skill' => $skill->loadCount(['opportunities', 'candidates']),
        ]);
    }

    public function update(SkillRequest $request, Skill $skill)
    {
        $skill->update($request->validated());

        return Redirect::route('skills.index')->with('toast', [
            'type' => 'update',
            'message' => 'Skill updated successfully.'
        ]);
    }

    public function destroy(Skill $skill)
    {
        DB::transaction(function () use ($skill) {
            DB::table('skillables')->where('skill_id', $skill->id)->delete();
            $skill->delete();
        });

        return Redirect::route('skills.index')->with('toast', [
            'type' => 'destroy',
            'message' => 'Skill deleted successfully.'
        ]);
    }
}
