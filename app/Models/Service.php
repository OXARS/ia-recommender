<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    protected $fillable = [
        'provider_id','categoria','titulo','descripcion','precio_desde','activo'
    ];

    public function provider(): BelongsTo {
        return $this->belongsTo(Provider::class);
    }
}
