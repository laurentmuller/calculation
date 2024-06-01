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

use App\Attribute\GetPost;
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
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to send comments to the webmaster.
 */
#[AsController]
#[Route(path: '/user', name: 'user_')]
class CommentController extends AbstractController
{
    /**
     * Send a comment to the webmaster.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[GetPost(path: '/comment', name: 'comment')]
    public function invoke(Request $request, MailerService $service, LoggerInterface $logger): Response
    {
        /** @psalm-var User|Address $from */
        $from = $this->getUser() ?? $this->getAddressFrom();
        $comment = new Comment(false);
        $comment->setSubject($this->getApplicationName())
            ->setFromAddress($from)
            ->setToAddress($this->getAddressFrom());
        $form = $this->createForm(UserCommentType::class, $comment);
        if ($this->handleRequestForm($request, $form)) {
            try {
                $service->sendComment($comment);

                return $this->redirectToHomePage('user.comment.success', request: $request);
            } catch (TransportExceptionInterface $e) {
                return $this->renderFormException('user.comment.error', $e, $logger);
            }
        }
        $parameters = [
            'form' => $form,
            'isMail' => $comment->isMail(),
        ];

        return $this->render('user/user_comment.html.twig', $parameters);
    }
}
