<?php

declare(strict_types=1);

namespace App\Http\Requests\MeetingPack;

use App\Models\MeetingPack;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 面談パック新規作成リクエスト。admin が SKU 名・説明・面談回数・価格・Stripe Price ID・並び順を入力する。
 *
 * バリデーション範囲の根拠:
 * - name: DB カラム定義 varchar(100)（migration: create_meeting_packs_table）
 * - description: 作成 / 編集画面（Blade）の hint「任意、最大 2000 文字」
 * - meeting_count: 作成 / 編集画面（Blade）の hint「1 〜 100 の整数」
 * - price: 作成 / 編集画面（Blade）の hint「0 〜 1,000,000 の整数」
 * - stripe_price_id: 要件シート・Blade に文字数の明記なし。DB カラム定義 varchar(255) に合わせて仮決め
 *   （PM 未確認、コミットメッセージに仮決め項目として明記）
 * - sort_order: 要件シート・Blade に範囲の明記なし。0〜9999 で仮決め
 *   （PM 未確認、コミットメッセージに仮決め項目として明記）
 */
class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MeetingPack::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'meeting_count' => ['required', 'integer', 'min:1', 'max:100'],
            'price' => ['required', 'integer', 'min:0', 'max:1000000'],
            'stripe_price_id' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'SKU 名',
            'description' => '説明',
            'meeting_count' => '面談回数',
            'price' => '価格',
            'stripe_price_id' => 'Stripe Price ID',
            'sort_order' => '並び順',
        ];
    }
}
