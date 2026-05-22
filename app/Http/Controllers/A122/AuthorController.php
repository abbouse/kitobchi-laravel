<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Books;
use App\Services\AuthorDirectoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AuthorController extends Controller
{
    public function __construct(
        protected AuthorDirectoryService $authorDirectory
    ) {
    }

    public function index(Request $request)
    {
        $query = Author::query()->withCount('books');

        if ($search = trim((string) $request->input('search'))) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $authors = $query->orderBy('name')->paginate(20)->withQueryString();

        $stats = [
            'total' => Author::query()->count(),
            'with_image' => Author::query()->whereNotNull('image')->where('image', '!=', '')->count(),
            'linked_books' => Books::query()->whereNotNull('author_id')->count(),
        ];

        return view('a122.authors.index', compact('authors', 'stats'));
    }

    public function create()
    {
        return view('a122.authors.edit');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['image'] = $this->storeImage($request, $data['image'] ?? null);

        Author::query()->create($data);

        return redirect()->route('admin.authors.index')->with('success', "Muallif qo'shildi.");
    }

    public function edit(Author $author)
    {
        return view('a122.authors.edit', compact('author'));
    }

    public function update(Request $request, Author $author)
    {
        $data = $this->validatedData($request, $author);
        $nameChanged = ($data['name'] ?? $author->name) !== $author->name;

        if ($request->boolean('remove_image') && $author->image && ! str_starts_with($author->image, 'http')) {
            Storage::disk('public')->delete($author->image);
            $data['image'] = null;
        }

        if ($request->hasFile('image_file')) {
            if ($author->image && ! str_starts_with($author->image, 'http')) {
                Storage::disk('public')->delete($author->image);
            }
            $data['image'] = $this->storeImage($request, $data['image'] ?? null);
        } elseif (array_key_exists('image', $data)) {
            $data['image'] = $this->storeImage($request, $data['image']);
        }

        $author->update($data);

        if ($nameChanged) {
            Books::query()
                ->where('author_id', $author->id)
                ->update(['author' => $author->name]);
        }

        return redirect()->route('admin.authors.index')->with('success', 'Muallif yangilandi.');
    }

    public function destroy(Author $author)
    {
        if ($author->books()->exists()) {
            return back()->with('error', "Bu muallifga bog'langan kitoblar mavjud — o'chirib bo'lmaydi.");
        }

        if ($author->image && ! str_starts_with($author->image, 'http')) {
            Storage::disk('public')->delete($author->image);
        }

        $author->delete();

        return redirect()->route('admin.authors.index')->with('success', "Muallif o'chirildi.");
    }

    public function syncBookUz(Request $request)
    {
        $limit = max(1, min((int) $request->integer('limit', 24000), 24000));
        $result = $this->authorDirectory->syncBookUzAuthors($limit);

        return redirect()
            ->route('admin.authors.index')
            ->with('success', "Book.uz mualliflari yangilandi: {$result['synced']} ta, yangi {$result['created']} ta, yangilangan {$result['updated']} ta.");
    }

    public function backfillBooks(Request $request)
    {
        $limit = $request->filled('limit') ? max(1, (int) $request->integer('limit')) : null;
        $result = $this->authorDirectory->backfillBooks($limit);

        return redirect()
            ->route('admin.authors.index')
            ->with('success', "Kitoblar mualliflarga ulandi: {$result['linked']} ta, yangi muallif {$result['createdAuthors']} ta.");
    }

    private function validatedData(Request $request, ?Author $author = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('authors', 'name')->ignore($author?->id),
            ],
            'image' => ['nullable', 'string', 'max:2048'],
            'image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'remove_image' => ['nullable', 'boolean'],
            'external_id' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('authors', 'external_id')->ignore($author?->id),
            ],
            'slug' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'string', 'max:2048'],
        ]);
    }

    private function storeImage(Request $request, ?string $fallback = null): ?string
    {
        if ($request->hasFile('image_file')) {
            return $request->file('image_file')->store('authors', 'public');
        }

        $image = trim((string) ($fallback ?? ''));
        return $image === '' ? null : $image;
    }
}
