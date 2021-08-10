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

use App\Entity\User;
use App\Form\FormHelper;
use App\Pdf\PdfDocument;
use App\Pdf\PdfResponse;
use App\Report\AbstractReport;
use App\Service\ApplicationService;
use App\Service\UrlGeneratorService;
use App\Spreadsheet\AbstractDocument;
use App\Spreadsheet\SpreadsheetDocument;
use App\Spreadsheet\SpreadsheetResponse;
use App\Traits\TranslatorFlashMessageTrait;
use App\Util\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseController;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides common features needed in controllers.
 *
 * @author Laurent Muller
 */
abstract class AbstractController extends BaseController
{
    use TranslatorFlashMessageTrait;

    /**
     * The home route name.
     */
    public const HOME_PAGE = 'homepage';

    /**
     * The application service.
     */
    protected ?ApplicationService $application = null;

    /**
     * The URL generator service.
     */
    protected ?UrlGeneratorService $generatorService = null;

    /**
     * Gets the address from (email and name) used to send email.
     */
    public function getAddressFrom(): Address
    {
        $email = $this->getStringParameter('mailer_user_email');
        $name = $this->getStringParameter('mailer_user_name');

        return new Address($email, $name);
    }

    /**
     * Gets the application service.
     */
    public function getApplication(): ApplicationService
    {
        if (null !== $this->application) {
            return $this->application;
        } else {
            /** @var ApplicationService $service */
            $service = $this->get(ApplicationService::class);

            return $this->application = $service;
        }
    }

    /**
     * Gets the application name and version.
     */
    public function getApplicationName(): string
    {
        $name = $this->getStringParameter('app_name');
        $version = $this->getStringParameter('app_version');

        return \sprintf('%s v%s', $name, $version);
    }

    /**
     * Gets the application owner.
     */
    public function getApplicationOwner(): string
    {
        return $this->getStringParameter('app_owner');
    }

    /**
     * Gets the application owner URL.
     */
    public function getApplicationOwnerUrl(): string
    {
        return $this->getStringParameter('app_owner_url');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return \array_merge([
            ApplicationService::class,
            TranslatorInterface::class,
            UrlGeneratorService::class,
        ], parent::getSubscribedServices());
    }

    /**
     * Gets the translator service.
     */
    public function getTranslator(): TranslatorInterface
    {
        if (null !== $this->translator) {
            return $this->translator;
        } else {
            /** @var TranslatorInterface $service */
            $service = $this->get(TranslatorInterface::class);

            return $this->translator = $service;
        }
    }

    /**
     * Gets the URL generator service.
     */
    public function getUrlGenerator(): UrlGeneratorService
    {
        if (null !== $this->generatorService) {
            return $this->generatorService;
        } else {
            /** @var UrlGeneratorService $service */
            $service = $this->get(UrlGeneratorService::class);

            return $this->generatorService = $service;
        }
    }

