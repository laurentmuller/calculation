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

use App\Entity\Category;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\CategoryTrait;
use App\Tests\EntityTrait\ProductTrait;
use App\Tests\EntityTrait\TaskTrait;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class CategoryControllerTest extends EntityControllerTestCase
{
    use CalculationTrait;
    use CategoryTrait;
    use ProductTrait;
    use TaskTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/category', self::ROLE_USER];
        yield ['/category', self::ROLE_ADMIN];
        yield ['/category', self::ROLE_SUPER_ADMIN];

        yield ['/category/add', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/category/add', self::ROLE_ADMIN];
        yield ['/category/add', self::ROLE_SUPER_ADMIN];

        yield ['/category/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/category/edit/1', self::ROLE_ADMIN];
        yield ['/category/edit/1', self::ROLE_SUPER_ADMIN];

        yield ['/category/clone/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/category/clone/1', self::ROLE_ADMIN];
        yield ['/category/clone/1', self::ROLE_SUPER_ADMIN];

        yield ['/category/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/category/delete/1', self::ROLE_ADMIN];
        yield ['/category/delete/1', self::ROLE_SUPER_ADMIN];

        yield ['/category/show/1', self::ROLE_USER];
        yield ['/category/show/1', self::ROLE_ADMIN];
        yield ['/category/show/1', self::ROLE_SUPER_ADMIN];

        yield ['/category/pdf', self::ROLE_USER];
        yield ['/category/pdf', self::ROLE_ADMIN];
        yield ['/category/pdf', self::ROLE_SUPER_ADMIN];

        yield ['/category/excel', self::ROLE_USER];
        yield ['/category/excel', self::ROLE_ADMIN];
        yield ['/category/excel', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws ORMException
     */
    public function testAdd(): void
    {
        $data = [
            'category[code]' => 'Code',
            'category[description]' => 'Description',
            'category[group]' => $this->getGroup()->getId(),
        ];
        $this->checkAddEntity('/category/add', $data);
    }

    /**
     * @throws ORMException
     */
    public function testDelete(): void
    {
        $this->addEntities();
        $uri = \sprintf('/category/delete/%d', (int) $this->getCategory()->getId());
        $this->checkDeleteEntity($uri);
    }

    /**
     * @throws ORMException
     */
    public function testDeleteWithDependencies(): void
    {
        $this->getTask();
        $this->getProduct();
        $category = $this->getCategory();
        $calculation = $this->getCalculation();
        $calculation->findCategory($category);
        $this->addEntity($calculation);

        $userName = self::ROLE_ADMIN;
        $uri = \sprintf('/category/delete/%d', (int) $category->getId());

        $this->loginUsername($userName);
        $crawler = $this->client->request(Request::METHOD_GET, $uri);
        self::assertResponseIsSuccessful();

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
        $uri = \sprintf('/category/edit/%d', (int) $this->getCategory()->getId());
        $data = [
            'category[code]' => 'New Code',
            'category[description]' => 'New Description',
            'category[group]' => $this->getGroup()->getId(),
        ];
        $this->checkEditEntity($uri, $data);
    }

    /**
     * @throws ORMException
     */
    public function testExcelEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/category/excel', Category::class);
    }

    /**
     * @throws ORMException
     */
    public function testPdfEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/category/pdf', Category::class);
    }

    /**
     * @throws ORMException
     */
    protected function addEntities(): void
    {
        $this->getCategory();
    }

    /**
     * @throws ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteCategory();
        $this->deleteGroup();
    }
}
