<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::with(['user', 'category'])->latest()->get();
        return response()->json($projects);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //Validate user input and file
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'file' => 'required|file|mimes:pdf,docx|max:20480', // Max 20MB
            'upload_date' => 'required|date',
        ]);

        //Store Uploaded file in storage/app/public/projects
        $file_path = $request->file('file')->store('projects', 'public');

        //Save project info in the databse
        Project::create([
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $file_path,
            'upload_date' => $request->upload_date,
            'category_id' => $request->category_id,
            'user_id' => Auth::id(), //logged in user id
        ]);

        return response()->json(['message' => 'Project uploaded successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $project = Project::with(['user', 'category'])->findOrFail($id);
        return response()->json($project);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //1.Find the project by id
        $project = Project::findOrFail($id);

        //2.Make sure the logged in user owns the project
        if ($project->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        //3.Validate the data
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'file' => 'sometimes|file|mimes:pdf,docx|max:20480', // Max 20MB
            'upload_date' => 'sometimes|required|date',
        ]);
        //4.If there is a new file, replace the old one
        if ($request->hasFile('file')) {
            //Delete the old file if it exists
            if ($project->file_path && \Storage::disk('public')->exists($project->file_path)) {
                \Storage::disk('public')->delete($project->file_path);
            }
            //Store the new file
            $file_path = $request->file('file')->store('projects', 'public');
            $project->file_path = $file_path;
        }
        //5.Update the other details
        $project->update([
            'title' => $request->title ?? $project->title,
            'description' => $request->description ?? $project->description,
            'category_id' => $request->category_id ?? $project->category_id,
            'upload_date' => $request->upload_date ?? $project->upload_date,
            'file_path' => $project->file_path, //keep or replace if new file
        ]);

        //6.Return a success message
        return response()->json(['message' => 'Project updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //1.Find the project by id
        $project = Project::findOrFail($id);

        //2.Make sure the logged in user owns the project
        if ($project->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        //3.Delete the file from storage
        if ($project->file_path && \Storage::disk('public')->exists($project->file_path)) {
            \Storage::disk('public')->delete($project->file_path);
        }

        //4.Delete the project from database
        $project->delete();

        //5.Return a success message
        return response()->json(['message' => 'Project deleted successfully']);
    }

    public function search(Request $request)
    {
        $query = Project::with(['user', 'category']);

        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('upload_date')) {
            $query->whereDate('upload_date', $request->upload_date);
        }

        $projects = $query->latest()->get();

        return response()->json($projects);
    }

    public function myProjects()
{
    $projects = Project::where('user_id', Auth::id())
        ->with('category')
        ->latest()
        ->get();

    return response()->json($projects);
}

}
