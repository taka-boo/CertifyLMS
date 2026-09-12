<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** スレッドを表すモデル */
class QaThread extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'certification_id',
        'title',
        'body',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'status' => QaThreadStatus::class,
        'resolved_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Certification, $this> */
    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }

    /** @return HasMany<QaReply, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(QaReply::class, 'qa_thread_id');
    }

    /** 資格でスレッドを絞り込む */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereHas(
            'certification',
            fn (Builder $q) => $q->published(),
        );
    }

    /** 解決済/未解決で絞り込む。$statusが'resolved'/'unresolved'以外(空文字含む)なら絞り込みなし */
    public function scopeStatusFilter(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'resolved' => $query->where('status', QaThreadStatus::Resolved->value),
            'unresolved' => $query->where('status', QaThreadStatus::Open->value),
            default => $query,
        };
    }

    /** タイトル・本文・回答本文のキーワードで検索 */
    public function scopeKeyword(Builder $query, ?string $keyword): Builder
    {
        if ($keyword === null || $keyword === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($keyword) {
            $q->where('title', 'LIKE', '%'.$keyword.'%')
                ->orWhere('body', 'LIKE', '%'.$keyword.'%')
                ->orWhereHas('replies', function (Builder $replyQuery) use ($keyword) {
                    $replyQuery->where('body', 'LIKE', '%'.$keyword.'%');
                });
        });
    }

    /**
     * ロールに応じて閲覧可能なスレッドに絞り込む。
     * qa-board一覧・詳細で共通利用する(admin横断閲覧はこのscopeを使わない)。
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return match ($user->role) {

            /** student = 公開済資格のスレッドすべて */
            UserRole::Student => $query->published(),
            /** coach = 担当資格のスレッドのみ */
            UserRole::Coach => $query->whereHas(
                'certification',
                fn (Builder $q) => $q->published()->assignedTo($user),
            ),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
