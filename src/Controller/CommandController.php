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

use App\Attribute\ForSuperAdmin;
use App\Attribute\GetPostRoute;
use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Model\CommandQuery;
use App\Report\CommandsReport;
use App\Service\CommandDataService;
use App\Service\CommandFormService;
use App\Service\CommandService;
use App\Traits\CacheKeyTrait;
use App\Utils\StringUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for the console commands.
 *
 * @phpstan-import-type CommandType from CommandService
 */
#[ForSuperAdmin]
#[Route(path: '/command', name: 'command_')]
class CommandController extends AbstractController
{
    use CacheKeyTrait;

    private const KEY_QUERY_COMMAND = 'last_command';

    /**
     * Render a single command content.
     */
    #[GetRoute(path: '/content', name: 'content')]
    public function content(
        #[MapQueryString]
        CommandQuery $query,
        CommandService $service,
        SessionInterface $session
    ): JsonResponse {
        $name = $query->name ?? '';
        if (!$service->hasCommand($name)) {
            return $this->jsonFalse(['message' => $this->trans('command.list.error', ['%name%' => $name])]);
        }

        $command = $service->getCommand($name);
        $this->saveQuery($session, $query, $command);
        $view = $this->renderView('command/_command.htm.twig', [
            'command' => $command,
            'query' => $query,
        ]);
        $lines = \array_map(\trim(...), \explode(StringUtils::NEW_LINE, \trim($view)));
        $view = \implode('', \array_filter($lines));

        return $this->jsonTrue([
            'content' => $view,
            'query' => $query,
        ]);
    }

    /**
     * Execute a command.
     */
    #[GetPostRoute(path: '/execute', name: 'execute')]
    public function execute(
        #[MapQueryString]
        CommandQuery $query,
        Request $request,
        CommandService $service,
        CommandFormService $formService,
        CommandDataService $dataService
    ): Response {
        $name = $query->name ?? '';
        if (!$service->hasCommand($name)) {
            throw $this->createTranslatedNotFoundException('command.list.error', ['%name%' => $name]);
        }

        $command = $service->getCommand($name);
        $session = $request->getSession();
        $this->saveQuery($session, $query, $command);

        $key = $this->cleanKey('command.execute.' . $name);
        $data = $this->getCommandData($session, $dataService, $key, $command);

        // form
        $form = $formService->createForm($command, $data);
        if ($this->handleRequestForm($request, $form)) {
            try {
                /** @phpstan-var array<string, array|scalar|null> $data */
                $data = $form->getData();
                $session->set($key, $data);
                $parameters = $dataService->createParameters($command, $data);
                $result = $service->execute($name, $parameters);

                return $this->render('command/command_result.html.twig', [
                    'parameters' => $parameters,
                    'command' => $command,
                    'result' => $result,
                    'query' => $query,
                ]);
            } catch (\Exception $e) {
                return $this->renderFormException('command.result.error', $e);
            }
        }

        $view = $form->createView();
        $parameters = [
            'arguments' => $formService->filter($view, CommandFormService::PRIORITY_ARGUMENT),
            'options' => [
                'texts' => $formService->filter($view, CommandFormService::PRIORITY_TEXT),
                'checkboxes' => $formService->filter($view, CommandFormService::PRIORITY_BOOL),
            ],
            'command' => $command,
            'query' => $query,
            'form' => $view,
        ];

        return $this->render('command/command_query.html.twig', $parameters);
    }

    /**
     * Show commands.
     */
    #[IndexRoute]
    public function index(
        Request $request,
        CommandService $service,
        #[MapQueryString]
        ?CommandQuery $query = null
    ): Response {
        $count = $service->count();
        if (0 === $count) {
            return $this->redirectToHomePage('command.list.empty');
        }

        $session = $request->getSession();
        $query ??= $session->get(self::KEY_QUERY_COMMAND, new CommandQuery());
        $command = $this->getCommand($service, $query->name);
        $this->saveQuery($session, $query, $command);

        $root = $this->trans('command.list.available');
        $commands = $service->getGroupedNames($root);
        $parameters = [
            'commands' => $commands,
            'command' => $command,
            'count' => $count,
            'query' => $query,
        ];

        return $this->render('command/commands.html.twig', $parameters);
    }

    /**
     * Export commands to a PDF document.
     */
    #[PdfRoute]
    public function pdf(CommandService $service): Response
    {
        if (0 === $service->count()) {
            return $this->redirectToHomePage('command.list.empty');
        }

        $root = $this->trans('command.list.available');
        $groups = $service->getGroupedCommands($root);

        return $this->renderPdfDocument(new CommandsReport($this, $groups));
    }

    /**
     * @phpstan-return CommandType
     */
    private function getCommand(CommandService $service, ?string $name): array
    {
        if (StringUtils::isString($name) && $service->hasCommand($name)) {
            return $service->getCommand($name);
        }

        return $service->first();
    }

    /**
     * @phpstan-param CommandType $command
     */
    private function getCommandData(
        SessionInterface $session,
        CommandDataService $dataService,
        string $key,
        array $command
    ): array {
        $data = $dataService->createData($command);
        /** @phpstan-var array<string, array|scalar|null> $existing */
        $existing = (array) $session->get($key, []);
        if ([] === $existing) {
            return $data;
        }

        return $dataService->validateData($command, \array_merge($data, $existing));
    }

    /**
     * @phpstan-param CommandType $command
     */
    private function saveQuery(SessionInterface $session, CommandQuery $query, array $command): void
    {
        $query->name = $command['name'];
        $session->set(self::KEY_QUERY_COMMAND, $query);
    }
}
