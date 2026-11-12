<?php

namespace App\Models;

use Database\Factories\GiftWishlistFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A link to a profile's wish list on another site (Amazon, Steam…).
 */
#[Fillable(['label', 'url'])]
class GiftWishlist extends Model
{
    /** @use HasFactory<GiftWishlistFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
