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

namespace App\Validator;

use App\Service\CaptchaImageService;
use Symfony\Component\Validator\Constraint;

/**
 * Captcha contraint.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Captcha extends Constraint
{
    final public const IS_INVALID_ERROR = '1a9a1094-3ae5-43c1-b016-6e96854bf144';

    final public const IS_TIMEOUT_ERROR = 'dae83095-9da6-4d38-94b2-693a57d41313';

    protected const ERROR_NAMES = [
        self::IS_INVALID_ERROR => 'IS_INVALID_ERROR',
        self::IS_TIMEOUT_ERROR => 'IS_TIMEOUT_ERROR',
    ];

    public string $invalid_message = 'captcha.invalid';

    public int $timeout = CaptchaImageService::DEFAULT_TIME_OUT;

    public string $timeout_message = 'captcha.timeout';

    /**
     * @param string[] $groups
     */
    public function __construct(
        ?int $timeout = null,
        ?array $options = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct($options, $groups, $payload);

        $this->timeout = $timeout ?? $this->timeout;
    }
}
