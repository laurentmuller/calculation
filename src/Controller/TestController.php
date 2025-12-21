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

namespace App\Controller;

use App\Attribute\ForSuperAdmin;
use App\Attribute\GetPostRoute;
use App\Attribute\GetRoute;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Product;
use App\Enums\MessagePosition;
use App\Form\Type\ReCaptchaType;
use App\Repository\GroupRepository;
use App\Service\RecaptchaResponseService;
use App\Service\RecaptchaService;
use App\Service\SearchService;
use App\Service\SwissPostService;
use App\Traits\GroupByTrait;
use App\Utils\StringUtils;
use Doctrine\ORM\EntityManagerInterface;
use ReCaptcha\Response as ReCaptchaResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for tests.
 *
 * @phpstan-import-type SearchType from SearchService
 *
 * @phpstan-type CurrencyType = array{code: string, name: string}
 */
#[ForSuperAdmin]
#[Route(path: '/test', name: 'test_')]
class TestController extends AbstractController
{
    use GroupByTrait;

    /**
     * Test notifications.
     */
    #[GetRoute(path: '/notifications', name: 'notifications')]
    public function notifications(): Response
    {
        return $this->render('test/notification.html.twig', ['positions' => MessagePosition::sorted()]);
    }

    /**
     * Display the reCaptcha.
     */
    #[GetPostRoute(path: '/recaptcha', name: 'recaptcha')]
    public function recaptcha(
        Request $request,
        RecaptchaService $service,
        RecaptchaResponseService $responseService
    ): Response {
        $data = [
            'subject' => 'My subject',
            'message' => 'My message',
        ];
        $expectedAction = 'register';
        $helper = $this->createFormHelper('user.fields.', $data);
        $helper->field('subject')->addTextType()
            ->field('message')->addTextType()
            ->field('captcha')
            ->updateOption('expectedAction', $expectedAction)
            ->add(ReCaptchaType::class)
            ->getBuilder()->setAttribute('block_name', '');
        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            $response = $service->getLastResponse();
            $message = $response instanceof ReCaptchaResponse ?
                $responseService->format($response) : 'test.recaptcha_success';

            return $this->redirectToHomePage($message);
        }

