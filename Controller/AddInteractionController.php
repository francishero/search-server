<?php

/*
 * This file is part of the Apisearch Server
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Apisearch\Server\Controller;

use Apisearch\Exception\InvalidFormatException;
use Apisearch\Http\Http;
use Apisearch\Model\AppUUID;
use Apisearch\Repository\RepositoryReference;
use Apisearch\Server\Domain\Command\AddInteraction;
use Apisearch\User\Interaction;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AddInteractionController.
 */
class AddInteractionController extends ControllerWithBus
{
    /**
     * Add an interaction.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->query;
        $interactionAsArray = $this->getRequestContentObject(
            $request,
            null,
            InvalidFormatException::appUUIDFormatNotValid(),
            []
        );

        $this
            ->commandBus
            ->handle(new AddInteraction(
                RepositoryReference::create(
                    AppUUID::createById($query->get(Http::APP_ID_FIELD, ''))
                ),
                $query->get(Http::TOKEN_FIELD, ''),
                Interaction::createFromArray($interactionAsArray)
            ));

        return new JsonResponse('Interaction added', 200);
    }
}
