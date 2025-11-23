<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\File;

class User extends Authenticatable
{
    /** @use HasFactory<\\Database\\Factories\\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'bio',
        'course',
        'school',
        'department',
        'profile_picture',
    ];

    /**
     * Attributes to append to the model's array / JSON form.
     * @var array<int,string>
     */
    protected $appends = ['profile_picture_url'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Return a full URL for the profile picture, or a sensible fallback.
     */
    public function getProfilePictureUrlAttribute(): ?string
    {
        // If a profile_picture value is set and the file exists in public/, return its asset URL
        if ($this->profile_picture && File::exists(public_path($this->profile_picture))) {
            return asset($this->profile_picture);
        }

        // Otherwise fall back to the first available default profile image in public/default/profiles
        $dir = public_path('default/profiles');
        if (File::exists($dir)) {
            $files = File::files($dir);
            if (!empty($files)) {
                $first = $files[0];
                return asset('default/profiles/' . $first->getFilename());
            }
        }

        // No image available
        return null;
    }
}
