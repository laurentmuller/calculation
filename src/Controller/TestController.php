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

use App\Attribute\GetPostRoute;
use App\Attribute\GetRoute;
use App\Attribute\PdfRoute;
use App\Attribute\WordRoute;
use App\Constraint\Captcha;
use App\Constraint\Password;
use App\Constraint\Strength;
use App\Entity\AbstractProperty;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\Enums\FlashType;
use App\Enums\Importance;
use App\Enums\MessagePosition;
use App\Enums\StrengthLevel;
use App\Form\Parameters\ApplicationParametersType;
use App\Form\Parameters\UserParametersType;
use App\Form\Type\AlphaCaptchaType;
use App\Form\Type\CaptchaImageType;
use App\Form\Type\ReCaptchaType;
use App\Form\Type\SimpleEditorType;
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\RoleInterface;
use App\Interfaces\SortModeInterface;
use App\Interfaces\TableInterface;
use App\Interfaces\UserInterface;
use App\Model\HttpClientError;
use App\Parameter\AbstractParameters;
use App\Parameter\ApplicationParameters;
use App\Parameter\UserParameters;
use App\Pdf\Events\PdfLabelTextEvent;
use App\Pdf\Interfaces\PdfLabelTextListenerInterface;
use App\Pdf\PdfLabelDocument;
use App\Report\FontAwesomeReport;
use App\Report\HtmlColorsReport;
use App\Report\HtmlReport;
use App\Report\MemoryImageReport;
use App\Repository\CustomerRepository;
use App\Repository\GroupRepository;
use App\Response\PdfResponse;
use App\Response\WordResponse;
use App\Service\AbstractHttpClientService;
use App\Service\CaptchaImageService;
use App\Service\FontAwesomeImageService;
use App\Service\FontAwesomeService;
use App\Service\MailerService;
use App\Service\PdfLabelService;
use App\Service\RecaptchaResponseService;
use App\Service\RecaptchaService;
use App\Service\SearchService;
use App\Service\SwissPostService;
use App\Traits\CookieTrait;
use App\Traits\GroupByTrait;
use App\Traits\StrengthLevelTranslatorTrait;
use App\Translator\TranslatorFactory;
use App\Utils\StringUtils;
use App\Word\HtmlDocument;
use Doctrine\ORM\EntityManagerInterface;
use fpdf\Enums\PdfFontStyle;
use Psr\Log\LoggerInterface;
use ReCaptcha\Response as ReCaptchaResponse;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Controller for tests.
 *
 * @phpstan-import-type SearchType from SearchService
 */
