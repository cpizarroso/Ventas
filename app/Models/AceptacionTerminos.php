<?php

namespace App\Models;

use Database\Factories\AceptacionTerminosFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'version', 'aceptado_en', 'ip_address', 'user_agent'])]
class AceptacionTerminos extends Model
{
    /** @use HasFactory<AceptacionTerminosFactory> */
    use HasFactory;

    protected $table = 'aceptaciones_terminos';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aceptado_en' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
