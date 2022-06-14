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
use App\Entity\User;
use App\Form\Admin\ParametersType;
use App\Form\Type\AlphaCaptchaType;
use App\Form\Type\CaptchaImageType;
use App\Form\Type\ImportanceType;
use App\Form\Type\MinStrengthType;
use App\Form\Type\SimpleEditorType;
use App\Interfaces\StrengthInterface;
use App\Report\HtmlReport;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use App\Response\PdfResponse;
use App\Service\AbstractHttpClientService;
use App\Service\AkismetService;
use App\Service\CaptchaImageService;
use App\Service\FakerService;
use App\Service\IpStackService;
use App\Service\MailerService;
use App\Service\SearchService;
use App\Service\SwissPostService;
use App\Traits\StrengthTranslatorTrait;
use App\Translator\TranslatorFactory;
use App\Util\DateUtils;
use App\Util\FormatUtils;
use App\Util\Utils;
use App\Validator\Captcha;
use App\Validator\Password;
use Psr\Log\LoggerInterface;
use ReCaptcha\ReCaptcha;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Twig\Mime\NotificationEmail as NotificationEmailAlias;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Controller for tests.
 */
#[AsController]
#[IsGranted('ROLE_SUPER_ADMIN')]
#[Route(path: '/test')]
class TestController extends AbstractController
{
    use StrengthTranslatorTrait;

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
     */
    #[Route(path: '/editor', name: 'test_editor')]
    public function editor(Request $request, MailerService $service, LoggerInterface $logger): Response
    {
        $data = [
            'email' => $this->getUserEmail(),
            'importance' => NotificationEmailAlias::IMPORTANCE_MEDIUM,
        ];

        $helper = $this->createFormHelper('user.fields.', $data);
        $helper->field('email')
            ->addEmailType();
        $helper->field('importance')
            ->label('importance.name')
            ->add(ImportanceType::class);
        $helper->field('message')
            ->updateAttribute('minlength', 10)
            ->add(SimpleEditorType::class);

        // handle request
        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            $user = $this->getUser();
            if ($user instanceof User) {
                /** @psalm-var array $data */
                $data = $form->getData();
                $email = (string) $data['email'];
                $message = (string) $data['message'];
                $importance = (string) $data['importance'];

                try {
                    $service->sendNotification($email, $user, $message, $importance);
                    $this->successTrans('user.comment.success');

                    return $this->redirectToHomePage();
                } catch (TransportExceptionInterface $e) {
                    $message = $this->trans('user.comment.error');
                    $context = Utils::getExceptionContext($e);
                    $logger->error($message, $context);

                    return $this->renderForm('@Twig/Exception/exception.html.twig', [
                        'message' => $message,
                        'exception' => $e,
                    ]);
                }
            }
        }

