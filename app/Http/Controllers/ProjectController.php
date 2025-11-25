<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

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
        ]);

        //Store Uploaded file in storage/app/public/projects
        $file_path = $request->file('file')->store('projects', 'public');

        // Get the category and assign a random thumbnail from the corresponding folder
        $category = Category::findOrFail($request->category_id);
        $thumbnail = $this->getRandomCategoryThumbnail($category);

        //Save project info in the database
        Project::create([
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $file_path,
            // Set upload_date server-side to current date
            'upload_date' => now()->toDateString(),
            'category_id' => $request->category_id,
            'user_id' => Auth::id(), //logged in user id
            'thumbnail' => $thumbnail,
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
        // 1. Find the project by id
        $project = Project::findOrFail($id);

        // 2. Validate the data
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'file' => 'sometimes|file|mimes:pdf,docx|max:20480', // Max 20MB
        ]);

        // 3. Authorize the user
        $user = Auth::user();
        if ($user->role !== 'admin' && $project->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // 4. If there is a new file, replace the old one
        if ($request->hasFile('file')) {
            // Delete the old file if it exists
            if ($project->file_path && Storage::disk('public')->exists($project->file_path)) {
                Storage::disk('public')->delete($project->file_path);
            }

            // Store the new file
            $file_path = $request->file('file')->store('projects', 'public');
        } else {
            $file_path = $project->file_path;
        }

        // 5. Update the project details
        $project->update([
            'title' => $validated['title'] ?? $project->title,
            'description' => $validated['description'] ?? $project->description,
            'category_id' => $validated['category_id'] ?? $project->category_id,
            // Do not allow clients to change upload_date via API; keep existing value
            'file_path' => $file_path,
        ]);

        // Refresh and return the updated project
        $project->refresh();
        return response()->json(['message' => 'Project updated successfully', 'project' => $project]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //1.Find the project by id
        $project = Project::findOrFail($id);

        //2.Make sure the logged in user owns the project
         $user = Auth::user();
         if ($user->role !== 'admin' && $project->user_id !== $user->id) {
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

    /**
     * Get a random thumbnail from the category's default folder.
     * 
     * Folder structure: public/default-categories/{categorySlug}/
     * Categories: educ, env, tech, med
     * 
     * @param Category $category
     * @return string|null Relative path to the thumbnail (e.g., 'default-categories/educ/image1.jpg')
     */
    public function getRandomCategoryThumbnail(Category $category): ?string
    {
        // Map category names to folder slugs
        $categoryMapping = [
            'education' => 'educ',
            'environment' => 'env',
            'technology' => 'tech',
            'health' => 'med',
        ];

        // Get the folder slug based on category name (case-insensitive)
        $slug = $categoryMapping[strtolower($category->name)] ?? null;

        if (!$slug) {
            return null;
        }

        // Build the path to the category folder (actual repo folder is `public/default/categories/{slug}`)
        $categoryFolderPath = public_path("default/categories/{$slug}");

        // Check if the folder exists
        if (!File::isDirectory($categoryFolderPath)) {
            return null;
        }

        // Get all image files from the folder (jpg, jpeg, png, webp, gif)
        $images = File::glob($categoryFolderPath . '/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE);

        if (empty($images)) {
            return null;
        }

        // Pick a random image
        $randomImage = $images[array_rand($images)];

        // Return the relative path from public folder
        return 'default/categories/' . $slug . '/' . basename($randomImage);
    }

}
