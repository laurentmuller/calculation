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

use App\Entity\AbstractEntity;
use App\Entity\User;
use App\Form\User\UserChangePasswordType;
use App\Form\User\UserCommentType;
use App\Form\User\UserImageType;
use App\Form\User\UserRightsType;
use App\Form\User\UserType;
use App\Interfaces\RoleInterface;
use App\Model\Comment;
use App\Report\UsersReport;
use App\Report\UsersRightsReport;
use App\Repository\AbstractRepository;
use App\Repository\UserRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Security\EntityVoter;
use App\Spreadsheet\UserRightsDocument;
use App\Spreadsheet\UsersDocument;
use App\Table\UserTable;
use App\Util\Utils;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * The controller for user entities.
 *
 * @template-extends AbstractEntityController<User>
 */
#[AsController]
#[IsGranted('ROLE_ADMIN')]
#[Route(path: '/user')]
class UserController extends AbstractEntityController
{
    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator, UserRepository $repository)
    {
        parent::__construct($translator, $repository);
    }

    /**
     * Add a user.
     */
    #[Route(path: '/add', name: 'user_add')]
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new User());
    }

    /**
     * Delete an user.
     */
    #[Route(path: '/delete/{id}', name: 'user_delete', requirements: ['id' => self::DIGITS])]
    public function delete(Request $request, User $item, LoggerInterface $logger): Response
    {
        // same?
        if ($this->isConnectedUser($item)) {
            $this->warningTrans('user.delete.connected');

            // redirect
            return $this->getUrlGenerator()->redirect($request, $item->getId(), $this->getDefaultRoute());
        }

        $parameters = [
            'title' => 'user.delete.title',
            'message' => 'user.delete.message',
            'success' => 'user.delete.success',
            'failure' => 'user.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $logger, $parameters);
    }

    /**
     * Edit a user.
     */
    #[Route(path: '/edit/{id}', name: 'user_edit', requirements: ['id' => self::DIGITS])]
    public function edit(Request $request, User $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the customers to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no user is found
     */
    #[Route(path: '/excel', name: 'user_excel')]
    public function excel(StorageInterface $storage, DateTimeFormatter $formatter): SpreadsheetResponse
    {
        $entities = $this->getEntities('username');
        if (empty($entities)) {
            $message = $this->trans('user.list.empty');
            throw $this->createNotFoundException($message);
        }

        $doc = new UsersDocument($this, $entities, $storage, $formatter);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Edit a user's image.
     */
    #[Route(path: '/image/{id}', name: 'user_image', requirements: ['id' => self::DIGITS])]
    public function image(Request $request, User $item): Response
    {
        // form
        $form = $this->createForm(UserImageType::class, $item);
        if ($this->handleRequestForm($request, $form)) {
            // save
            $this->saveToDatabase($item);

            // message
            $this->successTrans('user.image.success', ['%name%' => $item->getDisplay()]);

            // redirect
            return $this->getUrlGenerator()->redirect($request, $item->getId(), $this->getDefaultRoute());
        }

        // parameters
        $parameters = [
            'params' => ['id' => $item->getId()],
            'form' => $form,
            'item' => $item,
        ];

        // render
        return $this->renderForm('user/user_image.html.twig', $parameters);
    }

    /**
     * Send an email from the current user to another user.
     */
    #[Route(path: '/message/{id}', name: 'user_message', requirements: ['id' => self::DIGITS])]
    public function message(Request $request, User $user, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        // same user?
        if ($this->isConnectedUser($user)) {
            $this->warningTrans('user.message.connected');

            // redirect
            return $this->getUrlGenerator()->redirect($request, $user->getId(), $this->getDefaultRoute());
        }

        /** @var User $from */
        $from = $this->getUser() ?? $this->getAddressFrom();
        $comment = new Comment(true);
        $comment->setSubject($this->getApplicationName())
            ->setFromAddress($from)
            ->setToAddress($user);

        // create and handle request
        $form = $this->createForm(UserCommentType::class, $comment);
        if ($this->handleRequestForm($request, $form)) {
            try {
                // send
                $comment->send($mailer);
                $this->successTrans('user.message.success', ['%name%' => $user->getDisplay()]);

                // list
                return $this->getUrlGenerator()->redirect($request, $user->getId(), $this->getDefaultRoute());
            } catch (TransportExceptionInterface $e) {
                $message = $this->trans('user.message.error');
                $context = Utils::getExceptionContext($e);
                $logger->error($message, $context);

                return $this->renderForm('@Twig/Exception/exception.html.twig', [
                    'message' => $message,
                    'exception' => $e,
                ]);
            }
        }

        // parameters
        $parameters = [
            'item' => $user,
            'form' => $form,
            'isMail' => $comment->isMail(),
            'params' => ['id' => $user->getId()],
        ];

        // render
        return $this->renderForm('user/user_comment.html.twig', $parameters);
    }

    /**
     * Change password for an existing user.
     */
    #[Route(path: '/password/{id}', name: 'user_password', requirements: ['id' => self::DIGITS])]
    public function password(Request $request, User $item, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(UserChangePasswordType::class, $item);
        if ($this->handleRequestForm($request, $form)) {
            // encode password
            $plainPassword = (string) $form->get('plainPassword')->getData();
            $encodedPassword = $hasher->hashPassword($item, $plainPassword);
            $item->setPassword($encodedPassword);

            // save
            $this->saveToDatabase($item);

            // message
            $this->successTrans('user.change_password.change_success', ['%name%' => $item->getDisplay()]);

            // redirect
            return $this->getUrlGenerator()->redirect($request, $item->getId(), $this->getDefaultRoute());
        }

        // parameters
        $parameters = [
            'item' => $item,
            'form' => $form,
            'params' => ['id' => $item->getId()],
        ];

        // show form
        return $this->renderForm('user/user_password.html.twig', $parameters);
    }

    /**
     * Export the users to a PDF document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no user is found
     */
    #[Route(path: '/pdf', name: 'user_pdf')]
    public function pdf(StorageInterface $storage, DateTimeFormatter $formatter): PdfResponse
    {
        $entities = $this->getEntities('username');
        if (empty($entities)) {
            $message = $this->trans('user.list.empty');
            throw $this->createNotFoundException($message);
        }

        $doc = new UsersReport($this, $entities, $storage, $formatter);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Clear the request reset password.
     */
    #[Route(path: '/reset/{id}', name: 'user_reset', requirements: ['id' => self::DIGITS])]
    public function resetPasswordRequest(Request $request, User $item): Response
    {
        if ($item->isResetPassword()) {
            /** @psalm-var UserRepository $repository */
            $repository = $this->repository;
            $repository->removeResetPasswordRequest($item);
            $this->successTrans('user.reset.success', ['%name%' => $item->getUserIdentifier()]);
        } else {
            $this->warningTrans('user.reset.error', ['%name%' => $item->getUserIdentifier()]);
        }

        return $this->getUrlGenerator()->redirect($request, $item->getId(), $this->getDefaultRoute());
    }

    /**
     * Edit user access rights.
     */
    #[Route(path: '/rights/{id}', name: 'user_rights', requirements: ['id' => self::DIGITS])]
    public function rights(Request $request, User $item, RoleHierarchyInterface $hierarchy, EntityManagerInterface $manager): Response
    {
        // same user?
        if ($this->isConnectedUser($item)) {
            // super admin?
            $roles = $hierarchy->getReachableRoleNames($item->getRoles());
            if (!\in_array(RoleInterface::ROLE_SUPER_ADMIN, $roles, true)) {
                $this->warningTrans('user.rights.connected');

                // redirect
                return $this->getUrlGenerator()->redirect($request, $item->getId(), $this->getDefaultRoute());
            }
        }

        // form
        $form = $this->createForm(UserRightsType::class, $item);
        if ($this->handleRequestForm($request, $form)) {
            // save
            $manager->flush();

            // message
            $this->successTrans('user.rights.success', ['%name%' => $item->getDisplay()]);

            // redirect
            return $this->getUrlGenerator()->redirect($request, $item->getId(), $this->getDefaultRoute());
        }

        // parameters
        $parameters = [
            'item' => $item,
            'form' => $form,
            'default' => EntityVoter::getRole($item),
            'params' => ['id' => $item->getId()],
        ];

        // show form
        return $this->renderForm('user/user_rights.html.twig', $parameters);
    }

    /**
     * Export the user access rights to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no user is found
     */
    #[Route(path: '/rights/excel', name: 'user_rights_excel')]
    public function rightsExcel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('username');
        if (empty($entities)) {
            $message = $this->trans('user.list.empty');
            throw $this->createNotFoundException($message);
        }

        $doc = new UserRightsDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export user access rights to a PDF document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no user is found
     */
    #[Route(path: '/rights/pdf', name: 'user_rights_pdf')]
    public function rightsPdf(): PdfResponse
    {
        $users = $this->getEntities('username');
        if (empty($users)) {
            $message = $this->trans('user.list.empty');
            throw $this->createNotFoundException($message);
        }

        $doc = new UsersRightsReport($this, $users);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show the properties of a user.
     */
    #[Route(path: '/show/{id}', name: 'user_show', requirements: ['id' => self::DIGITS])]
    public function show(User $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     */
    #[Route(path: '', name: 'user_table')]
    public function table(Request $request, UserTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'user/user_table.html.twig');
    }

    /**
     * {@inheritdoc}
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        // update parameters
        $parameters['success'] = $item->isNew() ? 'user.add.success' : 'user.edit.success';

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditFormType(): string
    {
        return UserType::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntities(?string $field = null, string $mode = Criteria::ASC, array $criterias = [], string $alias = AbstractRepository::DEFAULT_ALIAS): array
    {
        // remove super admin users if not granted
        $role = RoleInterface::ROLE_SUPER_ADMIN;
        if (!$this->isGranted($role)) {
            $criterias[] = "$alias.role <> '$role' or $alias.role IS NULL";
        }

        return parent::getEntities($field, $mode, $criterias, $alias);
    }

    /**
     * Returns if the given user is the same as the logged-in user.
     *
     * @param User $user the user to verify
     *
     * @return bool true if the same
     */
    private function isConnectedUser(User $user): bool
    {
        $connectedUser = $this->getUser();

        return $connectedUser instanceof User && $connectedUser->getId() === $user->getId();
    }
}
