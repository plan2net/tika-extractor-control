<?php
declare(strict_types=1);

namespace Plan2net\TikaExtractorControl\Command;

use ApacheSolrForTypo3\Tika\Service\Extractor\MetaDataExtractor;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RemoveFileMetadataCommand
 *
 * @author Wolfgang Klinger <wk@plan2.net>
 */
class RemoveFileMetadataCommand extends Command
{
    protected array $messageList = [];

    protected function configure(): void
    {
        $this->setHelp('Removes extracted metadata for certain fields from files');
        $this
            ->addArgument(
                'storageId',
                InputArgument::REQUIRED,
                'Storage page folder ID'
            )
            ->addArgument(
                'folderName',
                InputArgument::REQUIRED,
                'Folder name inside storage'
            )
            ->addArgument(
                'fields',
                InputArgument::OPTIONAL,
                'Metadata fields to remove',
                'title'
            )
            ->addOption(
                'includeSubFolders',
                'r',
                InputOption::VALUE_OPTIONAL,
                'Recursive including sub folders',
                false
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->removeExtractedValueCommand(
                (int)$input->getArgument('storageId'),
                $input->getArgument('folderName'),
                $input->getArgument('fields'),
                (bool)$input->getOption('includeSubFolders')
            );
        } catch (InsufficientFolderAccessPermissionsException $e) {
            $output->writeln('<error>' . $e->getMessage() . ':</error>');

            return self::FAILURE;
        }

        foreach ($this->messageList as $message) {
            $output->writeln('<info>' . $message . ':</info>');
        }

        return self::SUCCESS;
    }

    public function removeExtractedValueCommand(
        int $storageId,
        string $folderName,
        string $fields = 'title',
        bool $includeSubFolders = false
    ): void {
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $storage = $resourceFactory->getStorageObject($storageId);

        $folder = $storage->getFolder($folderName);
        // Generate a list of all files (eventually including sub folders)
        $files = $storage->getFilesInFolder($folder);
        if ($includeSubFolders) {
            foreach ($storage->getFoldersInFolder($folder) as $subFolder) {
                $files[] = $storage->getFilesInFolder($subFolder);
            }
        }
        $this->removeExtractedValuesFromFiles(explode(',', $fields), $files);
    }

    /**
     * Remove title from file metadata if the value matches the extracted title
     *
     * @param array $fields
     * @param File[] $files
     */
    protected function removeExtractedValuesFromFiles(array $fields, array $files): void
    {
        /** @var MetaDataRepository $metadataRepository */
        $metadataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);

        foreach ($files as $file) {
            $fileMetadata = $file->getMetaData()->get();
            foreach ($fields as $field) {
                $currentValue = $fileMetadata[$field] ?? '';
                try {
                    $extractedValue = $this->extractFieldFromFile($field, $file);
                    if ($currentValue && $currentValue === $extractedValue) {
                        $fileMetadata[$field] = '';
                        $metadataRepository->update($file->getUid(), $fileMetadata);
                        $this->messageList[] = 'Removed extracted value "' . $currentValue . '" from file metadata ' . $field . ' for "' . $file->getIdentifier() . '"';
                    }
                } catch (Exception $e) {
                    $this->messageList[] = 'Failed to removed extracted value "' . $currentValue . '" from file metadata ' . $field . ' for "' . $file->getIdentifier() . '" with error: ' . $e->getMessage();
                }
            }
        }
    }

    /**
     * Get the file title from physical file metadata (EXIF, …) via tika's MetaDataExtractor
     *
     * @throws Exception
     */
    protected function extractFieldFromFile(string $field, File $file): string
    {
        $metadataExtractor = GeneralUtility::makeInstance(MetaDataExtractor::class);
        $value = '';

        if ($metadataExtractor->canProcess($file)) {
            $value = $metadataExtractor->extractMetaData($file)[$field] ?? '';
        }

        return $value;
    }
}
