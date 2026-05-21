<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PublisherController extends Controller
{
    public function index(Request $request)
    {
        $query = Publisher::query()->withCount('books');

        if ($search = trim((string) $request->input('search'))) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $publishers = $query->orderBy('name')->paginate(20)->withQueryString();

        $stats = [
            'total' => Publisher::count(),
            'with_image' => Publisher::query()->whereNotNull('image')->where('image', '!=', '')->count(),
            'linked_books' => Books::query()->whereNotNull('publisher_id')->count(),
        ];

        return view('a122.publishers.index', compact('publishers', 'stats'));
    }

    public function create()
    {
        return view('a122.publishers.edit');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['image'] = $this->storeImage($request);

        Publisher::create($data);

        return redirect()->route('admin.publishers.index')->with('success', "Nashriyot qo'shildi.");
    }

    public function edit(Publisher $publisher)
    {
        return view('a122.publishers.edit', compact('publisher'));
    }

    public function update(Request $request, Publisher $publisher)
    {
        $data = $this->validatedData($request, $publisher);

        if ($request->boolean('remove_image') && $publisher->image && ! str_starts_with($publisher->image, 'http')) {
            Storage::disk('public')->delete($publisher->image);
            $data['image'] = null;
        }

        if ($request->hasFile('image')) {
            if ($publisher->image && ! str_starts_with($publisher->image, 'http')) {
                Storage::disk('public')->delete($publisher->image);
            }
            $data['image'] = $this->storeImage($request);
        }

        $publisher->update($data);

        return redirect()->route('admin.publishers.index')->with('success', 'Nashriyot yangilandi.');
    }

    public function destroy(Publisher $publisher)
    {
        if ($publisher->books()->exists()) {
            return back()->with('error', "Bu nashriyotga bog'langan kitoblar mavjud — o'chirib bo'lmaydi.");
        }

        if ($publisher->image && ! str_starts_with($publisher->image, 'http')) {
            Storage::disk('public')->delete($publisher->image);
        }

        $publisher->delete();

        return redirect()->route('admin.publishers.index')->with('success', "Nashriyot o'chirildi.");
    }

    private function validatedData(Request $request, ?Publisher $publisher = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('publishers', 'name')->ignore($publisher?->id),
            ],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'remove_image' => ['nullable', 'boolean'],
        ]);
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return $request->file('image')->store('publishers', 'public');
    }
}
