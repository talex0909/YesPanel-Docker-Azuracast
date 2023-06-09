<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes\Media;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\Flow;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Post(
    path: '/station/{station_id}/podcast/{podcast_id}/episode/{episode_id}/media',
    description: 'Sets the media for a podcast episode.',
    security: OpenApi::API_KEY_SECURITY,
    tags: ['Stations: Podcasts'],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'podcast_id',
            description: 'Podcast ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string')
        ),
        new OA\Parameter(
            name: 'episode_id',
            description: 'Podcast Episode ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string')
        ),
    ],
    responses: [
        new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
    ]
)]
final class PostMediaAction
{
    public function __construct(
        private readonly Entity\Repository\PodcastEpisodeRepository $episodeRepo,
        private readonly StationFilesystems $stationFilesystems
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $podcast_id,
        ?string $episode_id = null
    ): ResponseInterface {
        $station = $request->getStation();

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        if (null !== $episode_id) {
            $episode = $this->episodeRepo->fetchEpisodeForStation($station, $episode_id);

            if (null === $episode) {
                return $response->withStatus(404)
                    ->withJson(Entity\Api\Error::notFound());
            }

            $this->episodeRepo->uploadMedia(
                $episode,
                $flowResponse->getClientFilename(),
                $flowResponse->getUploadedPath(),
                $this->stationFilesystems->getPodcastsFilesystem($station)
            );

            return $response->withJson(Entity\Api\Status::updated());
        }

        return $response->withJson($flowResponse);
    }
}
