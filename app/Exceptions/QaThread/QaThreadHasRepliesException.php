<?php

declare(strict_types=1);

namespace App\Exceptions\QaThread;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** 回答が1件でも付いているスレッドを削除しようとした場合の例外 */
final class QaThreadHasRepliesException extends ConflictHttpException
{
    public static function make(): self
    {
        return new self('回答が付いているスレッドは削除できません。');
    }
}
