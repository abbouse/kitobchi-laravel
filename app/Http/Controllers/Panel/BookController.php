<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\BookCategories;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BooksExport;
use App\Imports\BooksImport;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Books::with(['category', 'seller']);

        // Search
        if ($s = $request->input('search')) {
            $query->where(fn($q) => $q->where('name','like',"%$s%")
                ->orWhere('author','like',"%$s%")
                ->orWhere('id', $s));
        }

        // Moderatsiya filter (tab)
        $tab = $request->input('tab', 'pending');
        match($tab) {
            'pending'  => $query->where('is_approved', 0),
            'approved' => $query->where('is_approved', 1),
            'rejected' => $query->where('is_approved', 2),
            default    => null,
        };

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->input('seller_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $books      = $query->latest()->paginate(20)->withQueryString();
        $categories = BookCategories::orderBy('name_uz')->get();
        $sellers    = Seller::where('status','approved')->orderBy('shop_name')->get();

        $counts = [
            'pending'  => Books::where('is_approved', 0)->count(),
            'approved' => Books::where('is_approved', 1)->count(),
            'rejected' => Books::where('is_approved', 2)->count(),
        ];

        return view('panel.books.index', compact('books','categories','sellers','counts','tab'));
    }

    public function show(Books $book)
    {
        $book->load(['category','seller','tags']);
        return view('panel.books.show', compact('book'));
    }

    public function edit(Books $book)
    {
        $book->load(['category','seller','tags']);
        $categories = BookCategories::orderBy('name_uz')->get();
        return view('panel.books.edit', compact('book','categories'));
    }

    public function update(Request $request, Books $book)
    {
        $data = $request->validate([
            'is_approved'  => 'required|in:0,1,2',
            'status'       => 'required|in:0,1',
            'is_hidden'    => 'boolean',
            'price'        => 'nullable|numeric|min:0',
            'discountPrice'=> 'nullable|numeric|min:0',
            'count'        => 'nullable|integer|min:0',
        ]);

        $book->update($data);

        return redirect()->route('panel.books.show', $book)
            ->with('success', "Kitob ma'lumotlari yangilandi.");
    }

    /**
     * Tez moderatsiya (AJAX yoki oddiy POST)
     */
    public function moderate(Request $request, Books $book)
    {
        $request->validate(['is_approved' => 'required|in:0,1,2']);
        $book->update(['is_approved' => $request->input('is_approved')]);

        $labels = ['0'=>'Kutilmoqda','1'=>'Tasdiqlandi','2'=>'Rad etildi'];
        return back()->with('success', "Kitob: {$labels[$request->input('is_approved')]}");
    }

    public function export(Request $request)
    {
        return Excel::download(new BooksExport($request->all()),
            'books_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function importView()
    {
        return view('panel.books.import');
    }

    public function import(Request $request)
    {
        $request->validate(['file'=>'required|file|mimes:xlsx,xls,csv|max:10240']);
        try {
            Excel::import(new BooksImport, $request->file('file'));
            return redirect()->route('panel.books.index')->with('success', "Kitoblar import qilindi.");
        } catch (\Exception $e) {
            return back()->with('error', "Import xatosi: ".$e->getMessage());
        }
    }
}