<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentCounter extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'period',
        'current_number',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'current_number' => 'integer',
    ];
}
