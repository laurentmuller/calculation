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

namespace App\Form\Type;

use App\Captcha\AlphaCaptchaInterface;
use App\Traits\SessionTrait;
use App\Util\Utils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Type to display an alpha captcha.
 *
 * @author Laurent Muller
 */
class AlphaCaptchaType extends AbstractType
{
    use SessionTrait;

    private const SESSION_KEY = 'alpha_captcha_answer';

    /**
     * @var AlphaCaptchaInterface
     */
    private $captcha;

    private ?string $previousAnswer = null;

    private ?string $question = null;

    private string $required;

    /**
     * @param \Traversable $captchas
     */
    public function __construct(RequestStack $requestStack, TranslatorInterface $translator, iterable $captchas)
    {
        $this->requestStack = $requestStack;
        $captchas = \iterator_to_array($captchas);
        $this->captcha = $captchas[\array_rand($captchas)];
        $this->required = $translator->trans('required', [], 'captcha');
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        [$this->question, $nextAnswer] = $this->captcha->getChallenge();
        $this->previousAnswer = $this->getSessionString(self::SESSION_KEY);
        $this->setSessionValue(self::SESSION_KEY, $nextAnswer);
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        //$view->vars['label'] = $this->question;
        $view->vars['question'] = $this->question;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                //'class' => 'text-uppercase text-center col-sm-2',
                'data-error' => $this->required,
                'autocomplete' => 'disabled',
                'autocapitalize' => 'none',
                'spellcheck' => 'false',
                'autocorrect' => 'off',
                'maxlength' => 1,
            ],
            'row_attr' => [
                //'class' => 'row no-gutters',
            ],
            'label_attr' => [
                //'class' => 'col-sm-10 col-form-label',
                'class' => 'mb-0',
            ],
            'constraints' => [
                new Callback([$this, 'validate']),
            ],
            'mapped' => false,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return TextType::class;
    }

    public function validate(?string $data, ExecutionContextInterface $context): void
    {
        if (!Utils::isString($data) || false === $this->captcha->checkAnswer($data, $this->previousAnswer)) {
            $context
                ->buildViolation('error')
                ->setTranslationDomain('captcha')
                ->addViolation();
        }
    }
}
