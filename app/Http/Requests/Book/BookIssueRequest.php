<?php

namespace App\Http\Requests\Book;

use App\Helpers\Qs;
use Illuminate\Foundation\Http\FormRequest;

class BookIssueRequest extends FormRequest
{
    public function authorize()
    {
        return Qs::userIsTeamLibrary();
    }

    public function rules()
    {
        return [
            'book_id' => 'required|exists:books,id',
            'user_id' => 'required|exists:users,id',
            'issue_date' => 'nullable|string',
            'issue_date_bs' => 'nullable|string',
            'due_date' => 'nullable|string',
            'due_date_bs' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
            'request_id' => 'nullable|exists:book_requests,id',
        ];
    }
}
