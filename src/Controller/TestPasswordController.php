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
use App\Constraint\Captcha;
use App\Constraint\Password;
use App\Constraint\Strength;
use App\Entity\User;
use App\Enums\StrengthLevel;
use App\Form\Type\AlphaCaptchaType;
use App\Form\Type\CaptchaImageType;
use App\Interfaces\RoleInterface;
use App\Interfaces\UserInterface;
use App\Service\CaptchaImageService;
use App\Traits\StrengthLevelTranslatorTrait;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

#[Route(path: '/test', name: 'test_')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class TestPasswordController extends AbstractController
{
    use StrengthLevelTranslatorTrait;

    /**
     * Test password validation.
     *
     * @throws \Exception
     */
    #[GetPostRoute(path: '/password', name: 'password')]
    public function password(
        #[CurrentUser]
        User $user,
        Request $request,
        CaptchaImageService $service
    ): Response {
        $password = new Password(all: true);
        $options = Password::ALLOWED_OPTIONS;
        $strengthLevel = StrengthLevel::MEDIUM;
        $strength = new Strength($strengthLevel);
        $listener = static function (PreSubmitEvent $event) use ($options, $password, $strength): void {
            /** @phpstan-var array $data */
            $data = $event->getData();
            foreach ($options as $option) {
                $password->setOption($option, (bool) ($data[$option] ?? false));
            }
            $level = (int) $data['level'];
            $strength->minimum = StrengthLevel::tryFrom($level) ?? StrengthLevel::NONE;
        };
        $data = [
            'user_name' => $user->getDisplay(),
            'user_email' => $user->getEmail(),
            'input' => 'aB123456#*/82568A',
            'level' => $strengthLevel,
        ];
        foreach ($options as $option) {
            $data[$option] = true;
        }
        $helper = $this->createFormHelper('password.', $data);
        $helper->listenerPreSubmit($listener);
        $helper->field('input')
            ->widgetClass('password-strength')
            ->updateOption('prepend_icon', 'fa-solid fa-lock')
            ->updateAttributes([
                'data-url' => $this->generateUrl(route: 'ajax_password', referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
                'data-strength' => $strengthLevel->value,
            ])
            ->minLength(UserInterface::MIN_PASSWORD_LENGTH)
            ->maxLength(UserInterface::MAX_USERNAME_LENGTH)
            ->constraints(
                new Length(min: UserInterface::MIN_PASSWORD_LENGTH, max: UserInterface::MAX_USERNAME_LENGTH),
                $password,
                $strength
            )->addTextType();
        foreach ($options as $option) {
            $helper->field($option)
                ->updateAttribute('data-validation', $option)
                ->widgetClass('password-option')
                ->addCheckboxType();
        }

        $helper->field('user_name')
            ->updateOption('prepend_icon', 'fa-regular fa-user')
            ->label('user.fields.username_full')
            ->addPlainType();
        $helper->field('user_email')
            ->addHiddenType();
        $helper->field('level')
            ->label('password.strengthLevel')
            ->updateOption('prepend_icon', 'fa-solid fa-hand-fist')
            ->addEnumType(StrengthLevel::class);
        $helper->field('captcha')
            ->label('captcha.label')
            ->constraints(new NotBlank(), new Captcha())
            ->updateOption('image', $service->generateImage())
            ->add(CaptchaImageType::class);
        $helper->field('alpha')
            ->label('captcha.label')
            ->add(AlphaCaptchaType::class);

        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            /** @phpstan-var array<string, mixed> $data */
            $data = $form->getData();
            $message = $this->trans('password.success');
            $message .= '<ul>';
            foreach ($options as $option) {
                if (true === $data[$option]) {
                    $message .= '<li>' . $this->trans('password.' . $option) . '</li>';
                }
            }
            /** @phpstan-var StrengthLevel $level */
            $level = $data['level'];
            if (StrengthLevel::NONE !== $level) {
                $message .= '<li>';
                $message .= $this->trans('password.strengthLevel');
                $message .= ' : ';
                $message .= $this->translateLevel($level);
                $message .= '</li>';
            }
            $message .= '</ul>';

            return $this->redirectToHomePage($message);
        }

        return $this->render('test/password.html.twig', ['form' => $form]);
    }
}
