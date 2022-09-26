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
use App\Enums\EntityPermission;
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
use App\Traits\RequestTrait;
use App\Traits\TranslatorFlashMessageAwareTrait;
use App\Util\Utils;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Service\Attribute\SubscribedService;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides common features needed in controllers.
 *
 * @method ?User getUser() Gets the current user.
 */
abstract class AbstractController extends BaseController
{
    use RequestTrait;
    use TranslatorFlashMessageAwareTrait;

    /**
     * Integer requirement for route parameter.
     */
    final public const DIGITS = '\d+';

    /**
     * The home route name.
     */
    final public const HOME_PAGE = 'homepage';

    // services
    private ?UrlGeneratorService $generatorService = null;
    private ?RequestStack $requestStack = null;
    private ?TranslatorInterface $translator = null;
    private ?UserService $userService = null;

    /**
     * Gets the address from (email and name) used to send email.
     */
    public function getAddressFrom(): Address
    {
        $email = $this->getParameterString('mailer_user_email');
        $name = $this->getParameterString('mailer_user_name');

        return new Address($email, $name);
    }

    /**
     * Gets the application service.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function getApplication(): ApplicationService
    {
        return $this->getUserService()->getApplication();
    }

    /**
     * Gets the application name and version.
     */
    public function getApplicationName(): string
    {
        $name = $this->getParameterString('app_name');
        $version = $this->getParameterString('app_version');

        return \sprintf('%s v%s', $name, $version);
    }

    /**
     * Gets the application owner.
     */
    public function getApplicationOwner(): string
    {
        return $this->getParameterString('app_owner');
    }

    /**
     * Gets the application owner URL.
     */
    public function getApplicationOwnerUrl(): string
    {
        return $this->getParameterString('app_owner_url');
    }

    /**
     * Gets the request stack.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[SubscribedService]
    public function getRequestStack(): RequestStack
    {
        if (null === $this->requestStack) {
            /** @psalm-var RequestStack $requestStack */
            $requestStack = $this->container->get('request_stack');

            return $this->requestStack = $requestStack;
        }

        return $this->requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return \array_merge(parent::getSubscribedServices(), [
            UserService::class,
            TranslatorInterface::class,
            UrlGeneratorService::class,
        ]);
    }

    /**
     * Gets the translator.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
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
     *
     * @throws \Psr\Container\ContainerExceptionInterface
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
     * Gets the connected user identifier.
     */
    public function getUserIdentifier(): ?string
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            return $user->getUserIdentifier();
        }

        return null;
    }

    /**
     * Gets the user service.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function getUserService(): UserService
    {
        if (null === $this->userService) {
            $this->userService = $this->getService(UserService::class);
        }

        return $this->userService;
    }

    /**
     * Redirect to the home page.
     */
    public function redirectToHomePage(): RedirectResponse
    {
        return $this->redirectToRoute(self::HOME_PAGE);
    }

    /**
     * {@inheritDoc} Override the parent function to allow to use the default type like defined in the <code>FormFactoryInterface</code>.
     *
     * @param mixed $data the initial data
     *
     * @psalm-suppress ArgumentTypeCoercion
     * @psalm-suppress OverriddenMethodAccess
     */
    protected function createForm(string $type = FormType::class, mixed $data = null, array $options = []): FormInterface
    {
        return parent::createForm($type, $data, $options);
    }

    /**
     * Creates and returns a form helper instance.
     *
     * @param ?string    $labelPrefix the label prefix. If the prefix is not null, the label is automatically added when the field property is set.
     * @param mixed|null $data        the initial data
     * @param array      $options     the initial options
     */
    protected function createFormHelper(string $labelPrefix = null, mixed $data = null, array $options = []): FormHelper
    {
        $builder = $this->createFormBuilder($data, $options);

        return new FormHelper($builder, $labelPrefix);
    }

    /**
     * {inheritDoc}.
     */
    protected function denyAccessUnlessGranted(mixed $attribute, mixed $subject = null, string $message = 'Access Denied.'): void
    {
        if ($attribute instanceof EntityPermission) {
            $attribute = $attribute->name;
        }
        parent::denyAccessUnlessGranted($attribute, $subject, $message);
    }

    /**
     * Gets a string container parameter by its name.
     */
    protected function getParameterString(string $name): string
    {
        /** @psalm-var string $value */
        $value = $this->getParameter($name);

        return $value;
    }

    /**
     * Gets the service of the given class name.
     *
     * @param string $id the service identifier to get for
     *
     * @return mixed|null the service, if found; null otherwise
     *
     * @template T
     *
     * @psalm-param class-string<T> $id
     *
     * @psalm-return T
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function getService(string $id): mixed
    {
        /** @psalm-var T $service */
        $service = $this->container->get($id);

        return $service;
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
     * @param \Exception $e       the exception to serialize
     * @param ?string    $message the optional error message
     *
     * @throws \ReflectionException
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
     * Render the template exception.
     *
     * @throws \ReflectionException
     */
    protected function renderFormException(string $id, \Throwable $e, LoggerInterface $logger = null): Response
    {
        $message = $this->trans($id);
        $context = Utils::getExceptionContext($e);
        $logger?->error($message, $context);

        return $this->renderForm('@Twig/Exception/exception.html.twig', [
            'message' => $message,
            'exception' => $e,
        ]);
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
     * @throws \PhpOffice\PhpSpreadsheet\Exception
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
