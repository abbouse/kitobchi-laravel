<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Books;
use App\Services\AuthorDirectoryService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            'ai_candidates' => Author::query()
                ->where(function ($query) {
                    $query->whereNull('image')->orWhere('image', '');
                })
                ->where(function ($query) {
                    $query->where('name', 'not regexp', '[,/&;+]')
                        ->where('name', 'not like', '% va %')
                        ->where('name', 'not like', '% and %')
                        ->where('name', 'not like', '% feat %')
                        ->where('name', 'not like', '% ft %')
                        ->where('name', 'not like', '% x %');
                })
                ->count(),
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
        $author->loadCount('books');

        return view('a122.authors.edit', compact('author'));
    }

    public function generateImagePrompt(Author $author)
    {
        if (! $author->needs_ai_portrait) {
            return back()->with('error', 'Bu muallif uchun AI portret talab qilinmaydi. Ko‘p muallifli kartalarda default avatar yetarli.');
        }

        $prompt = $this->buildAuthorImagePrompt($author);

        return redirect()
            ->route('admin.authors.edit', $author)
            ->with('success', 'ChatGPT uchun rasm prompti tayyorlandi.')
            ->with('author_ai_prompt', $prompt);
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
            // GLOBAL KATALOG: avval karta, keyin uning takliflari; ulanmaganlar odatdagidek
            \App\Models\BookEdition::query()->where('author_id', $author->id)->update(['author' => $author->name]);
            Books::writingFromCatalog(fn () => Books::query()
                ->whereNotNull('edition_id')
                ->where('author_id', $author->id)
                ->toBase()
                ->update(['author' => $author->name, 'vector_text_hash' => null]));
            // vector_text_hash = null — scheduler qayta embed qiladi (Boshqaruv
            // paneldagi updateAuthor bilan bir xil xulq-atvor)
            Books::query()
                ->where('author_id', $author->id)
                ->update(['author' => $author->name, 'vector_text_hash' => null]);
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

    private function buildAuthorImagePrompt(Author $author): string
    {
        $fallback = $this->fallbackAuthorImagePrompt($author);

        try {
            /** @var OpenAIService $ai */
            $ai = app(OpenAIService::class);

            $prompt = trim($ai->askSimpleWithMessages([
                [
                    'role' => 'system',
                    'content' => "You write concise image-generation prompts for realistic author portraits. Return only the prompt text in English. No markdown, no explanation.",
                ],
                [
                    'role' => 'user',
                    'content' => "Create a premium image prompt for a bookstore admin panel portrait. Author name: {$author->name}. Requirements: photorealistic editorial portrait, neutral studio background, centered composition, soft natural lighting, respectful and culturally neutral styling, no text, no watermark, shoulders-up, 1:1 framing, suitable for an online book catalog. If the person is not globally well-known, still write a tasteful generic author portrait prompt anchored to the provided name without inventing biography details.",
                ],
            ], 180, 0.4));

            if ($prompt !== '' && ! str_contains($prompt, 'Kechirasiz')) {
                return $prompt;
            }
        } catch (\Throwable $e) {
            Log::warning('author.ai_image_prompt_failed', [
                'author_id' => $author->id,
                'message' => $e->getMessage(),
            ]);
        }

        return $fallback;
    }

    private function fallbackAuthorImagePrompt(Author $author): string
    {
        return "Photorealistic editorial portrait of author {$author->name}, shoulders-up, centered composition, soft natural studio lighting, clean neutral background, calm confident expression, realistic skin texture, high detail, bookstore catalog profile image, square 1:1 crop, no text, no watermark.";
    }
}
