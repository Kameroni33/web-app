<?php

namespace App\Models;

use App\Casts\Base64Image;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A profile's photo. $image is the raw bytes; the column holds base64.
 */
#[Fillable(['mime_type', 'image'])]
class ProfileAvatar extends Model
{
    protected $primaryKey = 'profile_id';

    public $incrementing = false;

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'image' => Base64Image::class,
        ];
    }
}
