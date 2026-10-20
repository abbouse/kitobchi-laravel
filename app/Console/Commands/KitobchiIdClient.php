<?php

namespace App\Console\Commands;

use App\Models\KitobchiId\IdClient;
use App\Models\KitobchiId\IdRefreshToken;
use App\Services\KitobchiId\TokenService;
use Illuminate\Console\Command;

/**
 * Ilovalarni ro'yxatdan o'tkazish va boshqarish.
 *
 *   php artisan kitobchi-id:client create "Ilova nomi" --redirect=https://app.uz/callback --scopes="openid profile phone"
 *   php artisan kitobchi-id:client create "Mobil ilova" --public --redirect=uz.app.mobile:/oauth
 *   php artisan kitobchi-id:client list
 *   php artisan kitobchi-id:client secret <client_id>     — yangi secret (eskisi darhol bekor)
 *   php artisan kitobchi-id:client disable <client_id>    — o'chirish + barcha refresh tokenlarni bekor qilish
 *   php artisan kitobchi-id:client enable <client_id>
 */
class KitobchiIdClient extends Command
{
    protected $signature = 'kitobchi-id:client
        {action : create|list|secret|disable|enable|show}
        {target? : create uchun nom, boshqalari uchun client_id}
        {--redirect=* : Redirect URI (bir nechta bo\'lishi mumkin, aniq moslik)}
        {--post-logout=* : Chiqishdan keyingi redirect URI}
        {--scopes=openid profile : Ruxsat berilgan scope\'lar}
        {--public : Public client (SPA/mobil, secret yo\'q, PKCE majburiy)}
        {--first-party : Kitobchi\'ning o\'z ilovasi (rozilik so\'ralmaydi, sub = user id)}
        {--homepage= : Ilova sayti}
        {--logo= : Logo URL (https)}
        {--email= : Texnik aloqa e-pochtasi}
        {--seller= : Bog\'liq sotuvchi ID}
        {--sector= : Pairwise sub uchun sektor (domen)}';

    protected $description = 'Kitobchi ID ilovalarini (client) boshqarish';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'create' => $this->create(),
            'list' => $this->listClients(),
            'show' => $this->show(),
            'secret' => $this->rotateSecret(),
            'disable' => $this->setStatus('disabled'),
            'enable' => $this->setStatus('active'),
            default => $this->failWith('Noma\'lum amal'),
        };
    }

    private function create(): int
    {
        $name = trim((string) $this->argument('target'));
        if ($name === '' || mb_strlen($name) > 60) {
            return $this->failWith('Nom 1–60 belgi bo\'lishi kerak.');
        }

        $public = (bool) $this->option('public');
        $redirects = array_values(array_unique((array) $this->option('redirect')));
        if (! $redirects) {
            return $this->failWith('Kamida bitta --redirect kerak.');
        }
        foreach ($redirects as $uri) {
            if ($err = self::validateRedirect($uri, $public)) {
                return $this->failWith("$uri: $err");
            }
        }
        $postLogout = array_values(array_unique((array) $this->option('post-logout')));
        foreach ($postLogout as $uri) {
            if ($err = self::validateRedirect($uri, $public)) {
                return $this->failWith("$uri: $err");
            }
        }

        $scopes = TokenService::scopes((string) $this->option('scopes'));
        if (! in_array('openid', $scopes, true)) {
            $scopes[] = 'openid';
        }
        if ($unknown = array_diff($scopes, array_keys((array) config('kitobchi_id.scopes')))) {
            return $this->failWith('Noma\'lum scope: '.implode(' ', $unknown));
        }

        foreach (['homepage', 'logo'] as $opt) {
            $v = $this->option($opt);
            if ($v && ! preg_match('#^https://[^\s]+$#i', $v)) {
                return $this->failWith("--$opt https:// bilan boshlanishi kerak");
            }
        }

        $clientId = 'kid_'.bin2hex(random_bytes(12));
        $secret = $public ? null : 'kid_cs_'.TokenService::randomToken(32);

        IdClient::create([
            'client_id' => $clientId,
            'client_secret_hash' => $secret ? password_hash($secret, PASSWORD_DEFAULT) : null,
            'name' => $name,
            'type' => $public ? 'public' : 'confidential',
            'first_party' => (bool) $this->option('first-party'),
            'redirect_uris' => $redirects,
            'post_logout_redirect_uris' => $postLogout ?: null,
            'allowed_scopes' => array_values($scopes),
            'homepage_url' => $this->option('homepage'),
            'logo_url' => $this->option('logo'),
            'contact_email' => $this->option('email'),
            'seller_id' => $this->option('seller') ? (int) $this->option('seller') : null,
            'sector_identifier' => $this->option('sector'),
            'status' => 'active',
        ]);

        $this->info('Ilova yaratildi.');
        $this->line('client_id:     '.$clientId);
        if ($secret) {
            $this->line('client_secret: '.$secret);
            $this->warn('Secret faqat HOZIR ko\'rsatiladi va bazada faqat hash saqlanadi. Uni xavfsiz kanal orqali bering.');
        }

        return self::SUCCESS;
    }

    private function listClients(): int
    {
        $rows = IdClient::orderBy('id')->get()->map(fn (IdClient $c) => [
            $c->client_id, $c->name, $c->type, $c->first_party ? 'ha' : '', $c->status,
            implode(' ', (array) $c->allowed_scopes), implode("\n", (array) $c->redirect_uris),
        ]);
        $this->table(['client_id', 'Nom', 'Turi', '1st', 'Holat', 'Scope', 'Redirect'], $rows);

        return self::SUCCESS;
    }

    private function show(): int
    {
        $c = $this->client();
        if (! $c) {
            return self::FAILURE;
        }
        $this->line(json_encode($c->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $active = IdRefreshToken::where('client_pk', $c->id)->whereNull('revoked_at')->whereNull('used_at')
            ->where('expires_at', '>', now())->distinct('user_id')->count('user_id');
        $this->line('Faol foydalanuvchilar (refresh token bilan): '.$active);

        return self::SUCCESS;
    }

    private function rotateSecret(): int
    {
        $c = $this->client();
        if (! $c) {
            return self::FAILURE;
        }
        if ($c->isPublic()) {
            return $this->failWith('Public client\'da secret yo\'q.');
        }
        $secret = 'kid_cs_'.TokenService::randomToken(32);
        $c->client_secret_hash = password_hash($secret, PASSWORD_DEFAULT);
        $c->save();
        $this->line('Yangi client_secret: '.$secret);
        $this->warn('Eski secret darhol bekor qilindi.');

        return self::SUCCESS;
    }

    private function setStatus(string $status): int
    {
        $c = $this->client();
        if (! $c) {
            return self::FAILURE;
        }
        $c->status = $status;
        $c->save();
        if ($status === 'disabled') {
            $n = IdRefreshToken::where('client_pk', $c->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $this->line("Bekor qilingan refresh tokenlar: $n");
        }
        $this->info("{$c->client_id}: $status");

        return self::SUCCESS;
    }

    private function client(): ?IdClient
    {
        $c = IdClient::where('client_id', (string) $this->argument('target'))->first();
        if (! $c) {
            $this->error('Client topilmadi.');
        }

        return $c;
    }

    /** null — to'g'ri, aks holda xato matni */
    public static function validateRedirect(string $uri, bool $public): ?string
    {
        if (strlen($uri) > 2000 || preg_match('/\s/', $uri)) {
            return 'noto\'g\'ri URI';
        }
        if (str_contains($uri, '#')) {
            return 'fragment (#) bo\'lishi mumkin emas';
        }
        if (str_contains($uri, '*')) {
            return 'wildcard taqiqlangan';
        }
        $parts = parse_url($uri);
        if (! $parts || empty($parts['scheme'])) {
            return 'scheme yo\'q';
        }
        $scheme = strtolower($parts['scheme']);
        if ($scheme === 'https') {
            return empty($parts['host']) ? 'host yo\'q' : null;
        }
        if ($scheme === 'http') {
            // Faqat loopback (lokal ishlab chiqish / desktop ilovalar, RFC 8252 §7.3)
            return in_array($parts['host'] ?? '', ['127.0.0.1', '[::1]', 'localhost'], true) ? null : 'http faqat loopback uchun';
        }
        if (in_array($scheme, ['javascript', 'data', 'file', 'vbscript'], true)) {
            return 'xavfli scheme';
        }
        // Mobil ilova: reverse-DNS custom scheme (masalan uz.kitobchi.app:/oauth), faqat public client
        if (! $public) {
            return 'custom scheme faqat --public ilovalar uchun';
        }

        return str_contains($scheme, '.') ? null : 'custom scheme reverse-DNS ko\'rinishida bo\'lishi kerak (masalan uz.company.app)';
    }

    private function failWith(string $message): int
    {
        $this->error($message);

        return self::FAILURE;
    }
}
