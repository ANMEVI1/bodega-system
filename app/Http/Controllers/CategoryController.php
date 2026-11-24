<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\InputGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::query()
            ->withCount('products')
            ->when($request->string('q')->trim()->value(), function ($query, $term) {
                $escaped = InputGuard::escapeLike($term);
                $query->where(fn ($q) => $q->where('name', 'like', "%{$escaped}%")->orWhere('description', 'like', "%{$escaped}%"));
            })
            ->when($request->has('status') && $request->input('status') !== '', function ($query) use ($request) {
                $query->where('active', $request->boolean('status'));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.categories.index', [
            'categories' => $categories,
            'totalCategories' => Category::count(),
            'activeCount' => Category::where('active', true)->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new Category(['color' => 'fuchsia', 'active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['slug'] = Str::slug($validated['name']);

        Category::create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Categoría creada exitosamente.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $oldName = $category->name;
        $validated = $this->validated($request, $category);
        $validated['slug'] = Str::slug($validated['name']);

        $category->update($validated);

        // Keep products category text in sync if name changed
        if ($oldName !== $validated['name']) {
            Product::where('category_id', $category->id)->update(['category' => $validated['name']]);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $count = $category->products()->count();

        if ($count > 0) {
            return back()->withErrors([
                'category' => "No se puede eliminar la categoría «{$category->name}» porque contiene {$count} " . ($count === 1 ? 'producto asociado' : 'productos asociados') . '. Reasigna o desactiva la categoría en su lugar.',
            ]);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Categoría eliminada.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category)],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['required', 'string', Rule::in(['fuchsia', 'violet', 'emerald', 'amber', 'sky', 'rose'])],
            'active' => ['nullable', 'boolean'],
        ]);

        $validated['active'] = $request->boolean('active');

        return $validated;
    }
}

