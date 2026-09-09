<?php

declare(strict_types=1);

namespace App\Http\Requests\QaReply;

use App\Models\QaReply;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 受講生・コーチによるQ&A掲示板への回答投稿リクエスト。
 * 受講中/担当資格かどうかの業務判定はAction側(StoreAction)で行う。
 */
class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $thread = $this->route('thread');

        return $this->user()?->can('create', [QaReply::class, $thread]) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body' => '回答本文',
        ];
    }
}
