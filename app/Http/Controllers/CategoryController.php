<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $categories = Category::query()
            ->withCount('books')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug($validated['name']);

        $category = Category::query()->create($validated);

        return response()->json($category, 201);
    }

    public function show(Category $category): JsonResponse
    {
        $category->loadCount('books');

        return response()->json($category);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
            'description' => ['nullable', 'string'],
        ]);

        $validated['slug'] = $validated['name'] === $category->name
            ? $category->slug
            : $this->generateUniqueSlug($validated['name'], $category->id);

        $category->update($validated);

        return response()->json($category->fresh());
    }

    public function destroy(Category $category): Response
    {
        $category->delete();

        return response()->noContent();
    }

    private function generateUniqueSlug(string $name, ?int $ignoreCategoryId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $iteration = 1;

        while (
            Category::query()
                ->when($ignoreCategoryId, fn ($query) => $query->where('id', '!=', $ignoreCategoryId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$iteration;
            $iteration++;
        }

        return $slug;
    }
}
