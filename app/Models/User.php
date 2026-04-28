<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'lastname',
        'position',
        'bio',
        'role_emoji',
        'role_title',
        'role_place',
        'role_preset_id',
        'email',
        'mainAddressID',
        'telegram_id',
        'telegram_username',
        'telegram_photo',
        'telegram_connected_at',
        'phone_number',
        'avatar',
        'status',
        'verifyCode',
        'remember_token',
        'real_balance',
        'cashback',
        'fcm_token',
        'ai_limit',
        'last_seen_at',
        'blocked_until',
        'blocked_at',
        'block_reason',
        'blocked_by_admin_id',
        'total_seconds_spend',
        'isVerified',
        'isSupport',
        'is_premium',
        'premium_until',
        'firstEdit',
        'locale'
    ];

    protected $hidden = [
        'password',
        // 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'isVerified' => 'boolean',
        'isSupport' => 'boolean',
        'firstEdit' => 'boolean',
        'telegram_connected_at' => 'datetime',
        'blocked_until' => 'datetime',
        'blocked_at' => 'datetime',
    ];

    public function isBlocked(): bool
    {
        if ($this->status !== 'blocked') {
            return false;
        }

        if ($this->blocked_until === null) {
            return true;
        }

        return $this->blocked_until->isFuture();
    }

    public function activeBlockLabel(): ?string
    {
        if (!$this->isBlocked()) {
            return null;
        }

        return $this->blocked_until?->format('d.m.Y H:i') ?? 'Abadiy';
    }

    public function isModerator(): bool
    {
        return mb_strtolower(trim((string) $this->position)) === 'moderator';
    }

    public function isAdministrator(): bool
    {
        $position = mb_strtolower(trim((string) $this->position));

        return in_array($position, ['administrator', 'admin'], true);
    }

    public function canModerateCommunity(): bool
    {
        return $this->isModerator() || $this->isAdministrator();
    }
    
    protected static function booted()
{
    static::creating(function ($user) {
        if (!$user->name || !$user->lastname) {
            $identity = self::generateRandomName();
            $user->name = $identity['name'];
            $user->lastname = $identity['lastname'];
        }
    });
}

    /**
     * Shaxsiy kitoblar (My Books bo'limi) — faqat kitoblar
     */
    public function myBooks()
    {
        return $this->hasMany(MyBooks::class);
    }

    /**
     * Foydalanuvchining shaxsiy kitoblari (kitoblar ro'yxati)
     */
    public function books()
    {
        return $this->hasManyThrough(
            Books::class,
            MyBooks::class,
            'user_id',     // MyBooks jadvalidagi foreign key
            'id',          // Books jadvalidagi primary key
            'id',          // User jadvalidagi primary key
            'book_id'      // MyBooks jadvalidagi book_id
        );
    }

    /**
     * Savatdagi elementlar — umumiy (kitob, stationery, variantlar bilan)
     */
    public function cart()
    {
        return $this->hasMany(MyCart::class);
    }

    /**
     * Qiziqishlari (interests)
     */
    public function interests()
    {
        return $this->hasMany(Interest::class);
    }

    /**
     * Tanlovlarda ishtiroki
     */
    public function contests()
    {
        return $this->hasMany(SellerContestParticipant::class, 'participant_id');
    }

    /**
     * Asosiy yetkazib berish manzili
     */
    public function location()
    {
        return $this->belongsTo(Locations::class, 'mainAddressID');
    }

    /**
     * Accessor: savatdagi umumiy mahsulotlar soni
     */
    public function getCartCountAttribute()
    {
        return $this->cart()->sum('count_item');
    }

    /**
     * Accessor: savatdagi umumiy narx (chegirma bilan)
     */
    public function getCartTotalPriceAttribute()
    {
        return $this->cart->sum(function ($item) {
            $price = $item->product?->discountPrice ?? $item->product?->price ?? 0;
            if ($item->product_type === 'stationery') {
                $price = $item->product?->discount_price ?? $item->product?->price ?? 0;
            }
            return $price * $item->count_item;
        });
    }
    public function getFullNameAttribute(): string
{
    $name = trim($this->name ?? '');
    $lastname = trim($this->lastname ?? '');

    $full = trim($name . ' ' . $lastname);

    return $full ?: 'unknown';
}
public function followings()
{
    return $this->belongsToMany(User::class, 'user_follows', 'follower_id', 'following_id');
}

// Menga obuna bo'lgan foydalanuvchilar (Followers)
public function followers()
{
    return $this->belongsToMany(User::class, 'user_follows', 'following_id', 'follower_id');
}

// Obuna bo'linganmi yoki yo'qligini tekshirish
public function isFollowing($userId)
{
    return $this->followings()->where('following_id', $userId)->exists();
}

