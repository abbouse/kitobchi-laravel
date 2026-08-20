<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RotateOgImageCommand extends Command
{
    protected $signature = "og:rotate";
    protected $description = "Rotates and generates a fresh dynamic Medium-style OG quote image";

    private array $quotes = array (
  0 => 
  array (
    'text' => 'Bugungi o‘qigan kitobingiz — ertangi fikrlashingizni belgilaydi.',
    'author' => 'James Clear',
  ),
  1 => 
  array (
    'text' => 'Kitob o‘qish — boshqa birovning miyasi bilan sayohat qilishdir.',
    'author' => 'Haruki Murakami',
  ),
  2 => 
  array (
    'text' => 'Bitta yaxshi kitob mingta oddiy suhbatdan ko‘proq narsa beradi.',
    'author' => 'Warren Buffett',
  ),
  3 => 
  array (
    'text' => 'Dunyo bir kitobdir, sayohat qilmaganlar faqat bitta sahifasini o‘qiydi.',
    'author' => 'Avliyo Avgustin',
  ),
  4 => 
  array (
    'text' => 'Kitoblar — vaqt ummonida suzib yuruvchi fikr kemalaridir.',
    'author' => 'Francis Bacon',
  ),
  5 => 
  array (
    'text' => 'O‘zingizni kashf etishning eng tezkor yo‘li — kitob sahifalaridir.',
    'author' => 'Paulo Coelho',
  ),
  6 => 
  array (
    'text' => 'Agar har kuni 20 daqiqa kitob o‘qisangiz, bir yilda 30 ta kitob bitirasiz.',
    'author' => 'Robin Sharma',
  ),
  7 => 
  array (
    'text' => 'Fikrlar o‘zgarsa, hayot o‘zgaradi. Fikrni esa kitob o‘zgartiradi.',
    'author' => 'Marcus Aurelius',
  ),
  8 => 
  array (
    'text' => 'Bilimga qilingan investitsiya doim eng yuqori dividend keltiradi.',
    'author' => 'Benjamin Franklin',
  ),
  9 => 
  array (
    'text' => 'Har bir buyuk yetakchi — avvalo buyuk kitobxondir.',
    'author' => 'Harry Truman',
  ),
  10 => 
  array (
    'text' => 'Kitob — bu qo‘lda ushlab turiladigan mo‘jizadir.',
    'author' => 'Neil Gaiman',
  ),
  11 => 
  array (
    'text' => 'O‘qish — aql uchun xuddi sport tana uchun bo‘lganidek zarurdir.',
    'author' => 'Joseph Addison',
  ),
  12 => 
  array (
    'text' => 'Kelajak bugun o‘qiyotgan va izlanayotganlarga tegishlidir.',
    'author' => 'Malcolm Gladwell',
  ),
  13 => 
  array (
    'text' => 'Kitobsiz uy — derazasiz xonaga o‘xshaydi.',
    'author' => 'Mark Twain',
  ),
  14 => 
  array (
    'text' => 'O‘qish nafaqat bilim, balki ichki xotirjamlik ham beradi.',
    'author' => 'Yuval Noah Harari',
  ),
  15 => 
  array (
    'text' => 'Muvaffaqiyat — har kuni takrorlanadigan kichik odatlarning yig‘indisidir.',
    'author' => 'James Clear',
  ),
  16 => 
  array (
    'text' => 'Yaxshi kitob — yopilganda ham siz bilan birga yashashda davom etadi.',
    'author' => 'Antoine de Saint-Exupery',
  ),
  17 => 
  array (
    'text' => 'Oddiy odamlar vaqt o‘tkazishni, donolar esa vaqtdan foydalanishni o‘ylaydi.',
    'author' => 'Artur Shopengauer',
  ),
  18 => 
  array (
    'text' => 'Aqlni boyitishning yagona yo‘li — yangi sahifalarni varaqlashdir.',
    'author' => 'Albert Einstein',
  ),
);

    public function handle(): int
    {
        $quote = $this->quotes[array_rand($this->quotes)];

        $w = 1200;
        $h = 630;
        $im = imagecreatetruecolor($w, $h);

        imagealphablending($im, true);
        imagesavealpha($im, true);

        $white = imagecolorallocate($im, 255, 255, 255);
        $textBlack = imagecolorallocate($im, 24, 24, 27);
        $textMuted = imagecolorallocate($im, 113, 113, 122);

        imagefilledrectangle($im, 0, 0, $w, $h, $white);

        $fontSerifBold = base_path("vendor/dompdf/dompdf/lib/fonts/DejaVuSerif-Bold.ttf");
        $fontSerifItalic = base_path("vendor/dompdf/dompdf/lib/fonts/DejaVuSerif-Italic.ttf");

        if (! file_exists($fontSerifBold)) {
            $fontSerifBold = "/System/Library/Fonts/Supplemental/Arial Bold.ttf";
        }
        if (! file_exists($fontSerifItalic)) {
            $fontSerifItalic = "/System/Library/Fonts/Supplemental/Arial.ttf";
        }

        $iconPath = public_path("apple-touch-icon.png");
        if (! file_exists($iconPath)) {
            $iconPath = public_path("favicon-48x48.png");
        }

        if (file_exists($iconPath)) {
            $icon = imagecreatefrompng($iconPath);
            if ($icon) {
                $iw = imagesx($icon);
                $ih = imagesy($icon);
                $targetIconSize = 52;
                imagecopyresampled($im, $icon, 100, 80, 0, 0, $targetIconSize, $targetIconSize, $iw, $ih);
                imagedestroy($icon);
            }
        }

        $text = $quote["text"];
        $fontSize = mb_strlen($text) > 60 ? 34 : 40;

        $lines = explode("
", wordwrap($text, 36, "
"));
        $quoteStartY = 240;

        foreach ($lines as $line) {
            if (file_exists($fontSerifBold)) {
                imagettftext($im, $fontSize, 0, 100, $quoteStartY, $textBlack, $fontSerifBold, $line);
            }
            $quoteStartY += (int) ($fontSize * 1.55);
        }

        if (file_exists($fontSerifItalic)) {
            imagettftext($im, 24, 0, 100, $quoteStartY + 30, $textMuted, $fontSerifItalic, "— " . $quote["author"]);
        }

        imagepng($im, public_path("og-image.png"));

        $webPublic = base_path("web/public/og-image.png");
        if (file_exists(dirname($webPublic))) {
            imagepng($im, $webPublic);
        }

        imagedestroy($im);

        $this->info("Successfully rotated OG image to quote by: " . $quote["author"]);
        return Command::SUCCESS;
    }
}