<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrLot extends Model
{
    protected $table = 'qr_lots';

    protected $fillable = ['creat_per', 'quantitat'];

    public function etiquetes(): HasMany
    {
        return $this->hasMany(QrEtiqueta::class, 'lot_id');
    }
}
