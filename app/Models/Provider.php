<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    protected $fillable = ['nombre','barrio','lat','lon','rating_promedio'];

    public function services(): HasMany {
        return $this->hasMany(Service::class);
    }
}
