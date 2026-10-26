<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'books';

    protected $fillable = [
        'name',
        'isbn',
        'my_class_id',
        'category_id',
        'description',
        'author',
        'publisher',
        'edition',
        'publication_year',
        'language',
        'book_type',
        'url',
        'location',
        'cover_image',
        'total_copies',
        'issued_copies',
    ];

    protected $appends = [
        'available_copies',
    ];

    /**
     * Compute remaining copies in library.
     */
    public function getAvailableCopiesAttribute()
    {
        $total = (int) ($this->total_copies ?? 0);
        $issued = (int) ($this->issued_copies ?? 0);
        return max(0, $total - $issued);
    }

    public function category()
    {
        return $this->belongsTo(BookCategory::class, 'category_id');
    }

    public function my_class()
    {
        return $this->belongsTo(MyClass::class, 'my_class_id');
    }

    public function requests()
    {
        return $this->hasMany(BookRequest::class, 'book_id');
    }

    public function activeLoans()
    {
        return $this->hasMany(BookRequest::class, 'book_id')->where('status', 'issued');
    }
}
