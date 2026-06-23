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

namespace App\Tests\Fixture;

use App\Controller\AbstractController;
use App\Form\FormHelper;
use App\Traits\RenderPdfDocumentTrait;
use App\Traits\RenderSpreadsheetDocumentTrait;
use App\Traits\RenderWordDocumentTrait;
use Psr\Container\ContainerInterface;

/**
 * Controller for tests with public methods.
 */
class FixtureController extends AbstractController
{
    use RenderPdfDocumentTrait {
        renderPdfDocument as public;
    }
    use RenderSpreadsheetDocumentTrait {
        renderSpreadsheetDocument as public;
    }
    use RenderWordDocumentTrait {
        renderWordDocument as public;
    }

    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    #[\Override]
    public function createFormHelper(?string $labelPrefix = null, mixed $data = null, array $options = []): FormHelper
    {
        return parent::createFormHelper($labelPrefix, $data, $options);
    }

    #[\Override]
    public function denyAccessUnlessGranted(mixed $attribute, mixed $subject = null, ?string $message = null): void
    {
        parent::denyAccessUnlessGranted($attribute, $subject, $message);
    }

    #[\Override]
    public function getCookiePath(): string
    {
        return parent::getCookiePath();
    }
}
