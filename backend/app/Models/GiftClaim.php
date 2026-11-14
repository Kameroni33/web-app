<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A profile saying "I'm getting this" for someone else's item.
 */
class GiftClaim extends Model
{
    protected $fillable = ['profile_id'];

    /**
     * @return BelongsTo<GiftItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(GiftItem::class, 'gift_item_id');
    }

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
