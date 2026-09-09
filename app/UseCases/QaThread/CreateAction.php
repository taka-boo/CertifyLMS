<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Models\Certification;
use Illuminate\Database\Eloquent\Collection;

/** 質問掲示板の新規投稿フォームで使う、選択可能な資格一覧を取得するAction */
final class CreateAction
{
    /**
     * @return Collection<int, Certification>
     */
    public function __invoke(): Collection
    {
        return Certification::query()->published()->orderBy('name')->get();
    }
}
