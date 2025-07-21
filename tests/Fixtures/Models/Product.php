<?php

namespace Wink\ControllerGenerator\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'cost',
        'sku',
        'stock_quantity',
        'weight',
        'dimensions',
        'category_id',
        'brand_id',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'weight' => 'decimal:3',
        'dimensions' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'stock_quantity' => 'integer',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the brand that owns the product.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the reviews for the product.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the tags for the product.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    /**
     * Get the product images.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include featured products.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include products in stock.
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Get the average rating for the product.
     */
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * Check if the product is in stock.
     */
    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Reduce stock quantity.
     */
    public function reduceStock(int $quantity): void
    {
        $this->decrement('stock_quantity', $quantity);
    }

    /**
     * Increase stock quantity.
     */
    public function increaseStock(int $quantity): void
    {
        $this->increment('stock_quantity', $quantity);
    }
}