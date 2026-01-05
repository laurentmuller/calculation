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

use App\Attribute\ForUser;
use App\Attribute\GetPostRoute;
use App\Entity\User;
use App\Form\User\UserCommentType;
use App\Model\Comment;
use App\Service\MailerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Controller to send comments to the webmaster.
 */
#[Route(path: '/user', name: 'user_')]
class CommentController extends AbstractController
{
    /**
     * Send a comment to the webmaster.
     */
    #[ForUser]
    #[GetPostRoute(path: '/comment', name: 'comment')]
    public function invoke(
        Request $request,
        #[CurrentUser]
        User $from,
        MailerService $service,
        LoggerInterface $logger
    ): Response {
        $comment = Comment::instance($this->getApplicationName())
            ->setFrom($from)
            ->setTo($this->getAddressFrom());
        $form = $this->createForm(UserCommentType::class, $comment)
            ->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $service->sendComment($comment);

                return $this->redirectToHomePage('user.comment.success', request: $request);
            } catch (TransportExceptionInterface $e) {
                return $this->renderFormException('user.comment.error', $e, $logger);
            }
        }

        return $this->render('user/user_comment.html.twig', [
            'form' => $form,
            'message' => false,
        ]);
    }
}
