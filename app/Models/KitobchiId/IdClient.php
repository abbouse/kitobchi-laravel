<?php

namespace App\Models\KitobchiId;

use Illuminate\Database\Eloquent\Model;

/** Kitobchi ID'ga ulangan ilova (relying party). */
class IdClient extends Model
{
    protected $table = 'id_clients';

    protected $guarded = ['id'];

    protected $hidden = ['client_secret_hash'];

    protected $casts = [
        'first_party' => 'boolean',
        'redirect_uris' => 'array',
        'post_logout_redirect_uris' => 'array',
        'allowed_scopes' => 'array',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPublic(): bool
    {
        return $this->type === 'public';
    }

    /** Qat'iy (exact) solishtirish — wildcard va prefiks yo'q. */
    public function hasRedirectUri(string $uri): bool
    {
        return in_array($uri, (array) $this->redirect_uris, true);
    }

    public function hasPostLogoutRedirectUri(string $uri): bool
    {
        return in_array($uri, (array) ($this->post_logout_redirect_uris ?? []), true);
    }

    public function verifySecret(?string $secret): bool
    {
        if ($this->client_secret_hash === null || $secret === null || $secret === '') {
            return false;
        }

        return password_verify($secret, $this->client_secret_hash);
    }

    /** Pairwise sub uchun sektor: alohida berilmagan bo'lsa — birinchi redirect URI hosti. */
    public function sector(): string
    {
        if ($this->sector_identifier) {
            return strtolower($this->sector_identifier);
        }
        $first = (array) $this->redirect_uris;

        return strtolower((string) (parse_url((string) ($first[0] ?? ''), PHP_URL_HOST) ?: $this->client_id));
    }
}
