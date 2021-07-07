<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Calculation;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Group;
use App\Form\Admin\ParametersType;
use App\Form\FormHelper;
use App\Form\Type\CaptchaImageType;
use App\Form\Type\ImportanceType;
use App\Form\Type\MinStrengthType;
use App\Form\Type\SimpleEditorType;
use App\Form\Type\TinyMceEditorType;
use App\Interfaces\StrengthInterface;
use App\Mime\NotificationEmail;
use App\Pdf\PdfResponse;
use App\Pdf\PdfTocDocument;
use App\Report\HtmlReport;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Repository\GroupRepository;
use App\Service\AbstractHttpClientService;
use App\Service\AkismetService;
use App\Service\CaptchaImageService;
use App\Service\FakerService;
use App\Service\IpStackService;
use App\Service\SearchService;
use App\Service\SwissPostService;
use App\Translator\TranslatorFactory;
use App\Util\DateUtils;
use App\Util\FormatUtils;
use App\Util\Utils;
use App\Validator\Captcha;
use App\Validator\Password;
use Psr\Log\LoggerInterface;
use ReCaptcha\ReCaptcha;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for tests.
 *
 * @author Laurent Muller
 *
 * @Route("/test")
 * @IsGranted("ROLE_SUPER_ADMIN")
 */
class TestController extends AbstractController
{
    /**
     * Update calculations with random customers.
     *
     * @Route("/flex", name="test_flex")
     */
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
     *
     * @Route("/html", name="test_html")
     */
    public function html(): PdfResponse
    {
        // get content
        $content = $this->renderView('test/html_report.html.twig');

        // create report
        $report = new HtmlReport($this);
        $report->setDebug(false)
            ->setContent($content)
            ->SetTitle($this->trans('test.html'), true);

        // render
        return $this->renderPdfDocument($report);
    }

