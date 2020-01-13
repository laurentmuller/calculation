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

use App\Calendar\CalculationsDay;
use App\Calendar\Calendar;
use App\Calendar\CalendarService;
use App\Entity\Calculation;
use App\Entity\CalculationItem;
use App\Entity\CalculationState;
use App\Entity\Customer;
use App\Entity\Product;
use App\Form\CaptchaImage;
use App\Form\FormHelper;
use App\Pdf\PdfResponse;
use App\Report\HtmlReport;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Service\CalculationService;
use App\Service\CaptchaImageService;
use App\Service\FakerService;
use App\Service\HttpClientService;
use App\Service\SearchService;
use App\Service\SwissPostService;
use App\Translator\TranslatorFactory;
use App\Utils\DateUtils;
use App\Validator\Captcha;
use App\Validator\Password;
use Doctrine\ORM\EntityManagerInterface;
use ReCaptcha\ReCaptcha;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for tests.
 *
 * @Route("/test")
 * @IsGranted("ROLE_SUPER_ADMIN")
 */
class TestController extends BaseController
{
    /**
     * Display a calendar.
     *
     * @Route("/calendar/{year}", name="test_calendar", requirements={"year": "\d+" })
     *
     * @param Request the request the get parameters
     * @param CalendarService       $service    the service to generate calendar model
     * @param CalculationRepository $repository the repository to query
     * @param int|null              $year       the year to search for or <code>null</code> for the current year
     */
    public function calendar(Request $request, CalendarService $service, CalculationRepository $repository, $year = null): Response
    {
        $year = DateUtils::completYear((int) ($year ?: \date('Y')));
        $service->setModels(null, null, null, CalculationsDay::class);
        $calendar = $service->generate($year);

        // $repository = $this->getDoctrine()->getRepository(Calculation::class);
        $calculations = $repository->getForYear($year);
        $this->merge($calendar, $calculations);

        return $this->render('test/calendar.html.twig', [
            'calendar' => $calendar,
            'calculations' => $calculations,
        ]);
    }

    /**
     * Create calculations with random data.
     *
     * @Route("/create/calculations/{count}", name="test_create_calculations", requirements={"count": "\d+" })
     */
    public function createCalculations(EntityManagerInterface $manager, CalculationService $service, FakerService $fakerService, int $count = 1): JsonResponse
    {
        /** @var \Faker\Generator $faker */
        $faker = $fakerService->getFaker();

        // 6 months before
        $startDate = new \DateTime();
        $startDate->modify('first day of this month');
        $interval = new \DateInterval('P6M');
        $startDate = $startDate->sub($interval);

        // end of next month
        $endDate = new \DateTime();
        $endDate->modify('last day of this month');
        $interval = new \DateInterval('P1M');
        $endDate = $endDate->add($interval);

        // load data
        $products = $manager->getRepository(Product::class)->findAll();
        $states = $manager->getRepository(CalculationState::class)->findBy([
            'editable' => true,
        ]);

        $calculations = [];
        for ($i = 0; $i < $count; ++$i) {
            // calculation
            $calculation = new Calculation();
            $calculation->setDate($faker->dateTimeBetween($startDate, $endDate))
                ->setDescription($faker->catchPhrase())
                ->setUserMargin($faker->randomFloat(2, 0, 0.1))
                ->setState($faker->randomElement($states))
                ->setCustomer($faker->name);

            // items
            /** @var Product[] $itemProducts */
            $itemProducts = $faker->randomElements($products, $faker->numberBetween(5, 15));
            foreach ($itemProducts as $product) {
                // copy
                $item = CalculationItem::create($product)->setQuantity($faker->numberBetween(1, 10));

                // find group
                $category = $product->getCategory();
                $group = $calculation->findGroupOrCreate($category);

                // add
                $group->addItem($item);
            }

            // update
            $service->updateTotal($calculation);

            // save
            $manager->persist($calculation);
            $manager->flush();

            $calculations[] = [
                'id' => $this->localeId($calculation->getId()),
                'description' => $calculation->getDescription(),
                'customer' => $calculation->getCustomer(),
                'state' => $calculation->getState()->getCode(),
                'date' => $this->localeDate($calculation->getDate()),
                'total' => $this->localeAmount($calculation->getOverallTotal()),
            ];
        }

        $data = [
            'result' => true,
            'count' => \count($calculations),
            'calculations' => $calculations,
        ];

        return $this->json($data);
    }

