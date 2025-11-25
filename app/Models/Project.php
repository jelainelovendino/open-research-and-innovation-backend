<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'upload_date',
        'user_id',
        'category_id',
        'thumbnail',
    ];

    protected $appends = ['thumbnail_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the full URL for the project thumbnail.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail && File::exists(public_path($this->thumbnail))) {
            return asset($this->thumbnail);
        }
        return null;
    }
}
