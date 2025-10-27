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

use App\Entity\Group;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\CategoryTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GroupControllerTest extends EntityControllerTestCase
{
    use CalculationTrait;
    use CategoryTrait;

    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/group', self::ROLE_USER];
        yield ['/group', self::ROLE_ADMIN];
        yield ['/group', self::ROLE_SUPER_ADMIN];

        yield ['/group/add', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/group/add', self::ROLE_ADMIN];
        yield ['/group/add', self::ROLE_SUPER_ADMIN];

        yield ['/group/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/group/edit/1', self::ROLE_ADMIN];
        yield ['/group/edit/1', self::ROLE_SUPER_ADMIN];

        yield ['/group/clone/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/group/clone/1', self::ROLE_ADMIN];
        yield ['/group/clone/1', self::ROLE_SUPER_ADMIN];

        yield ['/group/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/group/delete/1', self::ROLE_ADMIN];
        yield ['/group/delete/1', self::ROLE_SUPER_ADMIN];

        yield ['/group/show/1', self::ROLE_USER];
        yield ['/group/show/1', self::ROLE_ADMIN];
        yield ['/group/show/1', self::ROLE_SUPER_ADMIN];

        yield ['/group/pdf', self::ROLE_USER];
        yield ['/group/pdf', self::ROLE_ADMIN];
        yield ['/group/pdf', self::ROLE_SUPER_ADMIN];

        yield ['/group/excel', self::ROLE_USER];
        yield ['/group/excel', self::ROLE_ADMIN];
        yield ['/group/excel', self::ROLE_SUPER_ADMIN];
    }

    public function testAdd(): void
    {
        $data = [
            'group[code]' => 'Code',
            'group[description]' => 'Description',
        ];
        $this->checkAddEntity('/group/add', $data);
    }

    public function testDelete(): void
    {
        $this->addEntities();
        $uri = \sprintf('/group/delete/%d', (int) $this->getGroup()->getId());
        $this->checkDeleteEntity($uri);
    }

    public function testDeleteWithDependencies(): void
    {
        $this->getCategory();
        $group = $this->getGroup();
        $calculation = $this->getCalculation();
        $calculation->findOrCreateGroup($group);
        $this->addEntity($calculation);

        $userName = self::ROLE_ADMIN;
        $uri = \sprintf('/group/delete/%d', (int) $group->getId());

        $this->loginUsername($userName);
        $crawler = $this->client->request(Request::METHOD_GET, $uri);
        self::assertResponseIsSuccessful();

        $text = $this->getService(TranslatorInterface::class)
            ->trans('common.button_back_list');
        $button = $crawler->filter('.btn.btn-form.btn-primary');
        self::assertCount(1, $button);
        self::assertSame($text, $button->text());
    }

    public function testEdit(): void
    {
        $this->addEntities();
        $uri = \sprintf('/group/edit/%d', (int) $this->getGroup()->getId());
        $data = [
            'group[code]' => 'New Code',
            'group[description]' => 'New Description',
        ];
        $this->checkEditEntity($uri, $data);
    }

    public function testExcelEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/group/excel', Group::class);
    }

    public function testPdfEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/group/pdf', Group::class);
    }

    #[\Override]
    protected function addEntities(): void
    {
        $this->getGroup();
    }

    #[\Override]
    protected function deleteEntities(): void
    {
        $this->deleteCategory();
    }
}
