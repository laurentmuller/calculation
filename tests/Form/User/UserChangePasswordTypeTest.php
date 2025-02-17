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

namespace App\Tests\Form\User;

use App\Constraint\Password;
use App\Constraint\Strength;
use App\Constraint\StrengthValidator;
use App\Entity\User;
use App\Enums\StrengthLevel;
use App\Form\Type\PlainType;
use App\Form\User\UserChangePasswordType;
use App\Service\ApplicationService;
use App\Tests\Form\CustomConstraintValidatorFactory;
use App\Tests\Form\EntityTypeTestCase;
use App\Tests\TranslatorMockTrait;
use Createnl\ZxcvbnBundle\ZxcvbnFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZxcvbnPhp\Zxcvbn;

/**
 * @extends EntityTypeTestCase<User, UserChangePasswordType>
 */
class UserChangePasswordTypeTest extends EntityTypeTestCase
{
    use PasswordHasherExtensionTrait;
    use TranslatorMockTrait;
    use ValidatorExtensionTrait;
    private MockObject&ApplicationService $application;

    private bool $compromisedPassword = false;
    private Password $password;
    private StrengthLevel $score;
    private Strength $strength;
    private MockObject&TranslatorInterface $translator;

    #[\Override]
    protected function setUp(): void
    {
        $this->password = new Password();
        $this->score = StrengthLevel::VERY_WEAK;
        $this->strength = new Strength(StrengthLevel::WEAK);
        $this->translator = $this->createMockTranslator();

        $this->application = $this->createMock(ApplicationService::class);
        $this->application->method('getPasswordConstraint')
            ->willReturnCallback(fn (): Password => $this->password);
        $this->application->method('getStrengthConstraint')
            ->willReturnCallback(fn (): Strength => $this->strength);
        $this->application->method('isCompromisedPassword')
            ->willReturnCallback(fn (): bool => $this->compromisedPassword);

        parent::setUp();
    }

    public function testNotCompromisedInvalid(): void
    {
        $data = [
            'plainPassword' => [
                'first' => 'password',
                'second' => 'password',
            ],
        ];
        $this->compromisedPassword = true;
        $this->score = StrengthLevel::VERY_STRONG;
        $this->strength = new Strength(StrengthLevel::VERY_WEAK);
        $form = $this->factory->create(UserChangePasswordType::class, new User());
        $form->submit($data);
        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isSynchronized());
    }

    public function testNotCompromisedValid(): void
    {
        $data = [
            'plainPassword' => [
                'first' => '187@*QWWék98(AC248aa',
                'second' => '187@*QWWék98(AC248aa',
            ],
        ];
        $this->compromisedPassword = true;
        $this->score = StrengthLevel::VERY_STRONG;
        $this->strength = new Strength(StrengthLevel::VERY_WEAK);
        $form = $this->factory->create(UserChangePasswordType::class, new User());
        $form->submit($data);
        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isSynchronized());
    }

    public function testPasswordEmail(): void
    {
        $data = [
            'plainPassword' => [
                'first' => 'fake@fake.com',
                'second' => 'fake@fake.com',
            ],
        ];
        $this->score = StrengthLevel::VERY_STRONG;
        $this->strength = new Strength(StrengthLevel::VERY_WEAK);
        $this->password = new Password(email: true);
        $form = $this->factory->create(UserChangePasswordType::class, new User());
        $form->submit($data, false);
        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isSynchronized());
    }

    public function testStrengthInvalid(): void
    {
        $data = [
            'plainPassword' => [
                'first' => '187@*QWWék',
                'second' => '187@*QWWék',
            ],
        ];
        $this->score = StrengthLevel::VERY_WEAK;
        $this->strength = new Strength(StrengthLevel::VERY_STRONG);
        $form = $this->factory->create(UserChangePasswordType::class, new User());
        $form->submit($data);
        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isSynchronized());
    }

    public function testStrengthValid(): void
    {
        $data = [
            'plainPassword' => [
                'first' => '187@*QWWék',
                'second' => '187@*QWWék',
            ],
        ];
        $this->score = StrengthLevel::VERY_STRONG;
        $this->strength = new Strength(StrengthLevel::VERY_WEAK);
        $form = $this->factory->create(UserChangePasswordType::class, new User());
        $form->submit($data);
        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isSynchronized());
    }

    #[\Override]
    protected function getData(): array
    {
        return [
            'username' => '',
        ];
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return User::class;
    }

    /**
     * @throws \ReflectionException
     */
    #[\Override]
    protected function getExtensions(): array
    {
        return \array_merge(parent::getExtensions(), [
            $this->getPasswordHasherExtension(),
            $this->getValidatorExtension(),
        ]);
    }

    #[\Override]
    protected function getFormTypeClass(): string
    {
        return UserChangePasswordType::class;
    }

    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        return [
            new PlainType($this->translator),
            new UserChangePasswordType($this->application),
        ];
    }

    protected function getValidatorExtension(): ValidatorExtension
    {
        $constraints = [
            Strength::class => $this->getStrengthValidator(),
        ];

        $validator = Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new CustomConstraintValidatorFactory($constraints))
            ->getValidator();

        return new ValidatorExtension($validator);
    }

    private function getScore(): array
    {
        return [
            'score' => $this->score->value,
        ];
    }

    private function getStrengthValidator(): StrengthValidator
    {
        $service = $this->createMock(Zxcvbn::class);
        $service->method('passwordStrength')
            ->willReturnCallback(fn (): array => $this->getScore());

        $factory = $this->createMock(ZxcvbnFactoryInterface::class);
        $factory->method('createZxcvbn')
            ->willReturn($service);

        return new StrengthValidator($this->translator, $factory);
    }
}