    /**
     * @Route("/ipstack", name="test_ipstack")
     */
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
     * @Route("/notifications", name="test_notifications")
     */
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
     *
     * @Route("/password", name="test_password")
     */
    public function password(Request $request, CaptchaImageService $service): Response
    {
        // options
        $options = ParametersType::PASSWORD_OPTIONS;

        // constraint
        $constraint = new Password(['all' => true]);

        // listener
        $listener = function (FormEvent $event) use ($options, $constraint): void {
            $data = $event->getData();
            foreach ($options as $option) {
                $constraint->{$option} = (bool) ($data[$option] ?? false);
            }
            $constraint->minstrength = (int) ($data['minstrength'] ?? StrengthInterface::LEVEL_NONE);
        };

        // default values
        $data = [
            'input' => 'aB123456#*/82568A',
            'minstrength' => 2,
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
            ->updateOption('constraints', [
                new Length(['min' => 6]),
                $constraint,
            ])
            ->addTextType();

        foreach ($options as $option) {
            $helper->field($option)
                ->notRequired()
                ->addCheckboxType();
        }

        $helper->field('minstrength')
            ->add(MinStrengthType::class);

        $helper->field('captcha')
            ->label('captcha.label')
            ->updateOption('image', $service->generateImage(false))
            ->updateOption('constraints', [
                new NotBlank(),
                new Captcha(),
            ])
            ->add(CaptchaImageType::class);

        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            $data = $form->getData();
            $message = $this->trans('password.success');
            $message .= '<ul>';

            // options
            foreach ($options as $option) {
                if ($data[$option]) {
                    $message .= '<li>' . $this->trans("password.{$option}") . '</li>';
                }
            }

            // minimum strength
            if (StrengthInterface::LEVEL_NONE !== $data['minstrength']) {
                $message .= '<li>';
                $message .= $this->trans('password.minstrength');
                $message .= ' : ';
                $message .= Utils::translateLevel($this->translator, $data['minstrength']);
                $message .= '</li>';
            }

            $message .= '</ul>';

            return $this->succes($message)
                ->redirectToHomePage();
        }

        return $this->renderForm('test/password.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Display the reCaptcha.
     *
     * @Route("/recaptcha", name="test_recaptcha")
     */
    public function recaptcha(Request $request, TranslatorInterface $translator): Response
    {
        $data = [
            'subject' => 'My subject',
            'message' => 'My message',
        ];

        $recaptcha_action = 'login';
        $builder = $this->createFormBuilder($data);
        $builder->setAttribute('block_name', '');

        $helper = new FormHelper($builder, 'user.fields.');
        $helper->field('subject')
            ->addTextType();

        $helper->field('message')
            ->addTextType();

        $helper->field('recaptcha')
            ->addHiddenType();

        // render
        $form = $builder->getForm();
        if ($this->handleRequestForm($request, $form)) {
            // get values
            $data = $form->getData();
            $response = $data['recaptcha'];
            $hostname = $request->server->get('HTTP_HOST');
            $secret = $this->getStringParameter('google_recaptcha_secret_key');

            // verify
            $recaptcha = new ReCaptcha($secret);
            $recaptcha->setExpectedAction($recaptcha_action)
                ->setExpectedHostname($hostname)
                ->setChallengeTimeout(60)
                ->setScoreThreshold(0.5);
            $result = $recaptcha->verify($response);

            // OK?
            if ($result->isSuccess()) {
                $values = $result->toArray();
                $html = '<table class="table table-sm table-borderless alert-heading">';
                foreach ($values as $key => $value) {
                    //ISO format yyyy-MM-dd'T'HH:mm:ssZZ
                    if ($value) {
                        if (\is_array($value)) {
                            $value = \implode('<br>', $value);
                        } elseif ('challenge_ts' === $key && false !== $time = \strtotime($value)) {
                            $value = FormatUtils::formatDateTime($time, null, \IntlDateFormatter::MEDIUM);
                        }
                        $html .= "<tr><td>{$key}<td><td>:</td><td>{$value}</td></tr>";
                    }
                }
                $html .= '</table>';
                $this->succes('reCAPTCHA|' . $html);

                return $this->redirectToHomePage();
            }

            // translate errors
            $errorCodes = \array_map(function ($code) use ($translator): string {
                return $translator->trans("recaptcha.{$code}", [], 'validators');
            }, $result->getErrorCodes());
            if (empty($errorCodes)) {
                $errorCodes[] = $translator->trans('recaptcha.unknown-error', [], 'validators');
            }

            foreach ($errorCodes as $code) {
                $form->addError(new FormError($code));
            }
        }

        return $this->renderForm('test/recaptcha.html.twig', [
            'form' => $form,
            'recaptcha_action' => $recaptcha_action,
        ]);
    }

    /**
     * Test sending notification mail.
     *
     * @Route("/simple", name="test_simple")
     */
    public function simpleEditor(Request $request, MailerInterface $mailer, TranslatorInterface $translator, LoggerInterface $logger): Response
    {
        $data = [
            'email' => 'bibi@bibi.nu',
            'importance' => NotificationEmail::IMPORTANCE_LOW,
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

        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            $data = $form->getData();
            $email = (string) $data['email'];
            $importance = $translator->trans('importance.full.' . $data['importance']);
            $message = "<p style=\"font-weight: bold;\">$importance</p>" . $data['message'];

            try {
                /** @var \App\Entity\User $user */
                $user = $this->getUser();
                $comment = new Comment(true);
                $comment->setFromAddress(new Address($email))
                    ->setToUser($user)
                    ->setSubject($this->trans('user.comment.title'))
                    ->setMessage($message);
                $comment->send($mailer);

                $this->succesTrans('user.comment.success');

                return $this->redirectToHomePage();
            } catch (\Exception $e) {
                $logger->error($this->trans('user.comment.error'), [
                    'class' => Utils::getShortName($e),
                    'message' => $e->getMessage(),
                    'code' => (int) $e->getCode(),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                ]);

                return $this->renderForm('@Twig/Exception/exception.html.twig', [
                    'message' => $message,
                    'exception' => $e,
                ]);
            }
        }

        return $this->renderForm('test/simpleeditor.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Test service.
     *
     * @Route("/swiss", name="test_swiss")
     */
    public function swiss(Request $request, SwissPostService $service): Response
    {
        $zip = $request->get('zip');
        $city = $request->get('city');
        $street = $request->get('street');
        $limit = (int) $request->get('limit', 25);
        $transaction = (bool) $request->get('transaction', false);
        $import = (bool) $request->get('import', false);

        if ($zip) {
            $rows = $service->findZip($zip, $limit);
        } elseif ($city) {
            $rows = $service->findCity($city, $limit);
        } elseif ($street) {
            $rows = $service->findStreet($street, $limit);
        } elseif ($transaction) {
            $db = $service->getDatabase(false);
            if ($db->beginTransaction()) {
                $db->commitTransaction();
            }
            if ($db->beginTransaction()) {
                $db->rollbackTransaction();
            }
            $db->close();

            $rows = [];
        } elseif ($import) {
            $rows = $service->import();
        } else {
            $rows = [];
        }

        if ($import) {
            $data = $rows;
        } else {
            $data = [
                'result' => !empty($rows),
                'query' => $zip ?? $city ?? $street ?? '',
                'limit' => $limit,
                'count' => \count($rows),
                'rows' => $rows,
            ];
        }

        return $this->json($data);
    }

    /**
     * Display calculations in a timeline.
     *
     * @Route("/timeline", name="test_timeline")
     */
    public function timeline(Request $request, CalculationRepository $repository): Response
    {
        $interval = $request->get('interval', 'P1W');
        $to = new \DateTime($request->get('date', 'today'));
        $from = DateUtils::sub($to, $interval);
        $calculations = $repository->getByInterval($from, $to);
        $data = Utils::groupBy($calculations, function (Calculation $c) {
            return FormatUtils::formatDate($c->getDate(), \IntlDateFormatter::LONG);
        });

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
        ];

        return $this->renderForm('test/timeline.html.twig', $parameters);
    }

    /**
     * Display Tinymce editor.
     *
     * @Route("/tinymce", name="test_tinymce")
     */
    public function tinymce(Request $request): Response
    {
        $data = [
            'email' => 'bibi@bibi.nu',
            'message' => '',
        ];

        // create form
        $helper = $this->createFormHelper('user.fields.', $data);

        $helper->field('email')
            ->addEmailType();

        $helper->field('message')
            ->updateAttribute('minlength', 10)
            ->add(TinyMceEditorType::class);

        // handle request
        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            $data = $form->getData();
            $message = 'Message :<br>' . (string) $data['message'];
            $this->succes($message);

            return $this->redirectToHomePage();
        }

        return $this->renderForm('test/tinymce.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Test the PdfTocDocument.
     *
     * @Route("/toc", name="test_toc")
     */
    public function tocDocument(): PdfResponse
    {
        $doc = new PdfTocDocument();

        $doc->AddPage();
        $doc->Cell(0, 5, 'Cover', 0, 1, 'C');

        $doc->AddPage();
        $doc->tocStart();
        $doc->Cell(0, 5, 'TOC1', 0, 1, 'L');
        $doc->tocAddEntry('TOC1', 0);
        $doc->Cell(0, 5, 'TOC1.1', 0, 1, 'L');
        $doc->tocAddEntry('TOC1.1', 1);

        $doc->AddPage();
        $doc->Cell(0, 5, 'TOC2', 0, 1, 'L');
        $doc->tocAddEntry('TOC2', 0);

        $doc->AddPage();
        for ($i = 3; $i <= 25; ++$i) {
            if (0 === $i % 10) {
                $doc->AddPage();
            }
            $doc->Cell(0, 5, 'TOC' . $i, 0, 1, 'L');
            $doc->tocAddEntry('TOC' . $i, 0);
        }
        $doc->tocStop();
        $doc->tocOutput(2);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show the translation page.
     *
     * @Route("/translate", name="test_translate")
     */
    public function translate(TranslatorFactory $factory): Response
    {
        // get service
        $service = $factory->getSessionService();

        // get languages
        $languages = $service->getLanguages();

        // check error
        if ($error = $service->getLastError()) {
            // translate message
            $id = $service->getName() . '.' . $error['code'];
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
            'form' => $this->getForm(),
            'language' => AbstractHttpClientService::getAcceptLanguage(),
            'languages' => $languages,
            'services' => $factory->getServices(),
            'service_name' => $service::getName(),
            'service_url' => $service::getApiUrl(),
            'error' => $error,
        ];

        return $this->renderForm('test/translate.html.twig', $parameters);
    }

    /**
     * @Route("/tree", name="test_tree")
     */
    public function treeView(Request $request, GroupRepository $repository): Response
    {
        // JSON?
        if ($request->isXmlHttpRequest()) {
            $count = 0;

            /** @var Group[] $groups */
            $groups = $repository->findAllByCode();

            $nodes = [];
            foreach ($groups as $group) {
                $node = [
                    'id' => 'group-' . $group->getId(),
                    'text' => $group->getCode(),
                    'icon' => 'fas fa-code-branch fa-fw',
                    'badgeValue' => $group->countItems(),
                ];

                /** @var Category $category */
                foreach ($group->getCategories() as $category) {
                    $count += $category->countItems();
                    $node['nodes'][] = [
                        'id' => 'category-' . $category->getId(),
                        'text' => $category->getCode(),
                        'icon' => 'far fa-folder fa-fw',
                        'badgeValue' => $category->countItems(),
                    ];
                }

                $nodes[] = $node;
            }

            //root
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
            'countries' => Countries::getNames(),
        ]);
    }

    /**
     * @Route("/union", name="test_union")
     */
    public function union(Request $request, SearchService $service): JsonResponse
    {
        $query = $request->get('query');
        $entity = $request->get('entity');
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);

        $count = $service->count($query, $entity);
        $results = $service->search($query, $entity, $limit, $offset);

        foreach ($results as &$row) {
            $type = \strtolower($row[SearchService::COLUMN_TYPE]);
            $field = $row[SearchService::COLUMN_FIELD];
            $row['entityName'] = $this->trans("{$type}.name");
            $row['fieldName'] = $this->trans("{$type}.fields.{$field}");

            $content = $row[SearchService::COLUMN_CONTENT];
            switch ("{$type}.{$field}") {
                case 'calculation.id':
                    $content = FormatUtils::formatId((int) $content);
                    break;
                case 'calculation.overallTotal':
                case 'product.price':
                    $content = \number_format((float) $content, 2, '.', '');
                    break;
            }
            $row[SearchService::COLUMN_CONTENT] = $content;
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
     * @Route("/spam", name="test_spam")
     */
    public function verifyAkismetComment(AkismetService $akismetservice, FakerService $fakerService): JsonResponse
    {
        $faker = $fakerService->getFaker();
        $comment = $faker->realText(145, 2);
        $value = $akismetservice->verifyComment($comment);
        if ($lastError = $akismetservice->getLastError()) {
            return $this->json($lastError);
        }

        return $this->json([
            'comment' => $comment,
            'spam' => $value,
        ]);
    }

    /**
     * @Route("/verify", name="test_verify")
     */
    public function verifyAkismetKey(AkismetService $service): JsonResponse
    {
        if (false === $result = $service->verifyKey()) {
            return $this->json($service->getLastError());
        }

        return $this->json(['valid_key' => $result]);
    }
}
