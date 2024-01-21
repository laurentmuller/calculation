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

namespace App\Tests\Controller;

use App\Controller\CalculationArchiveController;
use App\Entity\CalculationState;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(CalculationArchiveController::class)]
class CalculationArchiveControllerTest extends AbstractControllerTestCase
{
    private ?CalculationState $editState = null;
    private ?CalculationState $notEditSate = null;

    public static function getRoutes(): \Iterator
    {
        yield ['/admin/archive', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/archive', self::ROLE_ADMIN];
        yield ['/admin/archive', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        if (!$this->editState instanceof CalculationState) {
            $this->editState = new CalculationState();
            $this->editState->setCode('Editable')->setEditable(true);
            $this->addEntity($this->editState);
        }

        if (!$this->notEditSate instanceof CalculationState) {
            $this->notEditSate = new CalculationState();
            $this->notEditSate->setCode('NotEditable')->setEditable(false);
            $this->addEntity($this->notEditSate);
        }
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->editState = $this->deleteEntity($this->editState);
        $this->notEditSate = $this->deleteEntity($this->notEditSate);
    }
}
