<?php

namespace App\Http\Controllers\Developers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiDocsController extends Controller
{
    private const DEFAULT_LIMIT_PER_SECOND = 8;
    private const DEFAULT_LIMIT_PER_MINUTE = 240;

    public function __invoke(Request $request, ?string $page = null)
    {
        $pages = $this->pages();
        $currentSlug = $page ?: 'getting-started';

        if (! isset($pages[$currentSlug])) {
            abort(404);
        }

        return view('developers.api-docs', [
            'baseUrl' => url('/api/v1/client'),
            'currentSlug' => $currentSlug,
            'currentPage' => $pages[$currentSlug],
            'pages' => $pages,
            'groups' => $this->groups(),
            'toc' => $this->toc($currentSlug),
            'endpoints' => $this->endpoints(),
            'defaultLimits' => [
                'per_second' => self::DEFAULT_LIMIT_PER_SECOND,
                'per_minute' => self::DEFAULT_LIMIT_PER_MINUTE,
            ],
        ]);
    }

    private function pages(): array
    {
        return [
            'getting-started' => [
                'title' => 'Boshlash',
                'description' => 'Client API bilan birinchi so‘rovni yuborish va integratsiya tartibini tushunish.',
                'group' => 'Asosiy qo‘llanma',
            ],
            'authentication' => [
                'title' => 'Autentifikatsiya',
                'description' => 'App ID, secret, headerlar va xavfsiz saqlash qoidalari.',
                'group' => 'Asosiy qo‘llanma',
            ],
            'rate-limits' => [
                'title' => 'Limit va cache',
                'description' => 'So‘rov limitlari, response cache, ETag va 304 javoblari.',
                'group' => 'Asosiy qo‘llanma',
            ],
            'products' => [
                'title' => 'Mahsulotlar API',
                'description' => 'Kitob, kanselyariya, tavsiyalar va seller mahsulotlarini olish.',
                'group' => 'Endpointlar',
            ],
            'search' => [
                'title' => 'Qidiruv API',
                'description' => 'Global qidiruv, kategoriyalar va kategoriya ichidagi mahsulotlar.',
                'group' => 'Endpointlar',
            ],
            'errors' => [
                'title' => 'Xatolar',
                'description' => 'Status kodlar, xato formatlari va integratsiyada tekshiriladigan holatlar.',
                'group' => 'Qo‘shimcha',
            ],
            'changelog' => [
                'title' => 'O‘zgarishlar',
                'description' => 'API versiyasi va kelajakdagi breaking change siyosati.',
                'group' => 'Qo‘shimcha',
            ],
        ];
    }

    private function groups(): array
    {
        return collect($this->pages())
            ->groupBy('group', preserveKeys: true)
            ->map(fn ($items) => $items->keys()->all())
            ->all();
    }

    private function endpoints(): array
    {
        return [
            'products' => [
                ['GET', '/api/v1/client/products/{col}', 'Mahsulotlar ro‘yxati. `col` qiymati odatda kitob yoki kanselyariya turini bildiradi.', 'read'],
                ['GET', '/api/v1/client/products/recommendation/{col}', 'Tavsiya qilinadigan mahsulotlar ro‘yxati.', 'read'],
                ['GET', '/api/v1/client/products/sellers/list', 'Sellerlar va ularning oxirgi mahsulotlari.', 'read'],
                ['GET', '/api/v1/client/products/sellers/by-qr/{token}', 'QR token orqali seller maʼlumotini olish.', 'read'],
                ['GET', '/api/v1/client/products/sellers/profile/{id}/{page}', 'Seller profili va sahifalangan mahsulotlari.', 'read'],
            ],
            'search' => [
                ['GET', '/api/v1/client/search', 'Global qidiruv. `q` query parametri orqali ishlaydi.', 'read'],
                ['GET', '/api/v1/client/search/categories', 'Qidiruv va katalog uchun kategoriyalar ro‘yxati.', 'read'],
                ['GET', '/api/v1/client/search/category/{cat_id}/{type}', 'Kategoriya ichidagi mahsulotlarni olish.', 'read'],
            ],
        ];
    }

    private function toc(string $slug): array
    {
        return match ($slug) {
            'authentication' => [
                ['id' => 'headers', 'label' => 'Headerlar'],
                ['id' => 'secrets', 'label' => 'Secret saqlash'],
                ['id' => 'abilities', 'label' => 'Ruxsatlar'],
            ],
            'rate-limits' => [
                ['id' => 'limits', 'label' => 'Limitlar'],
                ['id' => 'cache', 'label' => 'Cache'],
                ['id' => 'etag', 'label' => 'ETag'],
            ],
            'products' => [
                ['id' => 'list', 'label' => 'Ro‘yxat'],
                ['id' => 'recommendation', 'label' => 'Tavsiyalar'],
                ['id' => 'sellers', 'label' => 'Sellerlar'],
            ],
            'search' => [
                ['id' => 'global', 'label' => 'Global qidiruv'],
                ['id' => 'categories', 'label' => 'Kategoriyalar'],
                ['id' => 'category-products', 'label' => 'Kategoriya mahsulotlari'],
            ],
            'errors' => [
                ['id' => 'statuses', 'label' => 'Status kodlar'],
                ['id' => 'format', 'label' => 'Xato formati'],
                ['id' => 'checklist', 'label' => 'Tekshiruv ro‘yxati'],
            ],
            'changelog' => [
                ['id' => 'versioning', 'label' => 'Versioning'],
                ['id' => 'current', 'label' => 'Joriy versiya'],
            ],
            default => [
                ['id' => 'overview', 'label' => 'Umumiy tushuncha'],
                ['id' => 'first-request', 'label' => 'Birinchi so‘rov'],
                ['id' => 'response', 'label' => 'Javob formati'],
            ],
        };
    }
}
