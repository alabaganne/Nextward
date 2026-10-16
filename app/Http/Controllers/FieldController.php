<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Requests\FieldRequest;
use App\Http\Resources\FieldResource;
use App\Models\Field;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class FieldController extends Controller
{
    public function __construct()
    {
        $this->middleware(EnsureUserIsAdmin::class);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inertia::render('Fields/Index', [
            'fields' => Field::withCount(['internships', 'students'])->latest()->paginate(12),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Fields/Edit');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(FieldRequest $request)
    {
        Field::create($request->validated());

        return Redirect::route('fields.index')->with('toast', [
            'type' => 'store',
            'message' => 'Field created successfully.'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Field  $field
     * @return \Illuminate\Http\Response
     */
    public function show(Field $field)
    {
        return Redirect::route('fields.edit', $field);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Field  $field
     * @return \Illuminate\Http\Response
     */
    public function edit(Field $field)
    {
        return Inertia::render('Fields/Edit', [
            'field' => $field
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Field  $field
     * @return \Illuminate\Http\Response
     */
    public function update(FieldRequest $request, Field $field)
    {
        $field->update($request->validated());

        return Redirect::route('fields.index')->with('toast', [
            'type' => 'update',
            'message' => 'Field updated successfully.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Field  $field
     * @return \Illuminate\Http\Response
     */
    public function destroy(Field $field)
    {
        $field->delete();

        return Redirect::route('fields.index')->with('toast', [
            'type' => 'destroy',
            'message' => 'Field deleted successfully.'
        ]);
    }
}
