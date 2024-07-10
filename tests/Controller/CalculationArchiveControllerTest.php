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
use App\Repository\CalculationStateRepository;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(CalculationArchiveController::class)]
class CalculationArchiveControllerTest extends ControllerTestCase
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
     * @throws ORMException
     */
    public function testArchiveEditableEmpty(): void
    {
        $this->addNotEditState();
        $repository = $this->getService(CalculationStateRepository::class);

        /** @psalm-var CalculationState[] $entities */
        $entities = $repository->getEditableQueryBuilder()
            ->getQuery()
            ->getResult();
        if ([] !== $entities) {
            foreach ($entities as $entity) {
                $repository->remove($entity, false);
            }
            $repository->flush();
        }

        $this->checkRoute(
            url: '/admin/archive',
            username: self::ROLE_ADMIN,
            expected: Response::HTTP_FOUND,
            method: Request::METHOD_POST,
        );
    }

    /**
     * @throws ORMException
     */
    public function testArchiveNotEditableEmpty(): void
    {
        $this->addEditState();
        $repository = $this->getService(CalculationStateRepository::class);

        /** @psalm-var CalculationState[] $entities */
        $entities = $repository->getNotEditableQueryBuilder()
            ->getQuery()
            ->getResult();
        if ([] !== $entities) {
            foreach ($entities as $entity) {
                $repository->remove($entity, false);
            }
            $repository->flush();
        }

        $this->checkRoute(
            url: '/admin/archive',
            username: self::ROLE_ADMIN,
            expected: Response::HTTP_FOUND,
            method: Request::METHOD_POST,
        );
    }

    /**
     * @throws ORMException|\Exception
     */
    public function testArchiveSuccess(): void
    {
        $this->addEntities();
        $data = [
            'form[date]' => '2024-06-01',
            'form[simulate]' => '1',
            'form[confirm]' => '1',
        ];

        $repository = $this->getService(CalculationStateRepository::class);

        /** @psalm-var ?CalculationState $editState */
        $editState = $repository->getEditableQueryBuilder()
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
        $data['form[sources][0]'] = $editState?->getId();

        /** @psalm-var ?CalculationState $noEditable */
        $noEditable = $repository->getNotEditableQueryBuilder()
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
        $data['form[target]'] = $noEditable?->getId();

        $this->checkForm(
            uri: '/admin/archive',
            id: 'archive.edit.submit',
            data: $data,
            followRedirect: false
        );
    }

    /**
     * @throws ORMException
     */
    protected function addEntities(): void
    {
        $this->addEditState();
        $this->addNotEditState();
    }

    /**
     * @throws ORMException
     */
    protected function deleteEntities(): void
    {
        $this->editState = $this->deleteEntity($this->editState);
        $this->notEditSate = $this->deleteEntity($this->notEditSate);
    }

    /**
     * @throws ORMException
     */
    private function addEditState(): void
    {
        if ($this->editState instanceof CalculationState) {
            return;
        }
        $this->editState = new CalculationState();
        $this->editState->setCode('Editable')->setEditable(true);
        $this->addEntity($this->editState);
    }

    /**
     * @throws ORMException
     */
    private function addNotEditState(): void
    {
        if ($this->notEditSate instanceof CalculationState) {
            return;
        }
        $this->notEditSate = new CalculationState();
        $this->notEditSate->setCode('NotEditable')->setEditable(false);
        $this->addEntity($this->notEditSate);
    }
}
