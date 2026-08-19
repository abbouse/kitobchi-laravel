<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'role',
        'permissions',
        'is_active',
        'is_read_only',
        'last_ip',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'permissions'   => 'array',
        'is_active'     => 'boolean',
        'is_read_only'  => 'boolean',
        'last_login_at' => 'datetime',
        'password'      => 'hashed',
    ];

    /**
     * Boshqaruv panelidagi har bir funksional modul uchun ruxsat kaliti.
     * `PanelPermission` middleware route'larda aynan shu kalitlarni tekshiradi
     * (`panel.permission:catalog`, `panel.permission:finance` va h.k.), va
     * frontend (Layout.tsx, Adminlar.tsx) sidebar/panel ko'rinishini shu
     * ro'yxatga qarab boshqaradi. Yangi bo'lim qo'shilganda shu yerga ham
     * qo'shish kerak — aks holda u hech kimga ko'rinmay qoladi.
     *
     * `superOnly` => true bo'lgan modullar — permissions[] massivida bo'lsa
     * ham, faqat superadmin uchun amalda bo'ladi (himoya: storePanelAdmin/
     * updatePanelAdmin bunday kalitlarni superadmin bo'lmagan so'rovlardan
     * har doim tozalaydi — Adminlar.tsx orqali ham, to'g'ridan-to'g'ri API
     * so'rovi orqali ham chetlab o'tib bo'lmaydi).
     */
    public const MODULES = [
        'catalog'        => ['label' => 'Katalog (kitob, kanselyariya, muallif, nashriyot)', 'superOnly' => false],
        'orders'         => ['label' => 'Buyurtmalar', 'superOnly' => false],
        'users'          => ['label' => 'Foydalanuvchilar', 'superOnly' => false],
        'split'          => ['label' => "Split nazorati (bo'lib to'lash)", 'superOnly' => false],
        'search-history' => ['label' => 'Qidiruv tarixi', 'superOnly' => false],
        'sellers'        => ['label' => 'Sotuvchilar va seller buyurtmalari', 'superOnly' => false],
        'couriers'       => ['label' => 'Kuryerlar va kuryer buyurtmalari', 'superOnly' => false],
        'hubs'           => ['label' => 'Hub fulfillment va hub arizalari', 'superOnly' => false],
        'finance'        => ['label' => 'Tranzaksiya, fiskalizatsiya, komissiya audit, chiqim', 'superOnly' => false],
        'audit-logs'     => ['label' => 'Audit log', 'superOnly' => false],
        'seller-ai'      => ['label' => 'Seller AI audit', 'superOnly' => false],
        'logistika'      => ['label' => 'Logistika (yetkazib berish zonalari)', 'superOnly' => false],
        'marketing'      => ['label' => "Marketing (reklama, promokod, bloger, sertifikat, yangilik, to'plam, reels)", 'superOnly' => false],
        'book-club'      => ['label' => 'Book Club moderatsiyasi', 'superOnly' => false],
        'support'        => ['label' => 'Support, shikoyat, chat kuzatuv', 'superOnly' => false],
        'push'           => ['label' => 'Push bildirishnomalar', 'superOnly' => false],
        'hr'             => ['label' => 'Vakansiya va karyera arizalari', 'superOnly' => false],
        'premium'        => ["label" => "Mystery Box va sovg'alar", 'superOnly' => false],
        'settings'       => ['label' => 'Sozlamalar, siyosatlar, API mijozlar', 'superOnly' => false],
        'admins'         => ['label' => 'Adminlarni boshqarish', 'superOnly' => true],
    ];

    /**
     * Rol bo'yicha katta-marketpleys darajasidagi tayyor shablonlar. Bular
     * faqat "boshlang'ich taklif" — Adminlar sahifasida checkbox orqali
     * har doim qo'lda o'zgartirish mumkin (natija baribir admin.permissions
     * ustunida saqlanadi, rol nomi shunchaki hisobot/UI uchun yorliq).
     */
    public const ROLE_PRESETS_BASE = [
        'superadmin' => ['label' => 'Super Admin',              'permissions' => []], // hamma narsaga kirish — permissions tekshirilmaydi
        'operations' => ['label' => 'Operatsion menejer',       'permissions' => ['orders', 'users', 'sellers', 'couriers', 'hubs', 'logistika', 'search-history']],
        'finance'    => ['label' => 'Moliya menejeri',          'permissions' => ['finance', 'split']],
        'marketing'  => ['label' => 'Marketing menejeri',       'permissions' => ['marketing', 'push', 'book-club', 'premium']],
        'catalog'    => ['label' => 'Katalog/kontent menejeri', 'permissions' => ['catalog', 'book-club']],
        'support'    => ['label' => 'Mijozlarga xizmat',        'permissions' => ['support']],
        'hr'         => ['label' => 'HR menejeri',              'permissions' => ['hr']],
        'moderator'  => ['label' => 'Kontent moderatori',       'permissions' => ['book-club', 'support']],
        'auditor'    => ['label' => "Auditor (faqat ko'rish)",  'permissions' => [], 'readOnly' => true], // permissions — barcha modullar, quyida to'ldiriladi
        'admin'      => ['label' => "Umumiy admin (qo'lda sozlanadi)", 'permissions' => []],
    ];

    /**
     * ROLE_PRESETS_BASE'ni to'liq holatda qaytaradi — `array_keys(MODULES)`
     * kabi funksiya chaqiruvlarini class constant ichida ishlatib bo'lmaydi
     * (PHP buni ruxsat bermaydi), shuning uchun 'auditor' uchun barcha modul
     * kalitlari shu yerda, runtime'da to'ldiriladi.
     */
    public static function rolePresets(): array
    {
        $presets = self::ROLE_PRESETS_BASE;
        $presets['auditor']['permissions'] = array_keys(self::MODULES);

        return $presets;
    }

    // ── Role helpers ─────────────────────────────────
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }

    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    /**
     * Permission tekshirish
     * superadmin — hamma narsaga ruxsat
     * boshqa rollar — permissions[] array ga qarab
     *
     * ESLATMA: Laravel'ning can() metodi bilan to'qnashuv bo'lgani uchun
     *          hasPermission() deb nomlandi.
     *          Middleware ichida: $admin->hasPermission('books')
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) return true;

        // 'superOnly' modullarga (masalan 'admins') faqat superadmin kira oladi,
        // permissions massivida bo'lib qolgan bo'lsa ham (eski/tahrirlangan
        // ma'lumot ehtimoliga qarshi qo'shimcha himoya qatlami).
        if ((self::MODULES[$permission]['superOnly'] ?? false) === true) {
            return false;
        }

        return in_array($permission, $this->permissions ?? []);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLE_PRESETS_BASE[$this->role]['label'] ?? ($this->role ?: 'Administrator');
    }

    public function getRoleColorAttribute(): string
    {
        return match($this->role) {
            'superadmin' => 'danger',
            'auditor'    => 'muted',
            'moderator'  => 'warning',
            'admin'      => 'accent',
            default      => 'info',
        };
    }

    public function hubStaffRoles()
    {
        return $this->morphMany(HubStaff::class, 'staffable');
    }
}
