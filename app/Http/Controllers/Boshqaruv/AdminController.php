<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function login(): Response|\Illuminate\Http\RedirectResponse
    {
        if (Auth::guard('panel')->check()) {
            return redirect()->route('boshqaruv.dashboard');
        }

        return Inertia::render('Login');
    }

    public function authenticate(Request $request): \Illuminate\Http\RedirectResponse
    {
        $creds = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (! Auth::guard('panel')->attempt(
            ['email' => $creds['email'], 'password' => $creds['password'], 'is_active' => true],
            $request->boolean('remember')
        )) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', "Email yoki parol noto'g'ri, yoki akkaunt bloklangan.");
        }

        $admin = Auth::guard('panel')->user();
        $admin->update([
            'last_login_at' => now(),
            'last_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();

        return redirect()->intended(route('boshqaruv.dashboard'));
    }

    public function logout(Request $request): \Illuminate\Http\RedirectResponse
    {
        Auth::guard('panel')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('boshqaruv.login')
            ->with('success', "Tizimdan chiqildi.");
    }

    public function dashboard(): Response
    {
        return Inertia::render('Dashboard', [
            'metrics' => [
                'orders' => $this->tableCount('solds'),
                'users' => $this->tableCount('users'),
                'books' => $this->tableCount('books'),
                'sellers' => $this->tableCount('sellers'),
                'couriers' => $this->tableCount('couriers'),
                'tickets' => $this->tableCount('bot_tickets'),
            ],
        ]);
    }

    public function live(): Response
    {
        return Inertia::render('LiveDashboard', [
            'metrics' => [
                'orders' => $this->tableCount('solds'),
                'users' => $this->tableCount('users'),
                'books' => $this->tableCount('books'),
                'sellers' => $this->tableCount('sellers'),
            ],
        ]);
    }

    public function page(string $component): Response
    {
        return Inertia::render($component, [
            'legacy' => [
                'a122' => $this->legacyUrl($component),
            ],
        ]);
    }

    private function tableCount(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    private function legacyUrl(string $component): ?string
    {
        return [
            'Products' => route('admin.books.index'),
            'Books' => route('admin.books.index'),
            'BookCategories' => route('admin.book-categories.index'),
            'Stationeries' => route('admin.stationery.index'),
            'stationery-categories' => route('admin.stationery-categories.index'),
            'Authors' => route('admin.authors.index'),
            'Publishers' => route('admin.publishers.index'),
            'Parser' => route('admin.parsers.index'),
            'Users' => route('admin.users.index'),
            'Orders' => route('admin.orders.index'),
            'SellerOrders' => request()->is('boshqaruv/sellers*')
                ? route('admin.sellers.index')
                : route('admin.seller-orders.index'),
            'CourierOrders' => request()->is('boshqaruv/couriers*')
                ? route('admin.couriers.index')
                : route('admin.courier-orders.index'),
            'Hubs' => route('admin.hubs.index'),
            'Transaksiyalar' => route('admin.transactions.index'),
            'LogistikaPage' => route('admin.logistics.index'),
            'Reklamalar' => route('admin.ads.index'),
            'Promokodlar' => route('admin.promocodes.index'),
            'Blogerlar' => route('admin.bloggers.index'),
            'GiftSertifikatlar' => route('admin.gift-certificates.index'),
            'MarketNewsPage' => route('admin.news.index'),
            'ReelsPage' => route('admin.reels.index'),
            'BookClub' => route('admin.book-club.index'),
            'Tickets' => route('admin.support.index'),
            'Shikoyatlar' => route('admin.complaints.index'),
            'ChatKuzatuv' => route('admin.chats.index'),
            'PushNotifications' => route('admin.push.index'),
            'Vakansiyalar' => route('admin.jobs.index'),
            'KaryeraArizalari' => route('admin.job-applications.index'),
            'Adminlar' => route('admin.admins.index'),
            'MysteryBoxPage' => route('admin.mystery-box.index'),
            'Sovgalar' => route('admin.gifts.index'),
            'Siyosatlar' => route('admin.policies.index'),
            'ApiClients' => route('admin.api-clients.index'),
            'SearchHistory' => route('admin.search-history.index'),
            'Settings' => route('admin.settings.index'),
        ][$component] ?? null;
    }
}
