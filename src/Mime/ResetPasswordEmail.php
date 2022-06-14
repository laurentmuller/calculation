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

namespace App\Mime;

use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Notification email to reset password.
 */
class ResetPasswordEmail extends NotificationEmail
{
    public function __construct(TranslatorInterface $translator, Headers $headers = null, AbstractPart $body = null)
    {
        parent::__construct($translator, $headers, $body);
        $this->htmlTemplate('notification/reset_password.html.twig');
        $this->importance(self::IMPORTANCE_HIGH);
    }
}