#[AsController]
#[Route(path: '/test', name: 'test_')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class TestController extends AbstractController
{
    use CookieTrait;
    use GroupByTrait;
    use StrengthLevelTranslatorTrait;

    /**
     * Output a report with HTML and Boostrap colors.
     */
    #[GetRoute(path: '/colors', name: 'colors')]
    public function colors(): PdfResponse
    {
        $report = new HtmlColorsReport($this);

        return $this->renderPdfDocument($report);
    }

    /**
     * Test sending notification mail.
     */
    #[GetPostRoute(path: '/editor', name: 'editor')]
    public function editor(
        Request $request,
        #[CurrentUser]
        User $user,
        MailerService $service,
        LoggerInterface $logger
    ): Response {
        $data = [
            'email' => $user->getEmail(),
            'importance' => Importance::MEDIUM,
        ];
        $helper = $this->createFormHelper('user.fields.', $data);
        $helper->field('email')
            ->addEmailType();
        $helper->field('importance')
            ->label('importance.name')
            ->addEnumType(Importance::class);
        $helper->field('message')
            ->updateAttribute('minlength', 10)
            ->add(SimpleEditorType::class);
        $helper->field('attachments')
            ->updateOptions([
                'multiple' => true,
                'maxfiles' => 3,
                'maxsize' => '10mi',
                'maxsizetotal' => '30mi', ])
            ->notRequired()
            ->addFileType();
        $form = $helper->createForm();

        if ($this->handleRequestForm($request, $form)) {
            /**
             * @phpstan-var array{
             *     email: string,
             *     message: string,
             *     importance: Importance,
             *     attachments: UploadedFile[]}  $data
             */
            $data = $form->getData();
            $email = $data['email'];
            $message = $data['message'];
            $importance = $data['importance'];
            $attachments = $data['attachments'];

            try {
                $service->sendNotification($email, $user, $message, $importance, $attachments);

                return $this->redirectToHomePage('user.comment.success');
            } catch (TransportExceptionInterface $e) {
                return $this->renderFormException('user.comment.error', $e, $logger);
            }
        }

        return $this->render('test/editor.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Export a report label.
     */
    #[GetRoute(path: '/label', name: 'label')]
    public function exportLabel(CustomerRepository $repository, PdfLabelService $service): PdfResponse
    {
        $listener = new class() implements PdfLabelTextListenerInterface {
            #[\Override]
            public function drawLabelText(PdfLabelTextEvent $event): bool
            {
                if ($event->index !== $event->lines - 1 && $event->index > 2) {
                    return false;
                }

                if ('' === $event->text) {
                    return true;
                }

                $parent = $event->parent;
                $font = $parent->getCurrentFont();
                $parent->setFont(style: PdfFontStyle::BOLD);
                $parent->cell($event->width, $event->height, $event->text);
                $font->apply($parent);

                return true;
            }
        };

        $label = $service->get('5161');
        $report = new PdfLabelDocument($label);
        $report->setLabelBorder(true)
            ->setLabelTextListener($listener)
            ->setTitle(\sprintf('Etiquette - Avery %s', $label->name));

        $sortField = $repository->getSortField(CustomerRepository::NAME_COMPANY_FIELD);
        /** @phpstan-var \App\Entity\Customer[] $customers */
        $customers = $repository->createDefaultQueryBuilder()
            ->orderBy($sortField, SortModeInterface::SORT_ASC)
            ->setMaxResults(29)
            ->getQuery()
            ->getResult();

        foreach ($customers as $customer) {
            $values = \array_filter([
                $customer->getCompany(),
                $customer->getFullName(),
                StringUtils::NEW_LINE,
                $customer->getAddress(),
                $customer->getZipCity(),
            ]);
            $text = \implode(StringUtils::NEW_LINE, $values);
            $report->addLabel($text);
        }

        return $this->renderPdfDocument($report);
    }

    /**
     * Export an HTML page to PDF.
     */
    #[PdfRoute]
    public function exportPdf(): PdfResponse
    {
        $content = $this->renderView('test/html_report.html.twig');
        $report = new HtmlReport($this, $content);
        $report->setTitle($this->trans('test.html'));

        return $this->renderPdfDocument($report);
    }

    /**
     * Export an HTML page to Word.
     */
    #[WordRoute]
    public function exportWord(): WordResponse
    {
        $content = $this->renderView('test/html_report.html.twig');
        $doc = new HtmlDocument($this, $content);
        $doc->setTitleTrans('test.html');

        return $this->renderWordDocument($doc);
    }

    /**
     * Output a report with Fontawesome images.
     */
    #[GetRoute(path: '/fontawesome', name: 'fontawesome')]
    public function fontAwesome(FontAwesomeImageService $service): Response
    {
        if (!$service->isSvgSupported() || $service->isImagickException()) {
            return $this->redirectToHomePage(
                id: 'test.fontawesome_error',
                type: FlashType::WARNING
            );
        }

        $report = new FontAwesomeReport($this, $service);

        return $this->renderPdfDocument($report);
    }

    /**
     * Output a report with memory images.
     */
    #[GetRoute(path: '/memory', name: 'memory')]
    public function memoryImage(
        #[Autowire('%kernel.project_dir%/public/images/logo/logo-customer-148x148.png')]
        string $logoFile,
        #[Autowire('%kernel.project_dir%/public/images/icons/favicon-144x144.png')]
        string $iconFile,
        #[Autowire('%kernel.project_dir%/public/images/screenshots/home_light.png')]
        string $screenshotFile,
        FontAwesomeService $service
    ): PdfResponse {
        $report = new MemoryImageReport(
            $this,
            $logoFile,
            $iconFile,
            $screenshotFile,
            $service
        );

        return $this->renderPdfDocument($report);
    }

    /**
     * Test notifications.
     */
    #[GetRoute(path: '/notifications', name: 'notifications')]
    public function notifications(): Response
    {
        return $this->render('test/notification.html.twig', ['positions' => MessagePosition::sorted()]);
    }

    /**
     * Test password validation.
     *
     * @throws \Exception
     */
    #[GetPostRoute(path: '/password', name: 'password')]
    public function password(Request $request, CaptchaImageService $service): Response
    {
        $password = new Password(all: true);
        $options = PropertyServiceInterface::PASSWORD_OPTIONS;
        $strength = new Strength(StrengthLevel::MEDIUM);
        $listener = function (PreSubmitEvent $event) use ($options, $password, $strength): void {
            /** @phpstan-var array $data */
            $data = $event->getData();
            foreach ($options as $property => $option) {
                $password->setOption($option, (bool) ($data[$property] ?? false));
            }
            $level = (int) $data['level'];
            $strength->minimum = StrengthLevel::tryFrom($level) ?? StrengthLevel::NONE;
        };
        $data = [
            'input' => 'aB123456#*/82568A',
            'level' => StrengthLevel::MEDIUM,
        ];
        foreach (\array_keys($options) as $property) {
            $data[$property] = true;
        }
        $helper = $this->createFormHelper('password.', $data);
        $helper->listenerPreSubmit($listener);
        $helper->field('input')
            ->widgetClass('password-strength')
            ->updateAttribute('data-strength', StrengthLevel::MEDIUM->value)
            ->updateAttribute(
                'data-url',
                $this->generateUrl(route: 'ajax_password', referenceType: UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->minLength(UserInterface::MIN_PASSWORD_LENGTH)
            ->maxLength(UserInterface::MAX_USERNAME_LENGTH)
            ->constraints(
                new Length(min: UserInterface::MIN_PASSWORD_LENGTH, max: UserInterface::MAX_USERNAME_LENGTH),
                $password,
                $strength
            )->addTextType();
        foreach ($options as $key => $value) {
            $helper->field($key)
                ->updateAttribute('data-validation', $value)
                ->widgetClass('password-option')
                ->addCheckboxType();
        }
        $helper->field('level')
            ->label('password.security_strength_level')
            ->addEnumType(StrengthLevel::class);
        $helper->field('captcha')
            ->label('captcha.label')
            ->constraints(new NotBlank(), new Captcha())
            ->updateOption('image', $service->generateImage())
            ->add(CaptchaImageType::class);
        $helper->field('alpha')
            ->label('captcha.label')
            ->add(AlphaCaptchaType::class);

        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            /** @phpstan-var array<string, mixed> $data */
            $data = $form->getData();
            $message = $this->trans('password.success');
            $message .= '<ul>';
            foreach (\array_keys($options) as $property) {
                if (true === $data[$property]) {
                    $message .= '<li>' . $this->trans("password.$property") . '</li>';
                }
            }
            /** @phpstan-var StrengthLevel $level */
            $level = $data['level'];
            if (StrengthLevel::NONE !== $level) {
                $message .= '<li>';
                $message .= $this->trans('password.security_strength_level');
                $message .= ' : ';
                $message .= $this->translateLevel($level);
                $message .= '</li>';
            }
            $message .= '</ul>';

            return $this->redirectToHomePage($message);
        }

        return $this->render('test/password.html.twig', ['form' => $form]);
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
    public function search(Request $request, SearchService $service): JsonResponse
    {
        $query = $this->getRequestString($request, 'query');
        $entity = $this->getRequestString($request, 'entity');
        $limit = $this->getRequestInt($request, 'limit', 25);
        $offset = $this->getRequestInt($request, 'offset');
        $count = $service->count($query, $entity);
        $results = $service->search($query, $entity, $limit, $offset);
        foreach ($results as &$row) {
            $type = $row[SearchService::COLUMN_TYPE];
            $field = $row[SearchService::COLUMN_FIELD];
            $lowerType = \strtolower($type);
            $row[SearchService::COLUMN_ENTITY_NAME] = $this->trans("$lowerType.name");
            $row[SearchService::COLUMN_FIELD_NAME] = $this->trans("$lowerType.fields.$field");
            $row[SearchService::COLUMN_CONTENT] = $service->formatContent("$type.$field", $row[SearchService::COLUMN_CONTENT]);
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
    public function swiss(Request $request, SwissPostService $service): JsonResponse
    {
        $all = $this->getRequestString($request, 'all');
        $zip = $this->getRequestString($request, 'zip');
        $city = $this->getRequestString($request, 'city');
        $street = $this->getRequestString($request, 'street');
        $limit = $this->getRequestInt($request, 'limit', 25);
        if ('' !== $all) {
            $query = $all;
            $rows = $service->findAll($all, $limit);
        } elseif ('' !== $zip) {
            $query = $zip;
            $rows = $service->findZip($zip, $limit);
        } elseif ('' !== $city) {
            $query = $city;
            $rows = $service->findCity($city, $limit);
        } elseif ('' !== $street) {
            $query = $street;
            $rows = $service->findStreet($street, $limit);
        } else {
            $query = '';
            $rows = [];
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

    #[GetPostRoute(path: '/application/parameters', name: 'application_parameter')]
    public function testApplicationParameters(Request $request, ApplicationParameters $parameters): Response
    {
        $templateParameters = [
            'title_icon' => 'cogs',
            'title' => 'parameters.title',
            'title_description' => 'parameters.description',
        ];

        return $this->renderParameters(
            $request,
            $parameters,
            ApplicationParametersType::class,
            'parameters.success',
            $templateParameters
        );
    }

    #[GetPostRoute(path: '/user/parameters', name: 'user_parameter')]
    public function testUserParameters(Request $request, UserParameters $parameters): Response
    {
        $templateParameters = [
            'title_icon' => 'user-gear',
            'title' => 'user.parameters.title',
            'title_description' => 'user.parameters.description',
        ];

        return $this->renderParameters(
            $request,
            $parameters,
            UserParametersType::class,
            'user.parameters.success',
            $templateParameters
        );
    }

    /**
     * Show the translation page.
     *
     * @throws ServiceNotFoundException if the service is not found
     */
    #[GetRoute(path: '/translate', name: 'translate')]
    public function translate(TranslatorFactory $factory): Response
    {
        $service = $factory->getSessionService();
        $languages = $service->getLanguages();
        $error = $service->getLastError();
        if ($error instanceof HttpClientError) {
            $id = \sprintf('%s.%s', $service->getName(), $error->getCode());
            if ($this->isTransDefined($id, 'translator')) {
                $error->setMessage($this->trans($id, [], 'translator'));
            }
            $message = $this->trans('translator.title') . '|';
            $message .= $this->trans('translator.languages_error');
            $message .= $this->trans('translator.last_error', [
                '%code%' => $error->getCode(),
                '%message%' => $error->getMessage(),
            ]);
            $this->error($message);
            $error = true;
        }
        $parameters = [
            'service' => $service,
            'form' => $this->createForm(FormType::class),
            'translators' => $factory->getTranslators(),
            'language' => AbstractHttpClientService::getAcceptLanguage(),
            'languages' => $languages,
            'error' => $error,
        ];

        return $this->render('test/translate.html.twig', $parameters);
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

    private function getCategories(EntityManagerInterface $manager): array
    {
        /** @phpstan-var array<int, Category> $categories */
        $categories = $manager->getRepository(Category::class)
            ->getQueryBuilderByGroup()
            ->getQuery()
            ->getResult();
        $fn = static fn (Category $c): string => (string) $c->getGroupCode();

        return $this->groupBy($categories, $fn);
    }

    /**
     * @return array<array{code: string, name: string}>
     */
    private function getCurrencies(): array
    {
        /** @phpstan-var array<array{code: string, name: string}> $currencies */
        $currencies = \array_map(function (string $code): array {
            $name = \ucfirst(Currencies::getName($code));
            $symbol = Currencies::getSymbol($code);

            return [
                'code' => $code,
                'name' => "$name - $symbol",
            ];
        }, Currencies::getCurrencyCodes());

        $currencies = \array_filter(
            $currencies,
            static fn (array $currency): bool => !StringUtils::pregMatch('/\d|\(/', $currency['name'])
        );

        \usort($currencies, $this->sortCurrencies(...));

        return $currencies;
    }

    private function getProducts(EntityManagerInterface $manager): array
    {
        $products = $manager->getRepository(Product::class)
            ->findByGroup();
        $fn = static fn (Product $p): string => \sprintf('%s - %s', $p->getGroupCode(), $p->getCategoryCode());

        return $this->groupBy($products, $fn);
    }

    private function getStates(EntityManagerInterface $manager): array
    {
        /** @phpstan-var CalculationState[] $states */
        $states = $manager->getRepository(CalculationState::class)
            ->getQueryBuilderByEditable()
            ->getQuery()
            ->getResult();
        $fn = static fn (CalculationState $state): string => $state->isEditable()
            ? 'calculationstate.list.editable_1'
            : 'calculationstate.list.editable_0';

        return $this->groupBy($states, $fn);
    }

    /**
     * @template T of AbstractProperty
     *
     * @phpstan-param AbstractParameters<T> $parameters
     * @phpstan-param class-string<FormTypeInterface<array>> $type
     */
    private function renderParameters(
        Request $request,
        AbstractParameters $parameters,
        string $type,
        string $success,
        array $templateParameters,
    ): Response {
        $options = ['default_values' => $parameters->getDefaultValues()];
        $form = $this->createForm($type, $parameters, $options);
        if ($this->handleRequestForm($request, $form)) {
            if (!$parameters->save()) {
                return $this->redirectToHomePage();
            }
            $response = $this->redirectToHomePage($success);
            $view = $parameters->getDisplay()->getDisplayMode();
            $this->updateCookie($response, TableInterface::PARAM_VIEW, $view);

            return $response;
        }

        $templateParameters['form'] = $form;

        return $this->render('test/parameter.html.twig', $templateParameters);
    }

    /**
     * @phpstan-param array{code: string, name: string} $a
     * @phpstan-param array{code: string, name: string} $b
     */
    private function sortCurrencies(array $a, array $b): int
    {
        return \strnatcasecmp($a['name'], $b['name']);
    }
}