    /**
     * Gets the connected user name.
     *
     * @return string|null The user name or NULL if not connected
     */
    public function getUserName(): ?string
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            return $user->getUsername();
        }

        return null;
    }

    /**
     * Returns if the debug mode is enabled.
     *
     * @return bool true if enabled
     */
    public function isDebug(): bool
    {
        return (bool) $this->getParameter('kernel.debug');
    }

    /**
     * Gets a value indicating how entities are displayed.
     *
     * @return bool true, displays the entities in tabular mode; false, displays entities as cards
     */
    public function isDisplayTabular(): bool
    {
        return $this->getApplication()->isDisplayTabular();
    }

    /**
     * Redirect to the home page.
     */
    public function redirectToHomePage(): RedirectResponse
    {
        return $this->redirectToRoute(self::HOME_PAGE);
    }

    /**
     * Creates and returns a form helper instance.
     *
     * @param string $labelPrefix the label prefix. If the prefix is not null,
     *                            the label is automatically added when the field property is
     *                            set.
     * @param mixed  $data        the initial data
     * @param array  $options     the initial options
     */
    protected function createFormHelper(string $labelPrefix = null, $data = null, array $options = []): FormHelper
    {
        $builder = $this->createFormBuilder($data, $options);

        return new FormHelper($builder, $labelPrefix);
    }

    /**
     * Creates and returns a form instance.
     *
     * @param mixed $data    the initial data
     * @param array $options the initial options
     *
     * @see FormBuilderInterface::getForm()
     */
    protected function getForm($data = null, array $options = []): FormInterface
    {
        return $this->createFormBuilder($data, $options)->getForm();
    }

    /**
     * Gets the named object manager.
     *
     * This is a shortcut to: <code>$this->getDoctrine()->getManager();</code>.
     */
    protected function getManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $manager */
        $manager = $this->getDoctrine()->getManager();

        return $manager;
    }

    /**
     * Returns the request parameter value converted to boolean.
     *
     * @param Request $request the request to get parameter value from
     * @param string  $key     the parameter key
     * @param bool    $default the default value if the parameter key does not exist
     */
    protected function getRequestBoolean(Request $request, string $key, bool $default = false): bool
    {
        $value = $request->get($key, $default);

        return (bool) \filter_var($value, \FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Returns the request parameter value converted to integer.
     *
     * @param Request $request the request to get parameter value from
     * @param string  $key     the parameter key
     * @param int     $default the default value if the parameter key does not exist
     */
    protected function getRequestInt(Request $request, string $key, int $default = 0): int
    {
        return (int) $request->get($key, $default);
    }

    /**
     * Gets a container parameter by its name.
     */
    protected function getStringParameter(string $name): string
    {
        /** @var string $value */
        $value = $this->getParameter($name);

        return $value;
    }

    /**
     * Inspects the given request and calls submit() if the form was submitted, checks whether the given
     * form is submitted and if the form and all children are valid.
     *
     * @param Request       $request the request to handle
     * @param FormInterface $form    the form to validate
     *
     * @return bool true if the form is submitted and valid
     *
     * @see FormInterface::handleRequest()
     * @see FormInterface::isSubmitted()
     * @see FormInterface::isValid()
     */
    protected function handleRequestForm(Request $request, FormInterface $form): bool
    {
        $form->handleRequest($request);

        return $form->isSubmitted() && $form->isValid();
    }

    /**
     * Returns the given exception as a JsonResponse.
     *
     * @param \Exception  $e       the exception to serialize
     * @param string|null $message the optional error message
     */
    protected function jsonException(\Exception $e, ?string $message = null): JsonResponse
    {
        return $this->jsonFalse([
            'message' => $message ?? $e->getMessage(),
            'exception' => Utils::getExceptionContext($e),
        ]);
    }

    /**
     * Returns a Json response with false as result.
     *
     * @param array $data the data to merge within the response
     */
    protected function jsonFalse(array $data = []): JsonResponse
    {
        return $this->json(\array_merge_recursive(['result' => false], $data));
    }

    /**
     * Returns a Json response with true as result.
     *
     * @param array $data the data to merge within the response
     */
    protected function jsonTrue(array $data = []): JsonResponse
    {
        return $this->json(\array_merge_recursive(['result' => true], $data));
    }

    /**
     * Render the given PDF document and ouput the response.
     *
     * @param PdfDocument $doc    the document to render
     * @param bool        $inline <code>true</code> to send the file inline to the browser. The PDF viewer is used if available.
     *                            <code>false</code> to send to the browser and force a file download with the name given.
     * @param string      $name   the name of the PDF file or null to use default ('document.pdf')
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if the report can not be rendered
     */
    protected function renderPdfDocument(PdfDocument $doc, bool $inline = true, string $name = ''): PdfResponse
    {
        // render
        if ($doc instanceof AbstractReport && !$doc->render()) {
            throw $this->createNotFoundException($this->trans('errors.render_document'));
        }

        // title
        if (empty($name) && !empty($doc->getTitle())) {
            $name = $doc->getTitle() . '.pdf';
        }

        // create response
        return new PdfResponse($doc, $inline, $name);
    }

    /**
     * Render the given Spreadsheet document and ouput the response.
     *
     * @param SpreadsheetDocument $doc    the document to render
     * @param bool                $inline <code>true</code> to send the file inline to the browser. The Spreadsheet viewer is used if available.
     *                                    <code>false</code> to send to the browser and force a file download.
     * @param string              $name   the name of the Spreadsheet file or null to use default ('document.xlsx')
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if the report can not be rendered
     */
    protected function renderSpreadsheetDocument(SpreadsheetDocument $doc, bool $inline = true, string $name = ''): SpreadsheetResponse
    {
        // render
        if ($doc instanceof AbstractDocument && !$doc->render()) {
            throw $this->createNotFoundException($this->trans('errors.render_document'));
        }

        // title
        if (empty($name) && !empty($doc->getTitle())) {
            $name = $doc->getTitle() . '.xlsx';
        }

        return new SpreadsheetResponse($doc, $inline, $name);
    }
}
