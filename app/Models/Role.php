<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_INSTRUCTOR = 'instructor';

    public const ROLE_STUDENT = 'student';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'label',
    ];

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
