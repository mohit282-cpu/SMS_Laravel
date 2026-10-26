<?php

namespace App\Http\Requests\Book;

use App\Helpers\Qs;
use Illuminate\Foundation\Http\FormRequest;

class BookUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return Qs::userIsTeamLibrary();
    }

    public function rules()
    {
        $bookId = $this->route('book');
        if (is_object($bookId)) {
            $bookId = $bookId->id;
        }

        return [
            'name' => 'required|string|min:2|max:100',
            'isbn' => 'nullable|string|max:50|unique:books,isbn,' . $bookId,
            'author' => 'nullable|string|max:150',
            'publisher' => 'nullable|string|max:150',
            'category_id' => 'nullable|exists:book_categories,id',
            'my_class_id' => 'nullable|exists:my_classes,id',
            'edition' => 'nullable|string|max:50',
            'publication_year' => 'nullable|string|max:20',
            'language' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:100',
            'total_copies' => 'required|integer|min:1|max:10000',
            'description' => 'nullable|string|max:500',
            'url' => 'nullable|url|max:255',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $bookId = $this->route('book');
            if (is_object($bookId)) {
                $book = $bookId;
            } else {
                $book = \App\Models\Book::find($bookId);
            }

            if ($book && (int)$this->total_copies < (int)($book->issued_copies ?? 0)) {
                $validator->errors()->add('total_copies', __('Total copies cannot be less than currently issued copies (:count).', [
                    'count' => $book->issued_copies ?? 0,
                ]));
            }
        });
    }
}
