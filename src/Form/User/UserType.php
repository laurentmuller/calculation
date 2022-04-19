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

namespace App\Form\User;

use App\Entity\User;
use App\Form\AbstractEntityType;
use App\Form\FormHelper;
use App\Form\Type\EnabledDisabledType;
use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * User edit type.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityType<User>
 */
class UserType extends AbstractEntityType
{
    /**
     * Constructor.
     */
    public function __construct(private readonly UserPasswordHasherInterface $hasher, private readonly DateTimeFormatter $formatter)
    {
        parent::__construct(User::class);
    }

    /**
     * Format the last login date.
     */
    public function formatLastLogin(\DateTimeInterface|string $lastLogin): ?string
    {
        if ($lastLogin instanceof \DateTimeInterface) {
            return $this->formatter->formatDiff($lastLogin, new \DateTime());
        }

        return null;
    }

    /**
     * Handles the preset data event.
     */
    public function onPreSetData(FormEvent $event): void
    {
        /** @var User $user */
        $user = $event->getData();
        $form = $event->getForm();
        if ($user->isNew()) {
            $form->remove('lastLogin');
        } else {
            $form->remove('plainPassword');
        }
    }

    /**
     * Handles the submit event.
     */
    public function onSubmit(SubmitEvent $event): void
    {
        $form = $event->getForm();
        if ($form->has('plainPassword')) {
            /** @var User $user */
            $user = $event->getData();
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $encodedPassword = $this->hasher->hashPassword($user, $plainPassword);
            $user->setPassword($encodedPassword);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('id')
            ->addHiddenType();

        $helper->field('username')
            ->minLength(2)
            ->maxLength(180)
            ->add(UserNameType::class);

        $helper->field('email')
            ->maxLength(180)
            ->addEmailType();

        $helper->field('plainPassword')
            ->notMapped()
            ->addRepeatPasswordType();

        $helper->field('role')
            ->add(RoleChoiceType::class);

        $helper->field('enabled')
            ->add(EnabledDisabledType::class);

        $helper->field('lastLogin')
            ->updateOption('transformer', fn (\DateTimeInterface|string $lastLogin): ?string => $this->formatLastLogin($lastLogin))
            ->updateOption('empty_value', 'common.value_none')
            ->addPlainType(true);

        $helper->field('imageFile')
            ->updateOption('maxsize', '10mi')
            ->addVichImageType();

        // add listeners
        $helper->addPreSetDataListener(function (FormEvent $event): void {
            $this->onPreSetData($event);
        });
        $helper->addSubmitListener(function (SubmitEvent $event): void {
            $this->onSubmit($event);
        });
    }
}
