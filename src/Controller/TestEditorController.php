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

use App\Attribute\GetPostRoute;
use App\Entity\User;
use App\Enums\Importance;
use App\Form\Type\SimpleEditorType;
use App\Interfaces\RoleInterface;
use App\Service\MailerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/test', name: 'test_')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class TestEditorController extends AbstractController
{
    /**
     * Test sending notification mail.
     */
    #[GetPostRoute(path: '/editor', name: 'editor')]
    public function editor(
        Request $request,
        #[CurrentUser]
        User $user,
        MailerService $service,
        LoggerInterface $logger
    ): Response {
        $data = [
            'email' => $user->getEmail(),
            'importance' => Importance::MEDIUM,
        ];
        $helper = $this->createFormHelper('user.fields.', $data);
        $helper->field('email')
            ->addEmailType();
        $helper->field('importance')
            ->label('importance.name')
            ->addEnumType(Importance::class);
        $helper->field('message')
            ->updateAttribute('minlength', 10)
            ->add(SimpleEditorType::class);
        $helper->field('attachments')
            ->updateOptions([
                'multiple' => true,
                'maxfiles' => 3,
                'maxsize' => '10mi',
                'maxsizetotal' => '30mi'])
            ->notRequired()
            ->addFileType();
        $form = $helper->createForm();

        if ($this->handleRequestForm($request, $form)) {
            /**
             * @var array{email: string, message: string, importance: Importance, attachments: UploadedFile[]} $data
             */
            $data = $form->getData();

            try {
                $service->sendNotification(
                    $data['email'],
                    $user,
                    $data['message'],
                    $data['importance'],
                    $data['attachments']
                );

                return $this->redirectToHomePage('user.comment.success');
            } catch (TransportExceptionInterface $e) {
                return $this->renderFormException('user.comment.error', $e, $logger);
            }
        }

        return $this->render('test/editor.html.twig', [
            'form' => $form,
        ]);
    }
}
