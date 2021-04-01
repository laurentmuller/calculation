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

use AndreaSprega\Bundle\BreadcrumbBundle\Annotation\Breadcrumb;
use App\Entity\Comment;
use App\Form\User\UserCommentType;
use App\Util\Utils;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to send comments to the web master.
 *
 * @author Laurent Muller
 *
 * @Route("/user")
 */
class CommentController extends AbstractController
{
    /**
     * Send comment to the web master.
     *
     * @Route("/comment", name="user_comment")
     * @IsGranted("ROLE_USER")
     * @Breadcrumb({
     *     {"label" = "index.title", "route" = "homepage"},
     *     {"label" = "user.comment.title" }
     * })
     */
    public function invoke(Request $request, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        $comment = new Comment(false);
        $comment->setSubject($this->getApplicationName())
            ->setFromUser($this->getUser())
            ->setToAddress($this->getAddressFrom());

        // create and handle request
        $form = $this->createForm(UserCommentType::class, $comment);
        if ($this->handleRequestForm($request, $form)) {
            try {
                // send
                $comment->send($mailer);
                $this->succesTrans('user.comment.success');

                // home page
                return $this->redirectToHomePage();
            } catch (TransportExceptionInterface $e) {
                $message = $this->trans('user.comment.error');
                $logger->error($message, [
                    'class' => Utils::getShortName($e),
                    'message' => $e->getMessage(),
                    'code' => (int) $e->getCode(),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                ]);

                return $this->render('@Twig/Exception/exception.html.twig', [
                    'message' => $message,
                    'exception' => $e,
                ]);
            }
        }

        // render
        return $this->render('user/user_comment.html.twig', [
            'form' => $form->createView(),
            'isMail' => $comment->isMail(),
        ]);
    }
}