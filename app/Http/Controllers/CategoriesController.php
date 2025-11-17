<?php

namespace App\Http\Controllers;


use App\Models\Category;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Category::where('isDeleted', false);

        if ($search) {
            $query->where('categoryName', 'LIKE', "%$search%");
        }

        $categories = $query->paginate(10);
        $total= $query->count();

        return view('AdminPage.Categories', compact('categories', 'search','total'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'categoryName' => 'required',
            'categoryImage' => 'nullable|image',
            'categoryDesc' => 'nullable',
        ]);

        $data = $request->only('categoryName', 'categoryDesc');
        if ($request->hasFile('categoryImage')) {
            $data['categoryImage'] = $request->file('categoryImage')->store('categories', 'public');
        }

        Category::create($data);

        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'categoryName' => 'required',
            'categoryImage' => 'nullable|image',
            'categoryDesc' => 'nullable',
        ]);

        $data = $request->only('categoryName', 'categoryDesc');
        if ($request->hasFile('categoryImage')) {
            $data['categoryImage'] = $request->file('categoryImage')->store('categories', 'public');
        }

        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $category->update(['isDeleted' => 1]);
        return redirect()->route('categories.index')->with('success', 'Category deleted (soft) successfully.');
    }
}

