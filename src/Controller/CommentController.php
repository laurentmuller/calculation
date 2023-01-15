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
use App\Form\User\UserCommentType;
use App\Interfaces\RoleInterface;
use App\Model\Comment;
use App\Service\MailerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to send comments to the webmaster.
 */
#[AsController]
#[Route(path: '/user')]
class CommentController extends AbstractController
{
    /**
     * Send comment to the webmaster.
     *
     * @throws \ReflectionException
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/comment', name: 'user_comment')]
    public function invoke(Request $request, MailerService $service, LoggerInterface $logger): Response
    {
        /** @var User $from */
        $from = $this->getUser() ?? $this->getAddressFrom();
        $comment = new Comment(false);
        $comment->setSubject($this->getApplicationName())
            ->setFromAddress($from)
            ->setToAddress($this->getAddressFrom());

        // create and handle request
        $form = $this->createForm(UserCommentType::class, $comment);
        if ($this->handleRequestForm($request, $form)) {
            try {
                $service->sendComment($comment);
                $this->successTrans('user.comment.success');

                return $this->redirectToHomePage();
            } catch (TransportExceptionInterface $e) {
                return $this->renderFormException('user.comment.error', $e, $logger);
            }
        }
        // render
        return $this->render('user/user_comment.html.twig', [
            'form' => $form,
            'isMail' => $comment->isMail(),
        ]);
    }
}
