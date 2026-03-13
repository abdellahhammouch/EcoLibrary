<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookCopy extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_UNAVAILABLE = 'unavailable';
    public const PHYSICAL_GOOD = 'good';
    public const PHYSICAL_DEGRADED = 'degraded';

    protected $fillable = [
        'book_id',
        'reference_code',
        'status',
        'physical_state',
        'notes',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
