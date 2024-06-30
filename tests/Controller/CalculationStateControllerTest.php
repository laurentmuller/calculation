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

use App\Controller\AbstractController;
use App\Controller\AbstractEntityController;
use App\Controller\CalculationStateController;
use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Tests\EntityTrait\CalculationTrait;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(AbstractController::class)]
#[CoversClass(AbstractEntityController::class)]
#[CoversClass(CalculationStateController::class)]
class CalculationStateControllerTest extends EntityControllerTestCase
{
    use CalculationTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/calculationstate', self::ROLE_USER];
        yield ['/calculationstate', self::ROLE_ADMIN];
        yield ['/calculationstate', self::ROLE_SUPER_ADMIN];

        yield ['/calculationstate/add', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculationstate/add', self::ROLE_ADMIN];
        yield ['/calculationstate/add', self::ROLE_SUPER_ADMIN];

        yield ['/calculationstate/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculationstate/edit/1', self::ROLE_ADMIN];
        yield ['/calculationstate/edit/1', self::ROLE_SUPER_ADMIN];

        yield ['/calculationstate/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculationstate/delete/1', self::ROLE_ADMIN];
        yield ['/calculationstate/delete/1', self::ROLE_SUPER_ADMIN];

        yield ['/calculationstate/show/1', self::ROLE_USER];
        yield ['/calculationstate/show/1', self::ROLE_ADMIN];
        yield ['/calculationstate/show/1', self::ROLE_SUPER_ADMIN];

        yield ['/calculationstate/pdf', self::ROLE_USER];
        yield ['/calculationstate/pdf', self::ROLE_ADMIN];
        yield ['/calculationstate/pdf', self::ROLE_SUPER_ADMIN];

        yield ['/calculationstate/excel', self::ROLE_USER];
        yield ['/calculationstate/excel', self::ROLE_ADMIN];
        yield ['/calculationstate/excel', self::ROLE_SUPER_ADMIN];

        yield ['/calculationstate/clone/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculationstate/clone/1', self::ROLE_ADMIN];
        yield ['/calculationstate/clone/1', self::ROLE_SUPER_ADMIN];
    }

    public function testAdd(): void
    {
        $data = [
            'calculation_state[code]' => 'Code',
            'calculation_state[description]' => 'Description',
            'calculation_state[editable]' => '1',
            'calculation_state[color]' => '#000000',
        ];
        $this->checkAddEntity('/calculationstate/add', $data);
    }

    /**
     * @throws ORMException
     */
    public function testDelete(): void
    {
        $this->addEntities();
        $uri = \sprintf('/calculationstate/delete/%d', (int) $this->getCalculationState()->getId());
        $this->checkDeleteEntity($uri);
    }

    /**
     * @throws ORMException
     */
    public function testDeleteWithDependencies(): void
    {
        $this->getCalculation();
        $state = $this->getCalculationState();

        $userName = self::ROLE_ADMIN;
        $uri = \sprintf('/calculationstate/delete/%d', (int) $state->getId());

        $this->loginUsername($userName);
        $crawler = $this->client->request(Request::METHOD_GET, $uri);
        $this->assertResponseIsSuccessful();

        $text = $this->getService(TranslatorInterface::class)
            ->trans('common.button_back_list');
        $button = $crawler->filter('.btn.btn-form.btn-primary');
        self::assertCount(1, $button);
        self::assertSame($text, $button->text());
    }

    /**
     * @throws ORMException
     */
    public function testEdit(): void
    {
        $this->addEntities();
        $uri = \sprintf('/calculationstate/edit/%d', (int) $this->getCalculationState()->getId());
        $data = [
            'calculation_state[code]' => 'New Code',
            'calculation_state[description]' => 'New Description',
            'calculation_state[editable]' => '1',
            'calculation_state[color]' => '#FFFFFF',
        ];
        $this->checkEditEntity($uri, $data);
    }

    /**
     * @throws ORMException
     */
    public function testExcelEmpty(): void
    {
        $this->deleteEntitiesByClass(Calculation::class);
        $this->checkUriWithEmptyEntity('/calculationstate/excel', CalculationState::class);
    }

    /**
     * @throws ORMException
     */
    public function testPdfEmpty(): void
    {
        $this->deleteEntitiesByClass(Calculation::class);
        $this->checkUriWithEmptyEntity('/calculationstate/pdf', CalculationState::class);
    }

    /**
     * @throws ORMException
     */
    protected function addEntities(): void
    {
        $this->getCalculationState();
    }

    /**
     * @throws ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteEntitiesByClass(Calculation::class);
        $this->deleteCalculationState();
    }
}
