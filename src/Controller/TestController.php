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

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Product;
use App\Entity\User;
use App\Enums\Importance;
use App\Enums\MessagePosition;
use App\Enums\StrengthLevel;
use App\Form\Type\AlphaCaptchaType;
use App\Form\Type\CaptchaImageType;
use App\Form\Type\SimpleEditorType;
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\RoleInterface;
use App\Report\HtmlReport;
use App\Repository\CalculationRepository;
use App\Response\PdfResponse;
use App\Service\AbstractHttpClientService;
use App\Service\AkismetService;
use App\Service\CaptchaImageService;
use App\Service\FakerService;
use App\Service\IpStackService;
use App\Service\MailerService;
use App\Service\SearchService;
use App\Service\SwissPostService;
use App\Traits\StrengthLevelTranslatorTrait;
use App\Translator\TranslatorFactory;
use App\Util\DateUtils;
use App\Util\FormatUtils;
use App\Util\Utils;
use App\Validator\Captcha;
use App\Validator\Password;
use App\Validator\Strength;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use ReCaptcha\ReCaptcha;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Controller for tests.
 */
#[AsController]
#[Route(path: '/test')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class TestController extends AbstractController
{
    use StrengthLevelTranslatorTrait;

    /**
     * Show analog clock.
     */
    #[Route(path: '/clock', name: 'test_clock')]
    public function clock(Request $request): Response
    {
        $session = $request->getSession();
        $dark = (bool) $session->get('clock_dark', false);
        if ($request->request->has('dark')) {
            $dark = \filter_var($request->request->get('dark', $dark), \FILTER_VALIDATE_BOOLEAN);
            $session->set('clock_dark', $dark);

            return $this->jsonTrue(['dark' => $dark]);
        }

        return $this->renderForm('test/clock.html.twig', ['dark' => $dark]);
    }

    /**
     * Test sending notification mail.
     *
     * @throws \ReflectionException
     */
    #[Route(path: '/editor', name: 'test_editor')]
    public function editor(Request $request, MailerService $service, LoggerInterface $logger): Response
    {
        $data = [
            'email' => $this->getUserEmail(),
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

        // handle request
        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            $user = $this->getUser();
            if ($user instanceof User) {
                /** @var array{email: string, message: string, importance: Importance, attachments: UploadedFile[]}  $data */
                $data = $form->getData();
                $email = $data['email'];
                $message = $data['message'];
                $importance = $data['importance'];
                $attachments = $data['attachments'];

                try {
                    $service->sendNotification($email, $user, $message, $importance, $attachments);
                    $this->successTrans('user.comment.success');

                    return $this->redirectToHomePage();
                } catch (TransportExceptionInterface $e) {
                    return $this->renderFormException('user.comment.error', $e, $logger);
                }
            }
        }

        return $this->renderForm('test/editor.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Export a HTML page to PDF.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/html', name: 'test_html')]
    public function html(): PdfResponse
    {
        // get content
        $content = $this->renderView('test/html_report.html.twig');

        // create report
        $report = new HtmlReport($this);
        $report->setContent($content)
            ->SetTitle($this->trans('test.html'), true);

        // render
        return $this->renderPdfDocument($report);
    }

    /**
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/ipstack', name: 'test_ipstack')]
    public function ipStack(Request $request, IpStackService $service): JsonResponse
    {
        $result = $service->getIpInfo($request);
        if ($lastError = $service->getLastError()) {
            return $this->json($lastError);
        }

        return $this->json([
            'result' => true,
            'response' => $result,
        ]);
    }

    /**
     * Test notifications.
     */
    #[Route(path: '/notifications', name: 'test_notifications')]
    public function notifications(): Response
    {
        return $this->renderForm('test/notification.html.twig', ['positions' => MessagePosition::sorted()]);
    }

    /**
     * Test password validation.
     *
     * @throws \Exception
     */
    #[Route(path: '/password', name: 'test_password')]
    public function password(Request $request, CaptchaImageService $service): Response
    {
        $options = PropertyServiceInterface::PASSWORD_OPTIONS;
        $passwordConstraint = new Password(['all' => true]);
        $strengthConstraint = new Strength(StrengthLevel::MEDIUM);
        $listener = function (FormEvent $event) use ($options, $passwordConstraint, $strengthConstraint): void {
            /** @psalm-var array $data */
            $data = $event->getData();
            foreach ($options as $option) {
                $passwordConstraint->{$option} = (bool) ($data[$option] ?? false);
            }
            $strength = (int) $data['level'];
            $strengthConstraint->minimum = StrengthLevel::tryFrom($strength) ?? StrengthLevel::NONE;
        };

        $data = [
            'input' => 'aB123456#*/82568A',
            'level' => StrengthLevel::MEDIUM,
        ];
        foreach ($options as $option) {
            $data[$option] = true;
        }

        $helper = $this->createFormHelper('password.', $data);
        $helper->addPreSubmitListener($listener);
        $helper->field('input')
            ->widgetClass('password-strength')
            ->updateAttribute('data-strength', StrengthLevel::MEDIUM->value)
            ->updateAttribute('data-url', $this->generateUrl(route: 'ajax_password', referenceType: UrlGeneratorInterface::ABSOLUTE_URL))
            ->minLength(6)
            ->constraints(new Length(['min' => 6]), $passwordConstraint, $strengthConstraint)
            ->addTextType();
        foreach ($options as $option) {
            $helper->field($option)
                ->notRequired()
                ->addCheckboxType();
        }
        $helper->field('level')
            ->label('password.strength_level')
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
            /** @psalm-var array<string, mixed> $data */
            $data = $form->getData();
            $message = $this->trans('password.success');
            $message .= '<ul>';

            // options
            foreach ($options as $option) {
                if ($data[$option]) {
                    $message .= '<li>' . $this->trans("password.$option") . '</li>';
                }
            }

            /** @psalm-var StrengthLevel $level */
            $level = $data['level'];
            if (StrengthLevel::NONE !== $level) {
                $message .= '<li>';
                $message .= $this->trans('password.strength_level');
                $message .= ' : ';
                $message .= $this->translateLevel($level);
                $message .= '</li>';
            }
            $message .= '</ul>';

            return $this->success($message)
                ->redirectToHomePage();
        }

        return $this->renderForm('test/password.html.twig', ['form' => $form]);
    }

    /**
     * Display the reCaptcha.
     */
    #[Route(path: '/recaptcha', name: 'test_recaptcha')]
    public function recaptcha(
        Request $request,
        #[Autowire('%google_recaptcha_site_key%')]
        string $siteKey,
        #[Autowire('%google_recaptcha_secret_key%')]
        string $secretKey,
        #[Autowire('%kernel.debug%')]
        bool $isDebug
    ): Response {
        $data = [
            'subject' => 'My subject',
            'message' => 'My message',
        ];

        $action = 'login';
        $helper = $this->createFormHelper('user.fields.', $data);
        $helper->getBuilder()->setAttribute('block_name', '');

        $helper->field('subject')
            ->addTextType();
        $helper->field('message')
            ->addTextType();
        $helper->field('recaptcha')
            ->addHiddenType();

        // render
        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            /** @psalm-var array $data */
            $data = $form->getData();
            $response = (string) $data['recaptcha'];
            $hostname = (string) $request->server->get('SERVER_NAME');
            $remoteIp = (string) $request->server->get('REMOTE_ADDR');
            $expectedHostName = $isDebug ? $remoteIp : $hostname;

            // verify
            $recaptcha = new ReCaptcha($secretKey);
            $recaptcha->setExpectedHostname($expectedHostName)
                ->setExpectedAction($action)
                ->setChallengeTimeout(60)
                ->setScoreThreshold(0.5);
            $result = $recaptcha->verify($response, $remoteIp);

            // OK?
            if ($result->isSuccess()) {
                /** @psalm-var array<string, mixed> $values */
                $values = $result->toArray();
                $html = '<table class="table table-borderless table-sm mb-0">';

                /** @psalm-var string[]|string|null $value */
                foreach ($values as $key => $value) {
                    if (empty($value)) {
                        continue;
                    }
                    if (\is_array($value)) {
                        $value = \implode('<br>', $value);
                    } elseif ('challenge_ts' === $key && false !== $time = \strtotime($value)) {
                        $value = FormatUtils::formatDateTime($time, null, \IntlDateFormatter::MEDIUM);
                    }
                    $html .= "<tr><td>$key</td><td>:</td><td>$value</td></tr>";
                }
                $html .= '</table>';
                $this->success($html);

                return $this->redirectToHomePage();
            }

            // add errors
            $errorCodes = \array_map(fn (mixed $code): string => $this->trans("recaptcha.$code", [], 'validators'), $result->getErrorCodes());
            if (empty($errorCodes)) {
                $errorCodes[] = $this->trans('recaptcha.unknown-error', [], 'validators');
            }
            foreach ($errorCodes as $code) {
                $form->addError(new FormError($code));
            }
        }

        return $this->renderForm('test/recaptcha.html.twig', [
            'google_recaptcha_site_key' => $siteKey,
            'google_recaptcha_action' => $action,
            'form' => $form,
        ]);
    }

    /**
     * Search zip codes, cities and streets from Switzerland.
     */
    #[Route(path: '/swiss', name: 'test_swiss')]
    public function swiss(Request $request, SwissPostService $service): Response
    {
        $all = $this->getRequestString($request, 'all');
        $zip = $this->getRequestString($request, 'zip');
        $city = $this->getRequestString($request, 'city');
        $street = $this->getRequestString($request, 'street');

        $limit = $this->getRequestInt($request, 'limit', 25);

        if (null !== $all) {
            $rows = $service->findAll($all, $limit);
        } elseif (null !== $zip) {
            $rows = $service->findZip($zip, $limit);
        } elseif (null !== $city) {
            $rows = $service->findCity($city, $limit);
        } elseif (null !== $street) {
            $rows = $service->findStreet($street, $limit);
        } else {
            $rows = [];
        }

        $data = [
            'result' => !empty($rows),
            'query' => $all ?? $zip ?? $city ?? $street ?? '',
            'limit' => $limit,
            'count' => \count($rows),
            'rows' => $rows,
        ];

        return $this->json($data);
    }

    /**
     * Display calculations in a timeline.
     *
     * @throws \Exception
     */
    #[Route(path: '/timeline', name: 'test_timeline')]
    public function timeline(Request $request, CalculationRepository $repository): Response
    {
        $max_date = $repository->getMaxDate()?->format('Y-m-d') ?? 'today';
        $interval = (string) $this->getRequestString($request, 'interval', 'P1W');
        $to = new \DateTime((string) $this->getRequestString($request, 'date', $max_date));
        $max_date = new \DateTime($max_date);
        $from = DateUtils::sub($to, $interval);
        $calculations = $repository->getByInterval($from, $to);
        $data = Utils::groupBy($calculations, fn (Calculation $c) => FormatUtils::formatDate($c->getDate(), \IntlDateFormatter::LONG));

        $today = new \DateTime('today');
        $previous = DateUtils::sub($to, $interval);
        $next = DateUtils::add($to, $interval);

        $parameters = [
            'max_date' => $max_date->format('Y-m-d'),
            'date' => $to->format('Y-m-d'),
            'interval' => $interval,
            'today' => $today->format('Y-m-d'),
            'previous' => $previous->format('Y-m-d'),
            'next' => $next->format('Y-m-d'),
            'count' => \count($calculations),
            'data' => $data,
            'from' => $from,
            'to' => $to,
        ];

        return $this->renderForm('test/timeline.html.twig', $parameters);
    }

    /**
     * Show the translation page.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/translate', name: 'test_translate')]
    public function translate(TranslatorFactory $factory): Response
    {
        // get service
        $service = $factory->getSessionService();

        // get languages
        $languages = $service->getLanguages();

        // check error
        if ($error = $service->getLastError()) {
            // translate message
            $id = $service->getDefaultIndexName() . '.' . $error['code'];
            if ($this->isTransDefined($id, 'translator')) {
                $error['message'] = $this->trans($id, [], 'translator');
            }
            $message = $this->trans('translator.title') . '|';
            $message .= $this->trans('translator.languages_error');
            $message .= $this->trans('translator.last_error', [
                '%code%' => $error['code'],
                '%message%' => $error['message'],
                ]);
            $this->error($message);
            $error = true;
        }

        // form and parameters
        $parameters = [
            'form' => $this->createForm(),
            'service' => $service,
            'translators' => $factory->getTranslators(),
            'language' => AbstractHttpClientService::getAcceptLanguage(),
            'languages' => $languages,
            'error' => $error,
        ];

        return $this->renderForm('test/translate.html.twig', $parameters);
    }

    #[Route(path: '/tree', name: 'test_tree')]
    public function tree(Request $request, EntityManagerInterface $manager): Response
    {
        // JSON?
        if ($request->isXmlHttpRequest()) {
            $count = 0;
            $nodes = [];
            $groups = $manager->getRepository(Group::class)->findAllByCode();
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

            // root
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

        return $this->renderForm('test/treeview.html.twig', [
            'categories' => $this->getCategories($manager),
            'products' => $this->getProducts($manager),
            'states' => $this->getStates($manager),
            'currencies' => $this->getCurrencies(),
            'countries' => Countries::getNames(),
        ]);
    }

    /**
     * @throws \ReflectionException
     */
    #[Route(path: '/union', name: 'test_union')]
    public function union(Request $request, SearchService $service): JsonResponse
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
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    #[Route(path: '/spam', name: 'test_spam')]
    public function verifyAkismetComment(AkismetService $akismetService, FakerService $fakerService): JsonResponse
    {
        $generator = $fakerService->getGenerator();
        $comment = $generator->realText(145);
        $value = $akismetService->verifyComment($comment);
        if ($lastError = $akismetService->getLastError()) {
            return $this->json($lastError);
        }

        return $this->json([
            'comment' => $comment,
            'spam' => $value,
        ]);
    }

    /**
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/verify', name: 'test_verify')]
    public function verifyAkismetKey(AkismetService $service): JsonResponse
    {
        if (!$service->verifyKey()) {
            return $this->json($service->getLastError());
        }

        return $this->json(['valid_key' => true]);
    }

    private function getCategories(EntityManagerInterface $manager): array
    {
        $categories = $manager->getRepository(Category::class)
            ->getQueryBuilderByGroup()
            ->getQuery()
            ->getResult();

        return Utils::groupBy($categories, fn (Category $c): string => (string) $c->getGroupCode());
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    private function getCurrencies(): array
    {
        /** @var array<int, array{code: string, name: string}> $currencies */
        $currencies = \array_map(function (string $code): array {
            $name = \ucfirst(Currencies::getName($code));
            $symbol = Currencies::getSymbol($code);

            return [
                'code' => $code,
                'name' => "$name - $symbol",
            ];
        }, Currencies::getCurrencyCodes());

        $currencies = \array_filter($currencies, static fn (array $currency): bool => 0 === \preg_match('/\d|\(/', $currency['name']));

        \usort($currencies, static fn (array $left, array $right): int => \strnatcasecmp((string) $left['name'], (string) $right['name']));

        return $currencies;
    }

    private function getProducts(EntityManagerInterface $manager): array
    {
        $products = $manager->getRepository(Product::class)
            ->findAllByGroup();

        return Utils::groupBy($products, fn (Product $p): string => \sprintf('%s - %s', (string) $p->getGroupCode(), (string) $p->getCategoryCode()));
    }

    private function getStates(EntityManagerInterface $manager): array
    {
        $states = $manager->getRepository(CalculationState::class)
            ->getQueryBuilderByEditable()
            ->getQuery()
            ->getResult();

        return Utils::groupBy($states, fn (CalculationState $state): string => $state->isEditable() ? 'calculationstate.list.editable' : 'calculationstate.list.not_editable');
    }
}
