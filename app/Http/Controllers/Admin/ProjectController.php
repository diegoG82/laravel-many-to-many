<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Models\Type;
use App\Models\Technology;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $types = Type::paginate(10);
        $data = $request->all();

        if ($request->has('type_id') && !is_null($data['type_id'])) {
            $projects = Project::where('type_id', $data['type_id'])->paginate(10);
        } else {
            $projects = Project::paginate(10);
        }

        return view('admin.projects.index', compact('projects', 'types'));
    }
    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $types = Type::all();
        $technologies = Technology::all();
        $project = new Project();
        return view('admin.projects.create', compact('types', 'technologies', 'project'));
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProjectRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['title']);
        $data['type_id'] = $request->input('type_id');

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $imagePath = $request->file('image')->store('project_images', 'public');
            $data['image'] = $imagePath;
        }

        $project = Project::create($data);

        // Aggiungi le technologies associate
        if ($request->has('technologies')) {
            $project->technologies()->attach($request->input('technologies'));
        }

        return redirect()->route('admin.projects.index')->with('message', "{$project->title} è stato creato");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function show(Project $project)
    {
        return view('admin.projects.show', compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function edit(Project $project)
    {
        $types = Type::all();
        $technologies = Technology::all();
        return view('admin.projects.edit', compact('project', 'types', 'technologies'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['title']);

        if ($request->hasFile('new_image')) {
            if ($project->image) {
                Storage::delete($project->image);
            }
            $path = $request->file('new_image')->store('project_images', 'public');
            $data['image'] = $path;
        }

        $project->update($data);

        if ($request->has('technologies')) {
            $project->technologies()->sync($request->input('technologies'));
        } else {
            // rimuovi le tecnologie
            $project->technologies()->detach();
        }

        return redirect()->route('admin.projects.index')->with('message', "{$project->title} è stato modificato con successo");
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function destroy(Project $project)
    {
        if ($project->image) {
            Storage::delete($project->image);
        }

        $project->delete();
        return redirect()->route('admin.projects.index')->with('message', "{$project->title} è stato cancellato");
    }
}
