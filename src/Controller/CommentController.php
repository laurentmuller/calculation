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
use App\Model\Comment;
use App\Service\MailerService;
use App\Util\Utils;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;

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
    #[IsGranted('ROLE_USER')]
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
                // send
                $service->sendComment($comment);
                $this->successTrans('user.comment.success');

                // home page
                return $this->redirectToHomePage();
            } catch (TransportExceptionInterface $e) {
                $message = $this->trans('user.comment.error');
                $context = Utils::getExceptionContext($e);
                $logger->error($message, $context);

                return $this->renderForm('@Twig/Exception/exception.html.twig', [
                    'message' => $message,
                    'exception' => $e,
                ]);
            }
        }
        // render
        return $this->renderForm('user/user_comment.html.twig', [
            'form' => $form,
            'isMail' => $comment->isMail(),
        ]);
    }
}
