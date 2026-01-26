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

namespace App\Constraint;

use App\Service\CaptchaImageService;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Captcha contraint.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Captcha extends Constraint
{
    public const string INVALID_ERROR = '1a9a1094-3ae5-43c1-b016-6e96854bf144';

    public const string TIMEOUT_ERROR = 'dae83095-9da6-4d38-94b2-693a57d41313';

    protected const array ERROR_NAMES = [
        self::INVALID_ERROR => 'INVALID_ERROR',
        self::TIMEOUT_ERROR => 'TIMEOUT_ERROR',
    ];

    public string $invalidMessage = 'captcha.invalid';

    public string $timeoutMessage = 'captcha.timeout';

    /**
     * @param int $timeout the validation timeout in seconds
     */
    #[HasNamedArguments]
    public function __construct(
        public int $timeout = CaptchaImageService::DEFAULT_TIME_OUT,
        ?array $options = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct($options, $groups, $payload);
    }
}
