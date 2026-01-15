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
use App\Entity\Customer;
use App\Entity\Group;
use App\Enums\Importance;
use App\Model\HttpClientError;
use App\Repository\CustomerRepository;
use App\Repository\GroupRepository;
use App\Service\FontAwesomeService;
use App\Service\MailerService;
use App\Service\RecaptchaService;
use App\Service\SearchService;
use App\Translator\TranslatorFactory;
use App\Translator\TranslatorServiceInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;

final class TestControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        $routes = [
            'editor',
            'label',
            'pdf',
            'word',
            'colors',
            'memory',
            'notifications',
            'password',
            'recaptcha',
            'search',
            'swiss',
            'translate',
            'tree',
        ];

        $users = [
            self::ROLE_USER => Response::HTTP_FORBIDDEN,
            self::ROLE_ADMIN => Response::HTTP_FORBIDDEN,
            self::ROLE_SUPER_ADMIN => Response::HTTP_OK,
        ];

        foreach ($routes as $route) {
            foreach ($users as $user => $status) {
                yield ['/test/' . $route, $user, $status];
            }
        }

        yield [
            '/test/tree',
            self::ROLE_SUPER_ADMIN,
            Response::HTTP_OK,
            Request::METHOD_GET,
            true,
        ];

        yield [
            '/test/swiss?all=fribourg',
            self::ROLE_SUPER_ADMIN,
            Response::HTTP_OK,
            Request::METHOD_GET,
            true,
        ];
    }

    public function testEditorException(): void
    {
        $service = $this->createMock(MailerService::class);
        $service->method('sendNotification')
            ->willThrowException(new UnexpectedResponseException('Fake Message'));
        self::setService(MailerService::class, $service);
        $this->sendMessage(false);
    }

    public function testEditorSuccess(): void
    {
        $this->sendMessage(true);
    }

    public function testExportFontAwesome(): void
    {
        $service = $this->createMock(FontAwesomeService::class);
        $this->setService(FontAwesomeService::class, $service);
        $this->checkRoute(
            url: '/test/fontawesome',
            username: self::ROLE_SUPER_ADMIN,
            xmlHttpRequest: true
        );
    }

    public function testExportLabel(): void
    {
        $customer = $this->createCustomer();
        $query = $this->createMock(Query::class);
        $query->method('getResult')
            ->willReturn([$customer]);

        $builder = $this->createMock(QueryBuilder::class);
        $builder->method('orderBy')
            ->willReturnSelf();
        $builder->method('setMaxResults')
            ->willReturnSelf();
        $builder->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(CustomerRepository::class);
        $repository->method('createDefaultQueryBuilder')
            ->willReturn($builder);

        $this->setService(CustomerRepository::class, $repository);

        $this->checkRoute(
            url: '/test/label',
            username: self::ROLE_SUPER_ADMIN,
        );
    }

    public function testRecaptcha(): void
    {
        $response = new \ReCaptcha\Response(true);
        $service = $this->createMock(RecaptchaService::class);
        $service->method('verify')
            ->willReturn($response);
        $this->setService(RecaptchaService::class, $service);

        $this->checkForm(
            uri: '/test/recaptcha',
            data: [
                'form[subject]' => 'My subject',
                'form[message]' => 'My message',
                'form[captcha]' => 'fake',
            ],
            userName: self::ROLE_SUPER_ADMIN,
            disableReboot: true
        );
    }

    public function testSearch(): void
    {
        $row = $this->createSearchItem();
        $service = $this->createMock(SearchService::class);
        $service->method('count')
            ->willReturn(1);
        $service->method('search')
            ->willReturn([$row]);
        $this->setService(SearchService::class, $service);
        $this->checkRoute(
            url: '/test/search',
            username: self::ROLE_SUPER_ADMIN,
            xmlHttpRequest: true
        );
    }

    public function testTranslateWithError(): void
    {
        $error = new HttpClientError(400000, 'Fake');
        $service = $this->createMock(TranslatorServiceInterface::class);
        $service->method('getName')
            ->willReturn('Bing');
        $service->method('getApiUrl')
            ->willReturn('https://translate.bing.com');
        $service->method('getLastError')
            ->willReturn($error);

        $factory = $this->createMock(TranslatorFactory::class);
        $factory->method('getSessionService')
            ->willReturn($service);
        $factory->method('getTranslators')
            ->willReturn([$service]);
        $this->setService(TranslatorFactory::class, $factory);

        $this->checkRoute(
            url: '/test/translate',
            username: self::ROLE_SUPER_ADMIN
        );
    }

    public function testTree(): void
    {
        $category = new Category();
        $category->setCode('category');

        $group = new Group();
        $group->setCode('group');
        $group->addCategory($category);

        $repository = $this->createMock(GroupRepository::class);
        $repository->method('findByCode')
            ->willReturn([$group]);
        $this->setService(GroupRepository::class, $repository);

        $this->checkRoute(
            url: '/test/tree',
            username: self::ROLE_SUPER_ADMIN,
            xmlHttpRequest: true
        );
    }

    private function createCustomer(): Customer
    {
        $customer = new Customer();
        $customer->setCompany('company')
            ->setFirstName('firstname')
            ->setLastName('lastname')
            ->setCity('city')
            ->setZipCode('1234');

        return $customer;
    }

    private function createSearchItem(): array
    {
        return [
            SearchService::COLUMN_ID => 1,
            SearchService::COLUMN_TYPE => 'calculation',
            SearchService::COLUMN_FIELD => 'id',
            SearchService::COLUMN_CONTENT => '1',
            SearchService::COLUMN_ENTITY_NAME => 'calculation',
            SearchService::COLUMN_FIELD_NAME => 'id',
        ];
    }

    private function sendMessage(bool $followRedirect): void
    {
        $data = [
            'form[email]' => 'bibi@bibi.nu',
            'form[importance]' => Importance::LOW->value,
            'form[message]' => 'Fake message to be send.',
        ];
        $this->checkForm(
            uri: '/test/editor',
            id: 'common.button_send',
            data: $data,
            userName: self::ROLE_SUPER_ADMIN,
            followRedirect: $followRedirect,
            disableReboot: true
        );
    }
}
