<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Books;
use App\Services\GeminiService;

class FillBookVectors extends Command
{
    // Terminalda ishlatish uchun nom: php artisan books:vectorize
    protected $signature = 'books:vectorize';
    protected $description = 'Barcha kitoblarni Gemini orqali vektorlashtiradi';

    public function handle()
    {
        $service = new GeminiService();
        
        // Faqat vektorData bo'sh bo'lgan kitoblarni olamiz
        $books = Books::whereNull('vectorData')->get();
        $count = $books->count();

        if ($count === 0) {
            $this->info("Hamma kitoblar allaqachon vektorlashtirilgan.");
            return;
        }

        $this->info("$count ta kitobni qayta ishlash boshlandi...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($books as $book) {
            try {
                // Teglarni yuklab olish
                $tags = $book->tags->map(fn($t) => "{$t->tag_name_uz}, {$t->tag_name_ru}, {$t->tag_name_en}")->implode(', ');
                $category = $book->category ? $book->category->title : '';

                // Kontekstni yig'ish
                $contextText = "Nomi: {$book->name}. Muallif: {$book->author}. Kategoriya: {$category}. Teglar: {$tags}. Mazmuni: {$book->description}.";

                // Gemini API orqali vektorni olish
                $vector = $service->getVector($contextText);

                // Bazani yangilash
                $book->updateQuietly(['vectorData' => $vector]);

            } catch (\Exception $e) {
                $this->error("\nKitob ID {$book->id} da xatolik: " . $e->getMessage());
            }

            $bar->advance();
            // API limitidan oshib ketmaslik uchun qisqa tanaffus (ixtiyoriy)
            usleep(200000); 
        }

        $bar->finish();
        $this->info("\n\nBarcha kitoblar muvaffaqiyatli vektorlashtirildi!");
    }
}