        return $this->render('test/recaptcha.html.twig', ['form' => $form]);
    }

    #[GetRoute(path: '/search', name: 'search')]
    public function search(
        SearchService $service,
        #[MapQueryParameter]
        ?string $query = null,
        #[MapQueryParameter]
        ?string $entity = null,
        #[MapQueryParameter]
        int $limit = 25,
        #[MapQueryParameter]
        int $offset = 0
    ): JsonResponse {
        $count = $service->count($query, $entity);
        $results = $service->search($query, $entity, $limit, $offset);
        foreach ($results as &$row) {
            $type = $row[SearchService::COLUMN_TYPE];
            $field = $row[SearchService::COLUMN_FIELD];
            $lowerType = \strtolower($type);
            $row[SearchService::COLUMN_ENTITY_NAME] = $this->trans($lowerType . '.name');
            $row[SearchService::COLUMN_FIELD_NAME] = $this->trans(\sprintf('%s.fields.%s', $lowerType, $field));
            $row[SearchService::COLUMN_CONTENT] = $service->formatContent(\sprintf('%s.%s', $type, $field), $row[SearchService::COLUMN_CONTENT]);
        }
        $data = [
            'query' => $query,
            'entity' => $entity,
            'offset' => $offset,
            'limit' => $limit,
            'total' => $count,
            'filtered' => \count($results),
            'results' => $results,
        ];

        return $this->json($data);
    }

    /**
     * Search zip codes, cities and streets from Switzerland.
     */
    #[GetRoute(path: '/swiss', name: 'swiss')]
    public function swiss(
        SwissPostService $service,
        #[MapQueryParameter]
        string $all = '',
        #[MapQueryParameter]
        string $zip = '',
        #[MapQueryParameter]
        string $city = '',
        #[MapQueryParameter]
        string $street = '',
        #[MapQueryParameter]
        int $limit = 25
    ): JsonResponse {
        if ('' !== $all) {
            $query = $all;
            $rows = $service->findAll($all, $limit);
        } else {
            $parameters = [
                'street' => $street,
                'zip' => $zip,
                'city' => $city,
            ];
            $query = \implode(', ', \array_filter($parameters));
            $rows = $service->find($parameters, $limit);
        }
        $data = [
            'result' => [] !== $rows,
            'query' => $query,
            'limit' => $limit,
            'count' => \count($rows),
            'rows' => $rows,
        ];

        return $this->json($data);
    }

    #[GetRoute(path: '/tree', name: 'tree')]
    public function tree(Request $request, GroupRepository $repository, EntityManagerInterface $manager): Response
    {
        if ($request->isXmlHttpRequest()) {
            $count = 0;
            $nodes = [];
            $groups = $repository->findByCode();
            foreach ($groups as $group) {
                $node = [
                    'id' => \sprintf('group-%d', (int) $group->getId()),
                    'text' => $group->getCode(),
                    'icon' => 'fas fa-code-branch fa-fw',
                    'badgeValue' => $group->countItems(),
                ];
                foreach ($group->getCategories() as $category) {
                    $count += $category->countItems();
                    $node['nodes'][] = [
                        'id' => \sprintf('category-%d', (int) $category->getId()),
                        'text' => $category->getCode(),
                        'icon' => 'far fa-folder fa-fw',
                        'badgeValue' => $category->countItems(),
                    ];
                }
                $nodes[] = $node;
            }
            $root = [
                'id' => 'root',
                'text' => 'Catalogue',
                'icon' => 'fas fa-table fa-fw',
                'nodes' => $nodes,
                'expanded' => true,
                'badgeValue' => $count,
            ];

            return $this->json([$root]);
        }

        return $this->render('test/tree_view.html.twig', [
            'categories' => $this->getCategories($manager),
            'products' => $this->getProducts($manager),
            'states' => $this->getStates($manager),
            'currencies' => $this->getCurrencies(),
            'countries' => Countries::getNames(),
        ]);
    }

    /**
     * @phpstan-param CurrencyType $currency
     */
    private function filterCurrency(array $currency): bool
    {
        return !StringUtils::pregMatch('/\d|\(/', $currency['name']);
    }

    private function getCategories(EntityManagerInterface $manager): array
    {
        $categories = $manager->getRepository(Category::class)
            ->getQueryBuilderByGroup()
            ->getQuery()
            ->getResult();

        return $this->groupBy($categories, $this->mapCategory(...));
    }

    private function getCurrencies(): array
    {
        $currencies = \array_map($this->mapCurrency(...), Currencies::getCurrencyCodes());
        $currencies = \array_filter($currencies, $this->filterCurrency(...));
        \usort($currencies, $this->sortCurrencies(...));

        return $currencies;
    }

    private function getProducts(EntityManagerInterface $manager): array
    {
        $products = $manager->getRepository(Product::class)
            ->findByGroup();

        return $this->groupBy($products, $this->mapProduct(...));
    }

    private function getStates(EntityManagerInterface $manager): array
    {
        $states = $manager->getRepository(CalculationState::class)
            ->getQueryBuilderByEditable()
            ->getQuery()
            ->getResult();

        return $this->groupBy($states, $this->mapCalculationState(...));
    }

    private function mapCalculationState(CalculationState $state): string
    {
        return \sprintf('calculationstate.list.editable_%d', (int) $state->isEditable());
    }

    private function mapCategory(Category $category): string
    {
        return (string) $category->getGroupCode();
    }

    /**
     * @phpstan-return CurrencyType
     */
    private function mapCurrency(string $code): array
    {
        return [
            'code' => $code,
            'name' => \sprintf('%s - %s', \ucfirst(Currencies::getName($code)), Currencies::getSymbol($code)),
        ];
    }

    private function mapProduct(Product $product): string
    {
        return \sprintf('%s - %s', $product->getGroupCode(), $product->getCategoryCode());
    }

    /**
     * @phpstan-param CurrencyType $a
     * @phpstan-param CurrencyType $b
     */
    private function sortCurrencies(array $a, array $b): int
    {
        return \strnatcasecmp($a['name'], $b['name']);
    }
}
