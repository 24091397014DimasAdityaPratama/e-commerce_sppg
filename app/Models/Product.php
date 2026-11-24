<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    public function scopeAvailableInMarketplace(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereHas('supplier', fn (Builder $supplier) => $supplier
                ->where('status', 'active')
                ->whereHas('supplierProfile', fn (Builder $profile) => $profile
                    ->where('verification_status', 'approved')));
    }

    public function isAvailableInMarketplace(): bool
    {
        return $this->is_active
            && $this->supplier?->status === 'active'
            && $this->supplier?->supplierProfile?->verification_status === 'approved';
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'product';
        $slug = $baseSlug;
        $suffix = 1;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }

    protected $fillable = [
        'category_id',
        'supplier_id',
        'name',
        'slug',
        'description',
        'unit',
        'unit_quantity',
        'price',
        'stock',
        'low_stock_threshold',
        'image_url',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'unit_quantity' => 'decimal:3',
        'price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function visibleReviews()
    {
        return $this->reviews()->where('is_visible', true);
    }
}
