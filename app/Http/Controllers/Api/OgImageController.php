<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OgImageController extends Controller
{
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
    'text' => 'Haqiqiy bilim — bilmasligingni bilish va o‘rganishdan to‘xtamaslikdir.',
    'author' => 'Sokrat',
  ),
  13 => 
  array (
    'text' => 'Kelajak bugun o‘qiyotgan va izlanayotganlarga tegishlidir.',
    'author' => 'Malcolm Gladwell',
  ),
  14 => 
  array (
    'text' => 'Kitob — siz xohlagan vaqtda boshlanadigan eng chuqur suhbatdir.',
    'author' => 'Rene Dekart',
  ),
  15 => 
  array (
    'text' => 'Insonning eng katta erkinligi — o‘z fikrini o‘zi shakllantirishidir.',
    'author' => 'Viktor Frankl',
  ),
  16 => 
  array (
    'text' => 'Kitobsiz uy — derazasiz xonaga o‘xshaydi.',
    'author' => 'Mark Twain',
  ),
  17 => 
  array (
    'text' => 'O‘qimagan inson o‘qishni bilmaydigan insondan hech qanday ustunlikka ega emas.',
    'author' => 'Mark Twain',
  ),
  18 => 
  array (
    'text' => 'Ongni kengaytiruvchi yangi g‘oya hech qachon eski holatiga qaytmaydi.',
    'author' => 'Oliver Wendell Holmes',
  ),
  19 => 
  array (
    'text' => 'O‘qish nafaqat bilim, balki ichki xotirjamlik ham beradi.',
    'author' => 'Yuval Noah Harari',
  ),
  20 => 
  array (
    'text' => 'Kitoblar — insoniyatning eng buyuk ixtirosi.',
    'author' => 'Stephen King',
  ),
  21 => 
  array (
    'text' => 'Oddiy odamlar vaqt o‘tkazishni, donolar esa vaqtdan foydalanishni o‘ylaydi.',
    'author' => 'Artur Shopengauer',
  ),
  22 => 
  array (
    'text' => 'Muvaffaqiyat — har kuni takrorlanadigan kichik odatlarning yig‘indisidir.',
    'author' => 'James Clear',
  ),
  23 => 
  array (
    'text' => 'Mukammallikka erishish uchun emas, bugun kechagidan yaxshiroq bo‘lish uchun o‘qing.',
    'author' => 'Simon Sinek',
  ),
  24 => 
  array (
    'text' => 'Yaxshi kitob — yopilganda ham siz bilan birga yashashda davom etadi.',
    'author' => 'Antoine de Saint-Exupery',
  ),
  25 => 
  array (
    'text' => 'Barcha buyuk ishlar kichik qadam va bitta to‘g‘ri fikrdan boshlanadi.',
    'author' => 'Lao-Tszu',
  ),
  26 => 
  array (
    'text' => 'O‘rganishni to‘xtatgan kun — qarish boshlangan kundir.',
    'author' => 'Henry Ford',
  ),
  27 => 
  array (
    'text' => 'Aqlni boyitishning yagona yo‘li — yangi sahifalarni varaqlashdir.',
    'author' => 'Albert Einstein',
  ),
);

    public function generate(Request $request): Response
    {
        $quote = $this->quotes[array_rand($this->quotes)];

        $w = 1200;
        $h = 630;
        $im = imagecreatetruecolor($w, $h);

        imagealphablending($im, true);
        imagesavealpha($im, true);

        // Minimalist Pure White Canvas
        $white = imagecolorallocate($im, 255, 255, 255);
        $textBlack = imagecolorallocate($im, 24, 24, 27);
        $textMuted = imagecolorallocate($im, 113, 113, 122); // #71717a

        imagefilledrectangle($im, 0, 0, $w, $h, $white);

        $fontSerifBold = base_path("vendor/dompdf/dompdf/lib/fonts/DejaVuSerif-Bold.ttf");
        $fontSerifItalic = base_path("vendor/dompdf/dompdf/lib/fonts/DejaVuSerif-Italic.ttf");

        if (! file_exists($fontSerifBold)) {
            $fontSerifBold = "/System/Library/Fonts/Supplemental/Arial Bold.ttf";
        }
        if (! file_exists($fontSerifItalic)) {
            $fontSerifItalic = "/System/Library/Fonts/Supplemental/Arial.ttf";
        }

        // Favicon Icon Only (Top Left Corner)
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

        // Quote Text (Medium Minimalist Editorial Serif)
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

        // Author (Elegant Subtle Italic)
        if (file_exists($fontSerifItalic)) {
            imagettftext($im, 24, 0, 100, $quoteStartY + 30, $textMuted, $fontSerifItalic, "— " . $quote["author"]);
        }

        ob_start();
        imagepng($im);
        $imageData = ob_get_clean();
        imagedestroy($im);

        return response($imageData, 200, [
            "Content-Type" => "image/png",
            "Cache-Control" => "no-cache, no-store, must-revalidate",
            "Pragma" => "no-cache",
            "Expires" => "0",
        ]);
    }
}