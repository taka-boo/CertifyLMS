<?php

declare(strict_types=1);

namespace App\Http\Requests\QaThread;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 投稿者本人によるQ&A掲示板スレッドの編集リクエスト。
 * 資格(certification_id)は変更不可のため、title・bodyのみ受け付ける。
 */
class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('thread')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'body' => '本文',
        ];
    }
}
