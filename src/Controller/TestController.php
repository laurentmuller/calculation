<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Form\Admin\ParametersType;
use App\Form\FormHelper;
use App\Form\Type\CaptchaImage;
use App\Form\Type\MinStrengthType;
use App\Pdf\PdfResponse;
use App\Report\HtmlReport;
use App\Repository\CalculationStateRepository;
use App\Service\CaptchaImageService;
use App\Service\HttpClientService;
use App\Service\SearchService;
use App\Service\SwissPostService;
use App\Service\ThemeService;
use App\Translator\TranslatorFactory;
use App\Validator\Captcha;
use App\Validator\Password;
use ReCaptcha\ReCaptcha;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for tests.
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

        return $this->render('test/flex.html.twig', [
            'items' => $items,
        ]);
    }

    /**
     * Export the a HTML page to PDF.
     *
     * @Route("/html", name="test_html")
     */
    public function html(): PdfResponse
    {
        // get content
        $ontent = $this->renderView('test/html_report.html.twig');

        // create report
        $report = new HtmlReport($this);
        $report->setDebug(false)
            ->setContent($ontent)
            ->SetTitle($this->trans('test.html'), true);

        // render
        return $this->renderDocument($report);
    }

    /**
     * Test notifications.
     *
     * @Route("/notifications", name="test_notifications")
     */
    public function notifications(): Response
    {
        $data = [
            'position' => $this->getApplication()->getMessagePosition(),
            'timeout' => $this->getApplication()->getMessageTimeout(),
        ];

        return $this->render('test/notification.html.twig', $data);
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
            $constraint->minstrength = (int) ($data['minstrength'] ?? -1);
        };

        // default values
        $data = [
            'input' => '123456',
            'minstrength' => 2,
        ];
        foreach ($options as $option) {
            $data[$option] = true;
        }

        // form
        $helper = $this->createFormHelper('password.', $data);
        $helper->addEventListener(FormEvents::PRE_SUBMIT, $listener);

        $helper->field('input')
            ->className('password-strength')
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
            ->updateOption('image', $service->generateImage(false))
            ->updateOption('constraints', [
                new NotBlank(),
                new Captcha(),
            ])
            ->label('captcha.label')
            ->add(CaptchaImage::class);

        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            return $this->succes($this->trans('password.success'))
                ->redirectToHomePage();
        }

        return $this->render('test/password.html.twig', [
            'form' => $form->createView(),
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
            $secret = $this->getParameter('google_recaptcha_secret');

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
                        } elseif ('challenge_ts' === $key && -1 !== $time = \strtotime($value)) {
                            $value = $this->localeDateTime($time, null, \IntlDateFormatter::MEDIUM);
                        }
                        $html .= "<tr><td>{$key}<td><td>:</td><td>{$value}</td></tr>";
                    }
                }
                $html .= '</table>';
                $this->succes('reCAPTCHA|' . $html);

                return $this->redirectToHomePage();
            }

            // translate errors
            $errorCodes = \array_map(function ($code) use ($translator) {
                return $translator->trans("recaptcha.{$code}", [], 'validators');
            }, $result->getErrorCodes());
            if (empty($errorCodes)) {
                $errorCodes[] = $translator->trans('recaptcha.unknown-error', [], 'validators');
            }

            foreach ($errorCodes as $code) {
                $form->addError(new FormError($code));
            }
        }

        return $this->render('test/recaptcha.html.twig', [
            'form' => $form->createView(),
            'recaptcha_action' => $recaptcha_action,
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
     * Display Tinymce editor.
     *
     * @Route("/tinymce", name="test_tinymce")
     */
    public function tinymce(Request $request, ThemeService $service): Response
    {
        $data = [
            'email' => 'bibi@bibi.nu',
            'message' => '',
        ];

        // create form
        $helper = $this->createFormHelper('user.fields.', $data);

        $helper->field('email')
            ->addEmailType();

        $dark = $service->getCurrentTheme()->isDark();
        $helper->field('message')
            ->updateAttribute('minlength', 10)
            ->updateAttribute('data-skin', $dark ? 'oxide-dark' : 'oxide')
            ->className('must-validate')
            ->add(TextareaType::class);

        // handle request
        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            $data = $form->getData();
            $message = 'Message :<br>' . (string) $data['message'];
            $this->succes($message);

            return $this->redirectToHomePage();
        }

        return $this->render('test/tinymce.html.twig', [
            'form' => $form->createView(),
        ]);
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
            'form' => $this->getForm()->createView(),
            'language' => HttpClientService::getAcceptLanguage(true),
            'languages' => $languages,
            'services' => $factory->getServices(),
            'service_name' => $service::getName(),
            'service_url' => $service::getApiUrl(),
            'error' => $error,
        ];

        return $this->render('test/translate.html.twig', $parameters);
    }

    /**
     * @Route("/union", name="test_union")
     */
    public function union(Request $request, SearchService $service): JsonResponse
    {
        $query = $request->get('query');
        $limit = (int) $request->get('limit', 25);
        $offset = (int) $request->get('offset', 0);

        $count = $service->count($query);
        $results = $service->search($query, $limit, $offset);

        foreach ($results as &$row) {
            $type = \strtolower($row[SearchService::COLUMN_TYPE]);
            $field = $row[SearchService::COLUMN_FIELD];
            $row['entityName'] = $this->trans("{$type}.name");
            $row['fieldName'] = $this->trans("{$type}.fields.{$field}");

            $content = $row[SearchService::COLUMN_CONTENT];
            switch ("{$type}.{$field}") {
                case 'calculation.id':
                    $content = $this->localeId((int) $content);
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
            'offset' => $offset,
            'limit' => $limit,
            'total' => $count,
            'filtered' => \count($results),
            'results' => $results,
        ];

        return $this->json($data);
    }
}
