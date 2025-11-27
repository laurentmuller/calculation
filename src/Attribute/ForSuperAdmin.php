<?php

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Attribute;

use App\Interfaces\RoleInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
class ForSuperAdmin extends IsGranted
{
    public function __construct(
        string|array|Expression|\Closure|null $subject = null,
        ?string $message = null,
        ?int $statusCode = null,
        ?int $exceptionCode = null,
        array|string $methods = []
    ) {
        parent::__construct(
            attribute: RoleInterface::ROLE_SUPER_ADMIN,
            subject: $subject,
            message: $message,
            statusCode: $statusCode,
            exceptionCode: $exceptionCode,
            methods: $methods
        );
    }
}