public static function generateRandomName() {
    $adjectives = [
        // Ranglar va jilo
        "Qizil", "Moviy", "Yashil", "Sariq", "Pushti", "Binafsha", "Jigarrang", "Kumush", "Oltin", "Zumrad", 
        "Feruza", "Kulrang", "Qora", "Oq", "Lojuvard", "To'q sariq", "Havorang", "Siyohrang", "Zarhal", 
        "Shaffof", "Kamalakrang", "Tillarang", "Yoqutrang", "Sadafrang", "Nilufar", "Bronza", "Musaffo",
        
        // Xarakter va holat
        "Aqlli", "Shiddatli", "Katta", "Kichik", "Ayyor", "Botir", "Chaqqon", "Jasur", "Mag'rur", "Dono", 
        "Erka", "Kamtar", "Qaysar", "Quvnoq", "Mahzun", "Kuchli", "Epchil", "Sekin", "Shovqinli", "Jimjit", 
        "Mehribon", "Vafodor", "Qo'rqmas", "Sirdosh", "G'olib", "Chidamli", "Zukko", "Chiroyli", "Shijoatli", 
        "Vahshiy", "Sokin", "O'lmas", "Muqaddas", "Botiniy", "Zahiriy", "Noyob", "Qadimiy", "Zamonaviy", 
        "Yengilmas", "O'tkir", "Oliyjanob", "Ma'sum", "Zulmatli", "Nurafshon", "Jasoratli", "Sadoqatli",
        
        // Tabiat va elementlar
        "Olovli", "Muzli", "Osmondagi", "Yovvoyi", "Sehrli", "Nurli", "Zulmatdagi", "Kosmik", "Dengizdagi", 
        "Tog'li", "Sahrodagi", "Uchar", "Sirli", "Yashirin", "Abadiy", "Afsonaviy", "Yaltiroq", "Samoviy", 
        "Pinhona", "Tumanli", "Bo'ronli", "Silliq", "Tikanli", "Gulli", "Sahroiy", "Ummoniy", "Chaqnoq"
    ];

    $nouns = [
        // Hayvonlar (Sutemizuvchilar)
        "Mushuk", "Buqa", "Arslon", "Ayiq", "Bo'ri", "Tulki", "Yo'lbars", "Kiyik", "Qoplon", "Fil", 
        "Maymun", "Quyon", "Ot", "Tulpor", "Kenguru", "Panda", "Begemot", "Jirafa", "Sirtlon", "Leopard", 
        "Gepard", "Kuzun", "Sug'ur", "Ohu", "Qashqir", "Qunduz", "Yenot", "Suvsar", "Oq ayiq", "Siyovush",
        
        // Qushlar
        "Burgut", "Feniks", "Lochin", "To'ti", "Ukki", "Bulbul", "Laylak", "Tuyaqush", "Pingvin", 
        "Flamingo", "Qaldirg'och", "Zag'izg'on", "Turna", "Humo", "Simurg'", "Boyo'g'li", "Qarchig'ay", 
        "Tovus", "Qaqnus", "Kabutar",
        
        // Suv olami va boshqalar
        "Kit", "Delfin", "Akula", "Skat", "Meduza", "Baqa", "Qisqichbaqa", "Toshbaqa", "Baliq", "Timsoh", 
        "Ilon", "Kobra", "Ari", "Kapalak", "Qo'ng'iz", "Chumoli", "Ninachi", "O'rgimchak", "Chayon",
        
        // Kitob va ijod (Kitobchi uchun)
        "Sahifa", "Qalam", "Siyoh", "Daftar", "Kitob", "Varal", "Muqova", "Doston", "G'azal", "Ertak", 
        "Hikoya", "Satr", "Bayt", "Qog'oz", "Xattot", "Rishoda", "Mitti", "Asar", "Hikmat",
        
        // Koinot va tabiat elementlari
        "Yulduz", "Sayyora", "Quyosh", "Oy", "Galaktika", "Kometa", "Tumanlik", "Zuhal", "Mushtariy", 
        "Mars", "Venera", "Meteor", "Nur", "Shabada", "Ummon", "Daryo", "Tog'", "Chaqmoq", "Yomg'ir", 
        "Bulut", "Olov", "Muz", "Okean", "Sahro", "Voha", "Zamin", "Ufq", "Zarra", "Shula",
        
        // Qimmatbaho buyumlar
        "Olmos", "Gavhar", "Marvarid", "Yoqut", "Firuz", "Billur", "Yantar", "Kristall", "Tilla", "Kumush",
        
        // Afsonaviy
        "Ajdarho", "Pegas", "Unikorn", "Grifon", "Sfinks", "Kentavr", "Gidra", "Kraken", "Yakkashox"
    ];

    $randomAdjective = $adjectives[array_rand($adjectives)];
    $randomNoun = $nouns[array_rand($nouns)];

    return [
        "name" => $randomAdjective,
        "lastname" => $randomNoun
    ];
}
public function isPremium(): bool
{
    // premium_until ustuniga qarab tekshiramiz
    return $this->is_premium && ($this->premium_until === null || $this->premium_until->isFuture());
}

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest();
    }
    public function cards()
{
    return $this->hasMany(UserCard::class);
}

public function activeCard()
{
    return $this->hasOne(UserCard::class)->where('is_verified', true)->latest();
}
public function devices()
{
    return $this->hasMany(ConnectedDevice::class, 'user_id', 'id')->where('user_type', 'user');
}
}