        return $this->renderForm('test/editor.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Update calculations with random customers.
     */
    #[Route(path: '/flex', name: 'test_flex')]
    public function flex(CalculationStateRepository $repository): Response
    {
        $items = $repository->getSortedBuilder()
            ->getQuery()
            ->getResult();

        return $this->renderForm('test/flex.html.twig', [
            'items' => $items,
        ]);
    }

    /**
     * Export a HTML page to PDF.
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

    #[Route(path: '/ipstack', name: 'test_ipstack')]
    public function ipStrack(Request $request, IpStackService $service): JsonResponse
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
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/notifications', name: 'test_notifications')]
    public function notifications(): Response
    {
        $application = $this->getApplication();
        $data = [
            'position' => $application->getMessagePosition(),
            'timeout' => $application->getMessageTimeout(),
            'subtitle' => $application->isMessageSubTitle(),
        ];

        return $this->renderForm('test/notification.html.twig', $data);
    }

    /**
     * Test password validation.
     */
    #[Route(path: '/password', name: 'test_password')]
    public function password(Request $request, CaptchaImageService $service): Response
    {
        // options
        $options = ParametersType::PASSWORD_OPTIONS;

        // constraint
        $constraint = new Password(['all' => true]);

        // listener
        $listener = function (FormEvent $event) use ($options, $constraint): void {
            /** @psalm-var array $data */
            $data = $event->getData();
            foreach ($options as $option) {
                $constraint->{$option} = (bool) ($data[$option] ?? false);
            }
            $constraint->min_strength = (int) ($data['min_strength'] ?? StrengthInterface::LEVEL_NONE);
        };

        // default values
        $data = [
            'input' => 'aB123456#*/82568A',
            'min_strength' => StrengthInterface::LEVEL_MEDIUM,
        ];
        foreach ($options as $option) {
            $data[$option] = true;
        }

        // form
        $helper = $this->createFormHelper('password.', $data);
        $helper->addPreSubmitListener($listener);

        $helper->field('input')
            ->widgetClass('password-strength')
            ->minLength(6)
            ->constraints(new Length(['min' => 6]), $constraint)
            ->addTextType();

        foreach ($options as $option) {
            $helper->field($option)
                ->notRequired()
                ->addCheckboxType();
        }

        $helper->field('min_strength')
            ->add(MinStrengthType::class);

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
            /** @psalm-var array<bool|int> $data */
            $data = $form->getData();
            $message = $this->trans('password.success');
            $message .= '<ul>';

            // options
            foreach ($options as $option) {
                if ($data[$option]) {
                    $message .= '<li>' . $this->trans("password.$option") . '</li>';
                }
            }

            // minimum strength
            $min_strength = (int) $data['min_strength'];
            if (StrengthInterface::LEVEL_NONE !== $min_strength) {
                $message .= '<li>';
                $message .= $this->trans('password.min_strength');
                $message .= ' : ';
                $message .= $this->translateLevel($min_strength);
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
    public function recaptcha(Request $request, bool $isDebug): Response
    {
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
            $secret = $this->getStringParameter('google_recaptcha_secret_key');
            $expectedHostName = $isDebug ? $remoteIp : $hostname;

            // verify
            $recaptcha = new ReCaptcha($secret);
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
            'google_recaptcha_site_key' => $this->getStringParameter('google_recaptcha_site_key'),
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
     */
    #[Route(path: '/timeline', name: 'test_timeline')]
    public function timeline(Request $request, CalculationRepository $repository): Response
    {
        $interval = (string) $this->getRequestString($request, 'interval', 'P1W');
        $to = new \DateTime((string) $this->getRequestString($request, 'date', 'today'));
        $from = DateUtils::sub($to, $interval);
        $calculations = $repository->getByInterval($from, $to);
        $data = Utils::groupBy($calculations, fn (Calculation $c) => FormatUtils::formatDate($c->getDate(), \IntlDateFormatter::LONG));

        $today = new \DateTime('today');
        $previous = DateUtils::sub($to, $interval);
        $next = DateUtils::add($to, $interval);

        $parameters = [
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
            $id = $service->getDefaultIndexName() . '.' . (string) $error['code'];
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
    public function tree(Request $request, GroupRepository $repository, CategoryRepository $categories, CalculationStateRepository $states): Response
    {
        // JSON?
        if ($request->isXmlHttpRequest()) {
            $count = 0;
            $nodes = [];
            $groups = $repository->findAllByCode();
            foreach ($groups as $group) {
                $node = [
                    'id' => 'group-' . (string) $group->getId(),
                    'text' => $group->getCode(),
                    'icon' => 'fas fa-code-branch fa-fw',
                    'badgeValue' => $group->countItems(),
                ];

                foreach ($group->getCategories() as $category) {
                    $count += $category->countItems();
                    $node['nodes'][] = [
                        'id' => 'category-' . (string) $category->getId(),
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
            'categories' => $categories->findBy([], ['code' => 'ASC']),
            'currencies' => $this->getCurrencies(),
            'countries' => Countries::getNames(),
            'states' => $states->getList(),
        ]);
    }

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

    #[Route(path: '/spam', name: 'test_spam')]
    public function verifyAkismetComment(AkismetService $akismetservice, FakerService $fakerService): JsonResponse
    {
        $generator = $fakerService->getGenerator();
        $comment = $generator->realText(145);
        $value = $akismetservice->verifyComment($comment);
        if ($lastError = $akismetservice->getLastError()) {
            return $this->json($lastError);
        }

        return $this->json([
            'comment' => $comment,
            'spam' => $value,
        ]);
    }

    #[Route(path: '/verify', name: 'test_verify')]
    public function verifyAkismetKey(AkismetService $service): JsonResponse
    {
        if (!$service->verifyKey()) {
            return $this->json($service->getLastError());
        }

        return $this->json(['valid_key' => true]);
    }

    private function getCurrencies(): array
    {
        $currencies = \array_map(function (string $code): array {
            $name = \ucfirst(Currencies::getName($code));
            $symbol = Currencies::getSymbol($code);

            return [
                'code' => $code,
                'name' => "$name - $symbol",
            ];
        }, Currencies::getCurrencyCodes());

        $currencies = \array_filter($currencies, fn (array $currency): bool => 0 === \preg_match('/\d|\(/', $currency['name']));

        \usort($currencies, fn (array $left, array $right): int => \strnatcasecmp((string) $left['name'], (string) $right['name']));

        return $currencies;
    }
}
