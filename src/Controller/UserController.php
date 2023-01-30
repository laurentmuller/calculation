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
use App\Service\MailerService;
use App\Spreadsheet\UserRightsDocument;
use App\Spreadsheet\UsersDocument;
use App\Table\UserTable;
use App\Util\RoleBuilder;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * The controller for user entities.
 *
 * @template-extends AbstractEntityController<User>
 */
#[AsController]
#[Route(path: '/user')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class UserController extends AbstractEntityController
{
    /**
     * Constructor.
     *
     * @throws \ReflectionException
     */
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Add a user.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/add', name: 'user_add')]
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new User());
    }

    /**
     * Delete an user.
     *
     * @throws \ReflectionException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/delete/{id}', name: 'user_delete', requirements: ['id' => Requirement::DIGITS])]
    public function delete(Request $request, User $item, Security $security, LoggerInterface $logger): Response
    {
        // same?
        if ($this->isConnectedUser($item) || $this->isOriginalUser($item, $security)) {
            $this->warningTrans('user.delete.connected');

            // redirect
            return $this->getUrlGenerator()->redirect($request, $item->getId(), $this->getDefaultRoute());
        }

        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Edit a user.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/edit/{id}', name: 'user_edit', requirements: ['id' => Requirement::DIGITS])]
    public function edit(Request $request, User $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the customers to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no user is found
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Route(path: '/excel', name: 'user_excel')]
    public function excel(StorageInterface $storage): SpreadsheetResponse
    {
        $entities = $this->getEntities('username');
        if (empty($entities)) {
            $message = $this->trans('user.list.empty');
            throw $this->createNotFoundException($message);
        }

        $doc = new UsersDocument($this, $entities, $storage);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Edit a user's image.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/image/{id}', name: 'user_image', requirements: ['id' => Requirement::DIGITS])]
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
        return $this->render('user/user_image.html.twig', $parameters);
    }

    /**
     * Send an email from the current user to another user.
     *
     * @throws \ReflectionException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/message/{id}', name: 'user_message', requirements: ['id' => Requirement::DIGITS])]
    public function message(Request $request, User $user, MailerService $service, LoggerInterface $logger): Response
    {
        // same user?
        if ($this->isConnectedUser($user)) {
            $this->warningTrans('user.message.connected');

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
                $service->sendComment($comment);
                $this->successTrans('user.message.success', ['%name%' => $user->getDisplay()]);

                return $this->getUrlGenerator()->redirect($request, $user->getId(), $this->getDefaultRoute());
            } catch (TransportExceptionInterface $e) {
                return $this->renderFormException('user.message.error', $e, $logger);
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
        return $this->render('user/user_comment.html.twig', $parameters);
    }

    /**
     * Change password for an existing user.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/password/{id}', name: 'user_password', requirements: ['id' => Requirement::DIGITS])]
    public function password(Request $request, User $item): Response
    {
        $form = $this->createForm(UserChangePasswordType::class, $item);
        if ($this->handleRequestForm($request, $form)) {
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
        return $this->render('user/user_password.html.twig', $parameters);
    }

    /**
     * Export the users to a PDF document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no user is found
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/pdf', name: 'user_pdf')]
    public function pdf(StorageInterface $storage): PdfResponse
    {
        // $users = $this->repository->getResettableUsers();

        $entities = $this->getEntities('username');
        if (empty($entities)) {
            $message = $this->trans('user.list.empty');
            throw $this->createNotFoundException($message);
        }

        $doc = new UsersReport($this, $entities, $storage);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Clear all requested reset passwords.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/reset', name: 'user_reset_all')]
    public function resetAllPasswordRequest(Request $request): Response
    {
        /** @psalm-var UserRepository $repository */
        $repository = $this->repository;
        $users = $repository->getResettableUsers();
        if (empty($users)) {
            $this->warningTrans('user.reset_all.empty');

            return $this->getUrlGenerator()->redirect($request, null, $this->getDefaultRoute());
        }
        if (1 === \count($users)) {
            return $this->redirectToRoute('user_reset', $this->getUrlGenerator()->routeParams($request, $users[0]->getId()));
        }

        $fieldName = 'users';
        $data = [$fieldName => $users];
        $builder = $this->createFormBuilder($data)
            ->add($fieldName, ChoiceType::class, [
                'expanded' => true,
                'multiple' => true,
                'label' => 'user.list.title',
                'label_attr' => ['class' => 'ml-n4'],
                'choices' => $users,
                'choice_value' => 'id',
                'choice_label' => 'NameAndEmail',
                'choice_translation_domain' => false,
            ])->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($fieldName): void {
                /** @psalm-var array<string, mixed> $data */
                $data = $event->getData();
                $form = $event->getForm();
                $field = $form->get($fieldName);
                if ($field->isRequired() && !isset($data[$fieldName])) {
                    $form->addError(new FormError($this->trans('user.reset_all.error')));
                }
            });
        $form = $builder->getForm();

        if ($this->handleRequestForm($request, $form)) {
            /** @psalm-var User[] $users */
            $users = $form->get($fieldName)->getData();
            foreach ($users as $user) {
                $repository->resetPasswordRequest($user, false);
            }
            $repository->flush();

            if (1 === \count($users)) {
                $this->infoTrans('user.reset.success', ['%name%' => $users[0]->getUserIdentifier()]);
            } else {
                $this->infoTrans('user.reset_all.success', ['%count%' => \count($users)]);
            }

            return $this->getUrlGenerator()->redirect($request, null, $this->getDefaultRoute());
        }

        return $this->render('user/user_reset_all_passwords.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Clear the request reset password.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/reset/{id}', name: 'user_reset', requirements: ['id' => Requirement::DIGITS])]
    public function resetPasswordRequest(Request $request, User $item): Response
    {
        $identifier = $item->getUserIdentifier();
        $form = $this->createFormBuilder()->getForm();

        if ($this->handleRequestForm($request, $form)) {
            if ($item->isResetPassword()) {
                /** @psalm-var UserRepository $repository */
                $repository = $this->repository;
                $repository->removeResetPasswordRequest($item);
                $this->successTrans('user.reset.success', ['%name%' => $identifier]);
            } else {
                $this->warningTrans('user.reset.error', ['%name%' => $identifier]);
            }

            return $this->getUrlGenerator()->redirect($request, $item->getId(), $this->getDefaultRoute());
        }

        return $this->render('user/user_reset_password.html.twig', [
            'form' => $form,
            'name' => $identifier,
        ]);
    }

    /**
     * Edit user access rights.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/rights/{id}', name: 'user_rights', requirements: ['id' => Requirement::DIGITS])]
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

        // show form
        return $this->render('user/user_rights.html.twig', [
            'item' => $item,
            'form' => $form,
            'params' => ['id' => $item->getId()],
            'default' => RoleBuilder::getRole($item),
            'permissions' => EntityPermission::sorted(),
        ]);
    }

    /**
     * Export the user access rights to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no user is found
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Psr\Container\ContainerExceptionInterface
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
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
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
    #[Route(path: '/show/{id}', name: 'user_show', requirements: ['id' => Requirement::DIGITS])]
    public function show(User $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @throws \ReflectionException
     */
    #[Route(path: '', name: 'user_table')]
    public function table(Request $request, UserTable $table, LoggerInterface $logger): Response
    {
        return $this->handleTableRequest($request, $table, 'user/user_table.html.twig', $logger);
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
    protected function getEntities(?string $field = null, string $mode = Criteria::ASC, array $criteria = [], string $alias = AbstractRepository::DEFAULT_ALIAS): array
    {
        // remove super admin users if not granted
        $role = RoleInterface::ROLE_SUPER_ADMIN;
        if (!$this->isGranted($role)) {
            $criteria[] = "$alias.role <> '$role' or $alias.role IS NULL";
        }

        return parent::getEntities($field, $mode, $criteria, $alias);
    }

    /**
     * Returns if the given user is the same as the logged-in user.
     */
    private function isConnectedUser(User $user): bool
    {
        return $this->isSameUser($user, $this->getUser());
    }

    /**
     * Returns if the given user is the same as the original user.
     */
    private function isOriginalUser(User $user, Security $security): bool
    {
        $token = $security->getToken();
        if ($token instanceof SwitchUserToken) {
            return $this->isSameUser($user, $token->getOriginalToken()->getUser());
        }

        return false;
    }

    /**
     * Returns if the given users are equal.
     */
    private function isSameUser(User $user, ?UserInterface $value): bool
    {
        return $value instanceof User && $value->getId() === $user->getId();
    }
}
