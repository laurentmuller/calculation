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

namespace App\Traits;

use App\Controller\AbstractController;
use App\Entity\AbstractProperty;
use App\Form\Parameters\AbstractParametersType;
use App\Interfaces\TableInterface;
use App\Parameter\AbstractParameters;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait to edit parameters (Global or User).
 *
 * @phpstan-require-extends AbstractController
 */
trait EditParametersTrait
{
    use CookieTrait;

    /**
     * @template TProperty of AbstractProperty
     * @template TParameters of AbstractParameters<TProperty>
     * @template TFormType of AbstractParametersType
     *
     * @phpstan-param TParameters             $parameters
     * @phpstan-param class-string<TFormType> $type
     */
    protected function renderParameters(
        Request $request,
        AbstractParameters $parameters,
        string $type,
        string $success,
        array $templateParameters
    ): Response {
        $options = ['default_values' => $parameters->getDefaultValues()];
        $form = $this->createForm($type, $parameters, $options)
            ->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$parameters->save()) {
                return $this->redirectToHomePage();
            }
            $response = $this->redirectToHomePage($success);
            $view = $parameters->getDisplay()->getDisplayMode();
            $this->updateCookie($response, TableInterface::PARAM_VIEW, $view);

            return $response;
        }

        $templateParameters['form'] = $form;

        return $this->render('parameters/parameters.html.twig', $templateParameters);
    }
}
