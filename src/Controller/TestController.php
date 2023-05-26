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
use App\Model\HttpClientError;
use App\Report\HtmlReport;
use App\Response\PdfResponse;
use App\Response\WordResponse;
use App\Service\AbstractHttpClientService;
use App\Service\CaptchaImageService;
use App\Service\MailerService;
use App\Service\RecaptchaService;
use App\Service\SearchService;
use App\Service\SwissPostService;
use App\Traits\CookieTrait;
use App\Traits\GroupByTrait;
use App\Traits\StrengthLevelTranslatorTrait;
use App\Translator\TranslatorFactory;
use App\Utils\FormatUtils;
use App\Validator\Captcha;
use App\Validator\Password;
use App\Validator\Strength;
use App\Word\HtmlDocument;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\FormError;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function Symfony\Component\String\u;

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Controller for tests.
 *
 * @psalm-import-type SearchType from SearchService
 */
#[AsController]
#[Route(path: '/test')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class TestController extends AbstractController
{
    use CookieTrait;
    use GroupByTrait;
    use StrengthLevelTranslatorTrait;

    /**
     * Test sending notification mail.
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
        $form = $helper->createForm();

        if ($this->handleRequestForm($request, $form)) {
            /** @psalm-var User $user */
            $user = $this->getUser();
            /** @psalm-var array{email: string, message: string, importance: Importance, attachments: UploadedFile[]}  $data */
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
     * Export a HTML page to PDF.
     */
    #[Route(path: '/pdf', name: 'test_pdf')]
    public function exportPdf(): PdfResponse
    {
        $content = $this->renderView('test/html_report.html.twig');
        $report = new HtmlReport($this, $content);
        $report->SetTitle($this->trans('test.html'), true);

        return $this->renderPdfDocument($report);
    }

    /**
     * Export an HTML page to Word.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    #[Route(path: '/word', name: 'test_word')]
    public function exportWord(): WordResponse
    {
        $content = $this->renderView('test/html_report.html.twig');
        $doc = new HtmlDocument($this, $content);
        $doc->setTitleTrans('test.html');

        return $this->renderWordDocument($doc);
    }

    /**
     * Test notifications.
     */
    #[Route(path: '/notifications', name: 'test_notifications')]
    public function notifications(): Response
    {
        return $this->render('test/notification.html.twig', ['positions' => MessagePosition::sorted()]);
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
        $listener = function (PreSubmitEvent $event) use ($options, $passwordConstraint, $strengthConstraint): void {
            /** @psalm-var array $data */
            $data = $event->getData();
            foreach ($options as $option) {
                $property = u($option)->trimPrefix('security_')->toString();
                $passwordConstraint->{$property} = (bool) ($data[$option] ?? false);
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
        $helper->listenerPreSubmit($listener);
        $helper->field('input')
            ->widgetClass('password-strength')
            ->updateAttribute('data-strength', StrengthLevel::MEDIUM->value)
            ->updateAttribute('data-url', $this->generateUrl(route: 'ajax_password', referenceType: UrlGeneratorInterface::ABSOLUTE_URL))
            ->minLength(User::MIN_PASSWORD_LENGTH)
            ->maxLength(User::MAX_USERNAME_LENGTH)
            ->constraints(new Length(min: User::MIN_PASSWORD_LENGTH, max: User::MAX_USERNAME_LENGTH), $passwordConstraint, $strengthConstraint)
            ->addTextType();
        foreach ($options as $option) {
            $helper->field($option)
                ->notRequired()
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
            /** @psalm-var array<string, mixed> $data */
            $data = $form->getData();
            $message = $this->trans('password.success');
            $message .= '<ul>';
            foreach ($options as $option) {
                if ($data[$option]) {
                    $message .= '<li>' . $this->trans("password.$option") . '</li>';
                }
            }
            /** @psalm-var StrengthLevel $level */
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
    #[Route(path: '/recaptcha', name: 'test_recaptcha')]
    public function recaptcha(Request $request, RecaptchaService $service): Response
    {
        $data = [
            'subject' => 'My subject',
            'message' => 'My message',
        ];
        $helper = $this->createFormHelper('user.fields.', $data);
        $helper->field('subject')->addTextType()
            ->field('message')->addTextType()
            ->field('recaptcha')->addHiddenType()
            ->getBuilder()->setAttribute('block_name', '');
        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            $response = (string) $form->get('recaptcha')->getData();
            $result = $service->verify($request, $response);
            if ($result->isSuccess()) {
                /** @psalm-var array<string, string[]|string> $values */
                $values = $result->toArray();
                $html = $this->formatRecaptchaResult($values);

                return $this->redirectToHomePage($html);
            }
            $errors = $service->translateErrors($result->getErrorCodes());
            foreach ($errors as $error) {
                $form->addError(new FormError($error));
            }
        }

        return $this->render('test/recaptcha.html.twig', [
            'action' => $service->getAction(),
            'key' => $service->getSiteKey(),
            'form' => $form,
        ]);
    }

    #[Route(path: '/search', name: 'test_search')]
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
            'result' => [] !== $rows,
            'query' => $all ?? $zip ?? $city ?? $street ?? '',
            'limit' => $limit,
            'count' => \count($rows),
            'rows' => $rows,
        ];

        return $this->json($data);
    }

    /**
     * Show the translation page.
     *
     * @throws \Psr\Container\ContainerExceptionInterface if the service is not found
     */
    #[Route(path: '/translate', name: 'test_translate')]
    public function translate(TranslatorFactory $factory): Response
    {
        $service = $factory->getSessionService();
        $languages = $service->getLanguages();
        if (($error = $service->getLastError()) instanceof HttpClientError) {
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
            'form' => $this->createForm(),
            'translators' => $factory->getTranslators(),
            'language' => AbstractHttpClientService::getAcceptLanguage(),
            'languages' => $languages,
            'error' => $error,
        ];

        return $this->render('test/translate.html.twig', $parameters);
    }

    #[Route(path: '/tree', name: 'test_tree')]
    public function tree(Request $request, EntityManagerInterface $manager): Response
    {
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
     * @psalm-param array<string, string[]|string> $values
     */
    private function formatRecaptchaResult(array $values): string
    {
        $values = \array_filter($values);
        $html = '<table class="table table-borderless table-sm mb-0"><tbody>';
        foreach ($values as $key => $value) {
            if (\is_array($value)) {
                $value = \implode('<br>', $value);
            } elseif ('challenge_ts' === $key && false !== $time = \strtotime($value)) {
                $value = FormatUtils::formatDateTime($time, null, \IntlDateFormatter::MEDIUM);
            }
            $html .= "<tr><td>$key</td><td>:</td><td>$value</td></tr>";
        }

        return $html . '</tbody></table>';
    }

    private function getCategories(EntityManagerInterface $manager): array
    {
        /** @psalm-var array<int, Category> $categories */
        $categories = $manager->getRepository(Category::class)
            ->getQueryBuilderByGroup()
            ->getQuery()
            ->getResult();
        $fn = static fn (Category $c): string => (string) $c->getGroupCode();

        return $this->groupBy($categories, $fn);
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    private function getCurrencies(): array
    {
        /** @psalm-var array<int, array{code: string, name: string}> $currencies */
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
        $products = $manager->getRepository(Product::class)->findAllByGroup();
        $fn = static fn (Product $p): string => \sprintf('%s - %s', $p->getGroupCode(), $p->getCategoryCode());

        return $this->groupBy($products, $fn);
    }

    private function getStates(EntityManagerInterface $manager): array
    {
        /** @psalm-var array<int, CalculationState> $states */
        $states = $manager->getRepository(CalculationState::class)
            ->getQueryBuilderByEditable()
            ->getQuery()
            ->getResult();
        $fn = static fn (CalculationState $state): string => $state->isEditable() ? 'calculationstate.list.editable' : 'calculationstate.list.not_editable';

        return $this->groupBy($states, $fn);
    }
}
