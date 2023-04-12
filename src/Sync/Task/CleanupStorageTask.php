<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use Exception;
use League\Flysystem\StorageAttributes;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Throwable;

final class CleanupStorageTask extends AbstractTask
{
    public function __construct(
        private readonly Entity\Repository\StorageLocationRepository $storageLocationRepo,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger);
    }

    public static function getSchedulePattern(): string
    {
        return '24 * * * *';
    }

    public function run(bool $force = false): void
    {
        foreach ($this->iterateStations() as $station) {
            try {
                /** @var Entity\Station $station */
                $this->cleanStationTempFiles($station);
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage(), [
                    'station' => (string)$station,
                ]);
            }
        }

        $storageLocations = $this->iterateStorageLocations(Entity\Enums\StorageLocationTypes::StationMedia);
        foreach ($storageLocations as $storageLocation) {
            try {
                /** @var Entity\StorageLocation $storageLocation */
                $this->cleanMediaStorageLocation($storageLocation);
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage(), [
                    'storageLocation' => (string)$storageLocation,
                ]);
            }
        }
    }

    private function cleanStationTempFiles(Entity\Station $station): void
    {
        $tempDir = $station->getRadioTempDir();
        $finder = new Finder();

        $finder
            ->files()
            ->in($tempDir)
            ->date('before 2 days ago');

        foreach ($finder as $file) {
            $file_path = $file->getRealPath();
            if (false !== $file_path) {
                @unlink($file_path);
            }
        }
    }

    private function cleanMediaStorageLocation(Entity\StorageLocation $storageLocation): void
    {
        $fs = $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

        $allUniqueIdsRaw = $this->em->createQuery(
            <<<'DQL'
                SELECT sm.unique_id
                FROM App\Entity\StationMedia sm
                WHERE sm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $storageLocation)
            ->getArrayResult();

        $allUniqueIds = [];
        foreach ($allUniqueIdsRaw as $row) {
            $allUniqueIds[$row['unique_id']] = $row['unique_id'];
        }

        if (0 === count($allUniqueIds)) {
            $this->logger->notice(
                sprintf('Skipping storage location %s: no media found.', $storageLocation)
            );
            return;
        }

        $removed = [
            'albumart' => 0,
            'waveform' => 0,
        ];

        $cleanupDirs = [
            'albumart' => Entity\StationMedia::DIR_ALBUM_ART,
            'waveform' => Entity\StationMedia::DIR_WAVEFORMS,
        ];

        foreach ($cleanupDirs as $key => $dirBase) {
            try {
                /** @var StorageAttributes $row */
                foreach ($fs->listContents($dirBase, true) as $row) {
                    $path = $row->path();


                    $filename = pathinfo($path, PATHINFO_FILENAME);
                    if (!isset($allUniqueIds[$filename])) {
                        $fs->delete($path);
                        $removed[$key]++;
                    }
                }
            } catch (Exception $e) {
                $this->logger->error(
                    sprintf('Filesystem error: %s', $e->getMessage()),
                    [
                        'exception' => $e,
                    ]
                );
            }
        }

        $this->logger->info('Storage directory cleanup completed.', $removed);
    }
}
