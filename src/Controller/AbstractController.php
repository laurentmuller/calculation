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

use App\Entity\User;
use App\Form\FormHelper;
use App\Pdf\PdfDocument;
use App\Report\AbstractReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\ApplicationService;
use App\Service\UrlGeneratorService;
use App\Service\UserService;
use App\Spreadsheet\AbstractDocument;
use App\Spreadsheet\SpreadsheetDocument;
use App\Traits\TranslatorFlashMessageTrait;
use App\Util\Utils;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides common features needed in controllers.
 *
 * @method ?User getUser() Gets the current user.
 *
 * @author Laurent Muller
 */
abstract class AbstractController extends BaseController
{
    use TranslatorFlashMessageTrait;

    /**
     * The home route name.
     */
    final public const HOME_PAGE = 'homepage';

    /**
     * The application service.
     */
    protected ?ApplicationService $application = null;

    /**
     * The URL generator service.
     */
    protected ?UrlGeneratorService $generatorService = null;

    /**
     * The user service.
     */
    protected ?UserService $userService = null;

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
        if (null === $this->application) {
            $this->application = $this->getService(ApplicationService::class);
        }

        return $this->application;
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
     * Gets a boolean container parameter by its name.
     */
    public function getBoolParameter(string $name): bool
    {
        /* @psalm-var bool */
        return (bool) $this->getParameter($name);
    }

    /**
     * Gets the request stack.
     */
    public function getRequestStack(): RequestStack
    {
        /** @psalm-var RequestStack $requestStack */
        $requestStack = $this->container->get('request_stack');

        return $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return \array_merge(parent::getSubscribedServices(), [
            UserService::class,
            ApplicationService::class,
            TranslatorInterface::class,
            UrlGeneratorService::class,
        ]);
    }

    /**
     * Gets the translator service.
     */
    public function getTranslator(): TranslatorInterface
    {
        if (null === $this->translator) {
            $this->translator = $this->getService(TranslatorInterface::class);
        }

        return $this->translator;
    }

    /**
     * Gets the URL generator service.
     */
    public function getUrlGenerator(): UrlGeneratorService
    {
        if (null === $this->generatorService) {
            $this->generatorService = $this->getService(UrlGeneratorService::class);
        }

        return $this->generatorService;
    }

    /**
     * Gets the connected user e-mail.
     *
     * @return string|null the user e-mail or null if not connected
     */
    public function getUserEmail(): ?string
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            return $user->getEmail();
        }

        return null;
    }

    /**
     * Gets the connected username.
     *
     * @return string|null the username or null if not connected
     */
    public function getUserName(): ?string
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            return $user->getUserIdentifier();
        }

        return null;
    }

    /**
     * Gets the user service.
     */
    public function getUserService(): UserService
    {
        if (null === $this->userService) {
            $this->userService = $this->getService(UserService::class);
        }

        return $this->userService;
    }

    /**
     * Returns if the debug mode is enabled.
     */
    public function isDebug(): bool
    {
        return $this->getBoolParameter('kernel.debug');
    }

    /**
     * Redirect to the home page.
     */
    public function redirectToHomePage(): RedirectResponse
    {
        return $this->redirectToRoute(self::HOME_PAGE);
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container): ?ContainerInterface
    {
        $previous = parent::setContainer($container);
        if (null === $this->translator) {
            $this->setTranslator($this->getService(TranslatorInterface::class));
        }

        return $previous;
    }

    /**
     * {@inheritDoc} Override the parent function to allow to use the default type like defined in the <code>FormFactoryInterface</code>.
     *
     * @param mixed $data the initial data
     *
     * @psalm-suppress ArgumentTypeCoercion
     * @psalm-suppress OverriddenMethodAccess
     */
    protected function createForm(string $type = FormType::class, $data = null, array $options = []): FormInterface
    {
        return parent::createForm($type, $data, $options);
    }

    /**
     * Creates and returns a form helper instance.
     *
     * @param string|null $labelPrefix the label prefix. If the prefix is not null, the label is automatically added when the field property is set.
     * @param mixed|null  $data        the initial data
     * @param array       $options     the initial options
     */
    protected function createFormHelper(string $labelPrefix = null, mixed $data = null, array $options = []): FormHelper
    {
        $builder = $this->createFormBuilder($data, $options);

        return new FormHelper($builder, $labelPrefix);
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
        return Utils::getRequestInputBag($request)->getBoolean($key, $default);
    }

    /**
     * Returns the request parameter value converted to float.
     *
     * @param Request $request the request to get parameter value from
     * @param string  $key     the parameter key
     * @param float   $default the default value if the parameter key does not exist
     */
    protected function getRequestFloat(Request $request, string $key, float $default = 0): float
    {
        return (float) Utils::getRequestInputBag($request)->get($key, $default);
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
        return Utils::getRequestInputBag($request)->getInt($key, $default);
    }

    /**
     * Returns the request parameter value converted to string.
     *
     * @param Request     $request the request to get parameter value from
     * @param string      $key     the parameter key
     * @param string|null $default the default value if the parameter key does not exist
     */
    protected function getRequestString(Request $request, string $key, string $default = null): ?string
    {
        $value = Utils::getRequestInputBag($request)->get($key, $default);

        return \is_string($value) ? $value : $default;
    }

    /**
     * Gets the service of the given class name.
     *
     * @param string $id the service identifier to get for
     *
     * @return mixed|null the service, if found; null otherwise
     *
     * @template T
     * @psalm-param class-string<T> $id
     * @psalm-return T
     *
     * @throws \Psr\Container\NotFoundExceptionInterface  no entry was found for the identifier
     * @throws \Psr\Container\ContainerExceptionInterface error while retrieving the entry
     */
    protected function getService(string $id): mixed
    {
        /** @psalm-var T $service */
        $service = $this->container->get($id);

        return $service;
    }

    /**
     * Gets a string container parameter by its name.
     */
    protected function getStringParameter(string $name): string
    {
        /** @psalm-var string $value */
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
     * Render the given PDF document and output the response.
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
        if (empty($name) && \is_string($title = $doc->getTitle())) {
            $name = \sprintf('%s.pdf', $title);
        }

        // create response
        return new PdfResponse($doc, $inline, $name);
    }

    /**
     * Render the given Spreadsheet document and output the response.
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
        if (empty($name) && \is_string($title = $doc->getTitle())) {
            $name = \sprintf('%s.xlsx', $title);
        }

        return new SpreadsheetResponse($doc, $inline, $name);
    }
}
