<?php
namespace App\Observers;

use App\Models\Books;
use App\Services\GeminiService;

class BooksObserver
{
    public function saved(Books $book)
    {
        // Muhim maydonlar o'zgarganda vektorni yangilaymiz
        if ($book->isDirty(['name', 'author', 'author_id', 'description', 'category_id', 'lang', 'year'])) {
            
            $service = new GeminiService();

            // 1. Teglarni matn ko'rinishiga keltiramiz
            $tags = $book->tags->pluck('tag_name_uz')->implode(', ');

            // 2. Kategoriya nomi
            $category = $book->category ? $book->category->title : '';

            // 3. Gemini uchun "Semantik tavsif" yig'amiz
            // Bu matn qanchalik sifatli bo'lsa, qidiruv shunchalik aniq ishlaydi
            $contextText = "Kitob nomi: {$book->name}. " .
                           "Muallif: {$book->author}. " .
                           "Kategoriya: {$category}. " .
                           "Teglar: {$tags}. " .
                           "Tili: {$book->lang}. " .
                           "Nashr yili: {$book->year}. " .
                           "Muqova: {$book->coverType}. " .
                           "Qisqacha mazmuni: {$book->description}.";

            // Vektorni olish (Gemini API orqali)
            $vector = $service->getVector($contextText);

            // Bazaga saqlash (vectorData ustuniga)
            $book->vectorData = $vector;
            $book->saveQuietly();
        }
    }
}
