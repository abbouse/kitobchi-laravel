<?php

namespace App\Services\KitobchiId;

use RuntimeException;

/**
 * Kitobchi ID imzolash kalitlari (RS256).
 *
 * - Har bir kalit `{kid}.pem` (maxfiy, 0600) ko'rinishida saqlanadi.
 * - `active` fayli joriy imzolash kalitining kid'ini saqlaydi.
 * - JWKS'da katalogdagi BARCHA kalitlarning ochiq qismi e'lon qilinadi —
 *   rotatsiyadan keyin eski tokenlar muddati tugaguncha tekshirilaveradi.
 *   Eski kalit faqat `kitobchi-id:keys --prune` bilan arxivga olinadi.
 */
class KeyStore
{
    public function __construct(private ?string $path = null)
    {
        $this->path = rtrim($path ?? (string) config('kitobchi_id.keys_path'), '/');
    }

    public function path(): string
    {
        return $this->path;
    }

    /** Yangi kalit yaratib, uni joriy (active) qiladi. */
    public function generate(): string
    {
        if (! is_dir($this->path) && ! mkdir($this->path, 0700, true) && ! is_dir($this->path)) {
            throw new RuntimeException('Kalitlar katalogini yaratib bo\'lmadi: '.$this->path);
        }

        $res = openssl_pkey_new([
            'private_key_bits' => 3072,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        if ($res === false) {
            throw new RuntimeException('RSA kalit yaratilmadi: '.openssl_error_string());
        }
        openssl_pkey_export($res, $pem);
        $kid = $this->kidFor(openssl_pkey_get_details($res));

        $file = $this->path.'/'.$kid.'.pem';
        $old = umask(0077);
        try {
            file_put_contents($file, $pem, LOCK_EX);
            chmod($file, 0600);
            file_put_contents($this->path.'/active', $kid, LOCK_EX);
        } finally {
            umask($old);
        }

        return $kid;
    }

    public function hasActive(): bool
    {
        return is_file($this->path.'/active');
    }

    /** @return array{kid:string, pem:string} */
    public function active(): array
    {
        $kid = trim((string) @file_get_contents($this->path.'/active'));
        if ($kid === '' || ! preg_match('/^[A-Za-z0-9_-]{8,64}$/', $kid)) {
            throw new RuntimeException('Kitobchi ID kaliti yo\'q. `php artisan kitobchi-id:keys` ni ishga tushiring.');
        }
        $pem = @file_get_contents($this->path.'/'.$kid.'.pem');
        if (! $pem) {
            throw new RuntimeException('Joriy kalit fayli topilmadi: '.$kid);
        }

        return ['kid' => $kid, 'pem' => $pem];
    }

    /** kid => ochiq kalit PEM */
    public function publicKeys(): array
    {
        $out = [];
        foreach (glob($this->path.'/*.pem') ?: [] as $file) {
            $kid = basename($file, '.pem');
            $key = openssl_pkey_get_private((string) file_get_contents($file));
            if ($key === false) {
                continue;
            }
            $out[$kid] = openssl_pkey_get_details($key);
        }

        return $out;
    }

    /** RFC 7517 JWK Set (faqat ochiq qismlar). */
    public function jwks(): array
    {
        $keys = [];
        foreach ($this->publicKeys() as $kid => $details) {
            $keys[] = [
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'kid' => (string) $kid,
                'n' => self::b64url($details['rsa']['n']),
                'e' => self::b64url($details['rsa']['e']),
            ];
        }

        return ['keys' => $keys];
    }

    /** kid => PEM ochiq kalit (tekshirish uchun) */
    public function verificationKeys(): array
    {
        return array_map(fn ($d) => $d['key'], $this->publicKeys());
    }

    /** Joriy bo'lmagan kalitlarni `archive/` ga ko'chiradi. */
    public function prune(): array
    {
        $active = $this->active()['kid'];
        $archive = $this->path.'/archive';
        if (! is_dir($archive)) {
            mkdir($archive, 0700, true);
        }
        $moved = [];
        foreach (glob($this->path.'/*.pem') ?: [] as $file) {
            $kid = basename($file, '.pem');
            if ($kid !== $active) {
                rename($file, $archive.'/'.$kid.'.pem');
                $moved[] = $kid;
            }
        }

        return $moved;
    }

    private function kidFor(array $details): string
    {
        // RFC 7638 JWK thumbprint
        $json = '{"e":"'.self::b64url($details['rsa']['e']).'","kty":"RSA","n":"'.self::b64url($details['rsa']['n']).'"}';

        return substr(self::b64url(hash('sha256', $json, true)), 0, 22);
    }

    public static function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}