    /**
     * Create customers with random data.
     *
     * @Route("/create/customers/{count}", name="test_create_customers", requirements={"count": "\d+" })
     */
    public function createCustomers(EntityManagerInterface $manager, FakerService $fakerService, int $count = 1): JsonResponse
    {
        /** @var \Faker\Generator $faker */
        $faker = $fakerService->getFaker();

        $genders = [
            \Faker\Provider\Person::GENDER_MALE,
            \Faker\Provider\Person::GENDER_FEMALE,
        ];
        $styles = [
            0,
            1,
            2,
        ];

        $customers = [];
        for ($i = 0; $i < $count; ++$i) {
            $style = $faker->randomElement($styles);
            $gender = $faker->randomElement($genders);

            $customer = new Customer();
            switch ($style) {
                case 0:
                    $customer->setCompany($faker->companyAndSuffix())
                        ->setEmail($faker->companyEmail);
                    break;

                case 1:
                    $customer->setTitle($faker->title($gender))
                        ->setFirstName($faker->firstName($gender))
                        ->setLastName($faker->lastName)
                        ->setEmail($faker->email);
                    break;

                default: // both
                    $customer->setCompany($faker->companyAndSuffix())
                        ->setFirstName($faker->firstName($gender))
                        ->setTitle($faker->title($gender))
                        ->setLastName($faker->lastName)
                        ->setEmail($faker->email);
                    break;
            }
            $customer->setAddress($faker->streetAddress)
                ->setZipCode($faker->postcode)
                ->setCity($faker->city);

            // save
            $manager->persist($customer);
            $manager->flush();

            $customers[] = [
                'id' => $customer->getId(),
                'company' => $customer->getCompany(),
                'firstName' => $customer->getFirstName(),
                'lastName' => $customer->getLastName(),
            ];
        }

        $data = [
            'result' => true,
            'count' => \count($customers),
            'customers' => $customers,
        ];

        return $this->json($data);
    }

    /**
     * Display the error (for test purpose).
     *
     * @Route("/error", name="test_error")
     */
    public function errorTest(): Response
    {
        try {
            $value = 0;
            $result = 10 / $value;
            if ($result) {
                return $this->redirectToHomePage();
            }
        } catch (\Exception $e) {
            // render view
            return $this->render('@Twig/Exception/exception.html.twig', [
                'message' => 'Simulation d\'une <b>erreur</b> pour tester cette page.',
                'exception' => $e,
            ]);
        }
    }

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
     * Display a month of a calendar.
     *
     * @Route("/month/{year}/{month}", name="test_month", requirements={"year": "\d+", "month": "\d+"})
     *
     * @param Request               $request    the request the get parameters
     * @param CalendarService       $service    the service to generate calendar model
     * @param CalculationRepository $repository the repository to query
     * @param int|null              $year       the year to search for or <code>null</code> for the current year
     * @param int|null              $month      the month to search for or <code>null</code> for the current month
     */
    public function month(Request $request, CalendarService $service, CalculationRepository $repository, $year = null, $month = null)
    {
        // check month
        $month = (int) ($month ?: \date('n'));
        if ($month < 1 || $month > 12) {
            throw $this->createNotFoundException($this->trans('test.invalid_month'));
        }
        $year = DateUtils::completYear((int) ($year ?: \date('Y')));
        $service->setModels(null, null, null, CalculationsDay::class);

        /** @var Calendar $calendar */
        $calendar = $service->generate($year);
        $currentMonth = $calendar->getMonths()[$month - 1];

        $calculations = $repository->getForMonth($year, $month);
        $this->merge($calendar, $calculations);

        return $this->render('test/month.html.twig', [
            'calendar' => $calendar,
            'month' => $currentMonth,
            'calculations' => $calculations,
        ]);
    }

    /**
     * Test notifications.
     *
     * @Route("/notifications", name="test_notifications")
     */
    public function notifications(Request $request): Response
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
        $options = [
            'letters',
            'numbers',
            'specialCharacter',
            'caseDiff',
            'email',
            'blackList',
            'pwned',
        ];

