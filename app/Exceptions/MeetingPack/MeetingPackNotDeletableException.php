<?php

declare(strict_types=1);

namespace App\Exceptions\MeetingPack;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * 削除条件を満たさない面談パックを削除しようとした際の例外（HTTP 409）。
 * `MeetingPack\DestroyAction` が「公開中以外（下書き / アーカイブ）なら削除可」のドメインルールから throw する。
 * 過去の購入履歴の整合性を守るため、公開中の面談パック（購入履歴が発生しうる状態）は削除を禁止する。
 * 参考: App\Exceptions\Certification\CertificationNotDeletableException（ただし削除条件は逆: 資格は下書きのみ、面談パックは公開中のみ不可）
 */
final class MeetingPackNotDeletableException extends ConflictHttpException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('公開中の面談パックは削除できません。', $previous);
    }
}
