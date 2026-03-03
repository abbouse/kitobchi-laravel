<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\CommissionSetting;

class Books extends Model
{
    use HasFactory;
    protected $table = 'books';
    protected $fillable = [
        'name',
        'author',
        'category_id',
        'description',
        'images',
        'price',
        'discountPrice',
        'count',
        'lang',
        'langType',
        'coverType',
        'year',
        'pages',
        'status',
        'seller_id',
        'is_hidden',
        'is_approved',
        'totalSales',
        'totalRevenue',
        'totalClients',
        'totalSalesWeek',
        'totalRevenueWeek',
        'totalClientsWeek',
        'vectorData'
    ];
    protected $casts = [
    'images' => 'json',
    'status' => 'boolean',
    'vectorData' => 'json',
    ];
    protected $appends = ['first_image'];
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategories::class);
    }
    public function cartItems()
    {
        return $this->hasMany(MyCart::class, 'book_id');
    }
    public function discounts()
{
    return $this->belongsToMany(Discounts::class, 'book_discount', 'book_id', 'discount_id');
}
    public function getFirstImageAttribute(): ?string
    {
        $images = $this->images;

        if (empty($images) || !is_array($images)) {
            return null;
        }

        return $images[0] ?? null;
    }
    public function getDiscountPercentAttribute()
{
    $discount = $this->discounts()->first();
    return $discount ? $discount->discount : 0;
}
public function tags()
    {
        return $this->belongsToMany(BookTag::class, 'book_tag_relations', 'book_id', 'tag_id');
    }
    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id')
        ->select('id', 'shop_name', 'lastname', 'firstname', 'phone_number', 'photo', 'isVerified');
    }
}