        // constraint
        $constraint = new Password();
        $constraint->allViolations = true;

        // default values
        $data = [
            'password' => '123456',
            'minStrength' => 2,
        ];
        foreach ($options as $option) {
            $data[$option] = true;
        }

        // listener
        $listener = function (FormEvent $event) use ($options, $constraint): void {
            $data = $event->getData();
            foreach ($options as $option) {
                $constraint->{$option} = (bool) ($data[$option] ?? false);
            }
            $constraint->minStrength = (int) ($data['minStrength'] ?? -1);
        };

        // form
        $builder = $this->createFormBuilder($data);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $listener);

        $helper = new FormHelper($builder);
        $helper->field('password')
            ->label('password.input')
            ->className('password-strength')
            ->updateOption('constraints', [$constraint])
            ->addTextType();

        foreach ($options as $option) {
            $helper->field($option)
                ->label("password.{$option}")
                ->notRequired()
                ->addCheckboxType();
        }

        $helper->field('minStrength')
            ->label('password.minStrength')
            ->addChoiceType([
                'password.strength_level.none' => -1,
                'password.strength_level.very_weak' => 0,
                'password.strength_level.weak' => 1,
                'password.strength_level.medium' => 2,
                'password.strength_level.very_strong' => 3,
            ]);

        $helper->field('captcha')
            ->updateOption('image', $service->generateImage(false))
            ->updateOption('constraints', [
                new NotBlank(),
                new Captcha(),
            ])
            ->label('security.login.captcha')
            ->domain('FOSUserBundle')
            ->add(CaptchaImage::class);

        $form = $builder->getForm();
        if ($this->handleFormRequest($form, $request)) {
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

        $helper = new FormHelper($builder);
        $helper->field('subject')
            ->label('user.fields.subject')
            ->addTextType();

        $helper->field('message')
            ->label('user.fields.message')
            ->addTextType();

        $helper->field('recaptcha')
            ->addHiddenType();

        // render
        $form = $builder->getForm();
        if ($this->handleFormRequest($form, $request)) {
            // get values
            $data = $form->getData();
            $response = $data['recaptcha'];
            // $remoteIp = $request->getClientIp();
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
            // $message = \implode('<br>', $errorCodes);
            // $this->error($message);

            foreach ($errorCodes as $code) {
                //$form->get('subject')->addError(new FormError($code));
                $form->addError(new FormError($code));
            }
        }

        return $this->render('test/recaptcha.html.twig', [
            'form' => $form->createView(),
            'recaptcha_action' => $recaptcha_action,
        ]);
    }

    /**
     * Display Summernote editor and file attachments.
     *
     * @Route("/summernote", name="test_summernote")
     */
    public function summernote(Request $request): Response
    {
        $data = [
            'subject' => 'Mon object',
            'color' => '#73A5AD',
        ];

        $builder = $this->createFormBuilder($data);
        $helper = new FormHelper($builder);

        $helper->field('message')
            ->label('user.fields.message')
            ->minLength(10)
            ->addEditorType();

        $helper->field('color')
            ->label('calculationstate.fields.color')
            ->updateAttribute('data-focus', 1)
            ->addColorType();

        // render
        $form = $builder->getForm();
        if ($this->handleFormRequest($form, $request)) {
            $data = $form->getData();
            $message = 'Message :<br>' . (string) $data['message']; // . '<br>';
            //$message .= 'Couleur :<br><span style="background-color:' . $data['color'] . ';">&nbsp;&nbsp;' . $data['color'] . '&nbsp;</span>';
            $message .= 'Couleur :<br>' . $data['color'];
            $this->succes($message);

            // home page
            return $this->redirectToHomePage();
        }

        return $this->render('test/summernote.html.twig', [
            'form' => $form->createView(),
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
     * Show the translation page.
     *
     * @Route("/translate", name="test_translate")
     */
    public function translate(TranslatorFactory $factory): Response
    {
        // get service
        $service = $factory->getSessionService();

        $form = $this->createFormBuilder()
            ->getForm();

        $parameters = [
            'form' => $form->createView(),
            'language' => HttpClientService::getAcceptLanguage(true),
            'languages' => $service->getLanguages(),
            'service' => $service::getName(),
            'services' => $factory->getServices(),
            'service_url' => $service::getApiUrl(),
        ];

        return $this->render('test/translate.html.twig', $parameters);
    }

    /**
     * @Route("/union", name="test_union")
     */
    public function union(Request $request, SearchService $service)
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

    /**
     * Update calculations with random customers.
     *
     * @Route("/update/calculations", name="test_update_calculations")
     */
    public function updateCalculations(EntityManagerInterface $manager, FakerService $ervice): Response
    {
        // TODO: Disabled in the doctrine.yml and CalculationListener
        /** @var \Faker\Generator $faker */
        $faker = $ervice->getFaker();

        /** @var \App\Entity\Calculation[] $calculations */
        $calculations = $manager->getRepository(Calculation::class)->findAll();
        $states = $manager->getRepository(CalculationState::class)->findBy([
            'editable' => true,
        ]);
        $styles = [0, 1, 2];

        // update
        foreach ($calculations as $calculation) {
            $style = $faker->randomElement($styles);
            switch ($style) {
                case 0:
                    $calculation->setCustomer($faker->companyAndSuffix());
                    break;

                case 1:
                    $calculation->setCustomer($faker->name(\Faker\Provider\Person::GENDER_MALE));
                    break;

                default:
                    $calculation->setCustomer($faker->name(\Faker\Provider\Person::GENDER_FEMALE));
                    break;
            }
            $calculation->setDescription($faker->catchPhrase())
                ->setState($faker->randomElement($states));
        }

        // save
        $manager->flush();

        $count = \count($calculations);
        $this->addFlashMessage(self::FLASH_TYPE_INFO, "La mise à jour de {$count} calculations a été effectuée avec succès.");

        return $this->redirectToHomePage();
    }

    /**
     * Update customers with random values.
     *
     * @Route("/update/customers", name="test_update_customers")
     */
    public function updateCustomers(EntityManagerInterface $manager, FakerService $ervice)
    {
        /** @var \App\Entity\Customer[] $customers */
        $customers = $manager->getRepository(Customer::class)->findAll();

        /** @var \Faker\Generator $faker */
        $faker = $ervice->getFaker();

        $genders = [
            \Faker\Provider\Person::GENDER_MALE,
            \Faker\Provider\Person::GENDER_FEMALE,
        ];

        $accessor = new PropertyAccessor();
        foreach ($customers as $customer) {
            $gender = $faker->randomElement($genders);
            $this->replace($accessor, $customer, 'title', $faker->title($gender))
                ->replace($accessor, $customer, 'firstName', $faker->firstName($gender))
                ->replace($accessor, $customer, 'lastName', $faker->lastName)
                ->replace($accessor, $customer, 'company', $faker->companyAndSuffix())
                ->replace($accessor, $customer, 'address', $faker->streetAddress)
                ->replace($accessor, $customer, 'zipCode', $faker->postcode)
                ->replace($accessor, $customer, 'city', $faker->city)
                ->replace($accessor, $customer, 'email', $faker->email);
        }

        $manager->flush();

        $count = \count($customers);
        $this->addFlashMessage(self::FLASH_TYPE_INFO, "La mise à jour de {$count} clients a été effectuée avec succès.");

        return $this->redirectToRoute('customer_list');
    }

    /**
     * Merges calculation to the calendar.
     *
     * @param Calculation[] $calculations
     */
    private function merge(Calendar $calendar, array $calculations): void
    {
        foreach ($calculations as $calculation) {
            /** @var \App\Calendar\CalculationsDay $day */
            $day = $calendar->getDay($calculation->getDate());
            if ($day) {
                $day->addCalculation($calculation);
            }
        }
    }

    /**
     * Update an element property.
     *
     * @param PropertyAccessor $accessor the property accessor to get or set value
     * @param mixed            $element  the element to update
     * @param string           $property the property name
     * @param mixed            $value    the new value to set
     */
    private function replace(PropertyAccessor $accessor, $element, string $property, $value): self
    {
        if (empty($accessor->getValue($element, $property))) {
            $value = null;
        }
        $accessor->setValue($element, $property, $value);

        return $this;
    }
}
