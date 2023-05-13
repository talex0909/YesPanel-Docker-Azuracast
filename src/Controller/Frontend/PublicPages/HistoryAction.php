<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\VueComponent\NowPlayingComponent;
use Psr\Http\Message\ResponseInterface;

final class HistoryAction
{
    public function __construct(
        private readonly NowPlayingComponent $nowPlayingComponent
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $view = $request->getView();

        // Add station public code.
        $view->fetch(
            'frontend/public/partials/station-custom',
            ['station' => $station]
        );

        return $view->renderVuePage(
            response: $response->withHeader('X-Frame-Options', '*'),
            component: 'Vue_PublicHistory',
            id: 'song-history',
            layout: 'minimal',
            title: __('History') . ' - ' . $station->getName(),
            layoutParams: [
                'page_class' => 'embed station-' . $station->getShortName(),
                'hide_footer' => true,
            ],
            props: $this->nowPlayingComponent->getProps($request),
        );
    }
}
