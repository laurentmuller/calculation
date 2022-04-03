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

namespace App\Form\User;

use App\Entity\User;
use App\Form\FormHelper;
use App\Traits\RoleTranslatorTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * User rights type.
 *
 * @author Laurent Muller
 */
class UserRightsType extends RightsType
{
    use RoleTranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(RoleHierarchyInterface $roleHierarchy, bool $isDebug, TranslatorInterface $translator)
    {
        parent::__construct($roleHierarchy, $isDebug);
        $this->setTranslator($translator);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(['data_class' => User::class]);
    }

    /**
     * Translate the enabled state.
     *
     * @param string $enabled the enabled state
     *
     * @return string the translated enabled state
     */
    public function translateEnabled(string $enabled): string
    {
        $enabled = \filter_var($enabled, \FILTER_VALIDATE_BOOLEAN);

        return $this->trans($enabled ? 'common.value_enabled' : 'common.value_disabled');
    }

    /**
     * {@inheritdoc}
     */
    protected function addFormFields(FormHelper $helper): void
    {
        parent::addFormFields($helper);

        $helper->field('username')
            ->addPlainType(true);

        $helper->field('role')
            ->updateOption('transformer', fn (string $role) => $this->translateRole($role))
            ->addPlainType(true);

        $helper->field('enabled')
            ->updateOption('transformer', fn (string $enabled) => $this->translateEnabled($enabled))
            ->addPlainType(true);

        $helper->field('overwrite')
            ->notRequired()
            ->addCheckboxType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): ?string
    {
        return 'user.fields.';
    }
}
