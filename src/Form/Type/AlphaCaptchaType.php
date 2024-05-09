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

namespace App\Form\Type;

use App\Captcha\AlphaCaptchaInterface;
use App\Traits\SessionAwareTrait;
use App\Utils\StringUtils;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Type to display an alpha captcha.
 *
 * @extends AbstractType<TextType>
 */
class AlphaCaptchaType extends AbstractType implements ServiceSubscriberInterface
{
    use ServiceMethodsSubscriberTrait;
    use SessionAwareTrait;

    private const SESSION_KEY = 'alpha_captcha_answer';

    private readonly AlphaCaptchaInterface $captcha;
    private readonly string $dataError;
    private ?string $previousAnswer = null;
    private ?string $question = null;

    /**
     * @param iterable<AlphaCaptchaInterface> $captchas
     */
    public function __construct(
        #[TaggedIterator(AlphaCaptchaInterface::class)]
        iterable $captchas,
        TranslatorInterface $translator
    ) {
        $this->captcha = $this->randomCaptcha($captchas);
        $this->dataError = $translator->trans('required', [], 'captcha');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        [$this->question, $nextAnswer] = $this->captcha->getChallenge();
        $this->previousAnswer = $this->getSessionString(self::SESSION_KEY);
        $this->setSessionValue(self::SESSION_KEY, $nextAnswer);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['question'] = $this->question;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'text-uppercase text-center',
                'data-error' => $this->dataError,
                'autocapitalize' => 'none',
                'autocomplete' => 'off',
                'spellcheck' => 'false',
                'autocorrect' => 'off',
                'minlength' => 1,
                'maxlength' => 1,
            ],
            'label_attr' => [
                'class' => 'mb-0',
            ],
            'constraints' => [
                new Callback(function (?string $data, ExecutionContextInterface $context): void {
                    $this->validate($data, $context);
                }),
            ],
            'mapped' => false,
        ]);
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }

    public function validate(?string $data, ExecutionContextInterface $context): void
    {
        if (!StringUtils::isString($data) || !$this->captcha->checkAnswer($data, (string) $this->previousAnswer)) {
            $context->buildViolation('error')
                ->setTranslationDomain('captcha')
                ->addViolation();
        }
    }

    /**
     * @param iterable<AlphaCaptchaInterface> $captchas
     */
    private function randomCaptcha(iterable $captchas): AlphaCaptchaInterface
    {
        $captchas = $captchas instanceof \Traversable ? \iterator_to_array($captchas) : $captchas;

        return $captchas[\array_rand($captchas)];
    }
}
