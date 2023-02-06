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

namespace App\Listener;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\PasswordHasher\EventListener\PasswordHasherListener as BaseListener;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * Extends to take account of AbstractType that implements the RepeatedType.
 */
class PasswordHasherListener extends BaseListener
{
    public function registerPassword(FormEvent $event): void
    {
        $form = $event->getForm();
        $parentForm = $form->getParent();
        $mapped = $form->getConfig()->getMapped();

        if (null !== $parentForm && $this->isRepeatedType($parentForm)) {
            $mapped = $parentForm->getConfig()->getMapped();
            $parentForm = $parentForm->getParent();
        }

        if ($mapped) {
            throw new InvalidConfigurationException('The "hash_property_path" option cannot be used on mapped field.');
        }

        if (!($user = $parentForm?->getData()) || !$user instanceof PasswordAuthenticatedUserInterface) {
            throw new InvalidConfigurationException(\sprintf('The "hash_property_path" option only supports "%s" objects, "%s" given.', PasswordAuthenticatedUserInterface::class, \get_debug_type($user)));
        }

        $this->updatePasswords([
            'user' => $user,
            'property_path' => $form->getConfig()->getOption('hash_property_path'),
            'password' => $event->getData(),
        ]);
    }

    private function isRepeatedType(FormInterface $parentForm): bool
    {
        $innerType = $parentForm->getConfig()->getType()->getInnerType();

        return $innerType instanceof RepeatedType ||
            ($innerType instanceof AbstractType && RepeatedType::class === $innerType->getParent());
    }

    private function updatePasswords(array $values): void
    {
        $class = new \ReflectionClass(BaseListener::class);
        $property = $class->getProperty('passwords');
        /** @psalm-var array $passwords */
        $passwords = $property->getValue($this);
        $passwords[] = $values;
        $property->setValue($this, $passwords);
    }
}
