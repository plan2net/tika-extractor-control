<?php
declare(strict_types=1);

namespace Plan2net\TikaExtractorControl\Command;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use ApacheSolrForTypo3\Tika\Service\Extractor\MetaDataExtractor;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * Class FalIndexerCommandController
 * @package Plan2net\TikaExtractorControl\Command
 * @author Wolfgang Klinger <wk@plan2.net>
 */
class FalIndexerCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController
{

    /**
     * @var array
     */
    protected $messageList = [];

    /**
     * @param int $storageId UID of the file storage
     * @param string $folderName Relative folder path to storage
     * @param string $fields A comma separated list of fields to reset (default is 'title')
     * @param bool $includeSubfolders Include subfolders (default is false)
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    public function removeExtractedValueCommand(
        int $storageId,
        string $folderName,
        string $fields = 'title',
        bool $includeSubfolders = false
    ) {
        /** @var ResourceFactory $resourceFactory */
        $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
        /** @var ResourceStorage $storage */
        $storage = $resourceFactory->getStorageObject($storageId);

        if ($storage) {
            $folder = $storage->getFolder($folderName);
            if ($folder) {
                // generate a list of all files including subfolders
                $files = $storage->getFilesInFolder($folder);
                if ($includeSubfolders) {
                    foreach ($storage->getFoldersInFolder($folder) as $subFolder) {
                        $files += $storage->getFilesInFolder($subFolder);
                    }
                }
                $this->removeExtractedValuesFromFiles(explode(',', $fields), $files);
            }
        }

        $this->outputReport();
    }

    /**
     * Remove title from file metadata if the value matches the extracted title
     *
     * @param array $fields
     * @param File[] $files
     */
    protected function removeExtractedValuesFromFiles(array $fields, array $files)
    {
        /** @var MetaDataRepository $metadataRepository */
        $metadataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);

        /** @var File $file */
        foreach ($files as $file) {
            $fileMetadata = $file->_getMetaData();
            foreach ($fields as $field) {
                $currentValue = $fileMetadata[$field] ?? '';
                $extractedValue = $this->extractFieldFromFile($field, $file);
                if ($currentValue && $currentValue === $extractedValue) {
                    $fileMetadata[$field] = '';
                    $metadataRepository->update($file->getUid(), $fileMetadata);
                    $this->messageList[] = 'Removed extracted value "' . $currentValue . '" from file metadata ' . $field . ' for "' . $file->getIdentifier() . '"';
                }
            }
        }
    }

    /**
     * Get the file title from physical file metadata (EXIF, …) via tika's MetaDataExtractor
     *
     * @param string $field
     * @param File $file
     * @return string
     */
    protected function extractFieldFromFile(string $field, File $file): string
    {
        $value = '';
        /** @var MetaDataExtractor $metadataExtractor */
        $metadataExtractor = GeneralUtility::makeInstance(MetaDataExtractor::class);

        if ($metadataExtractor->canProcess($file)) {
            $value = $metadataExtractor->extractMetaData($file)[$field] ?? '';
        }

        return $value;
    }

    protected function outputReport()
    {
        /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
        $flashMessageService = $this->objectManager->get(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
        $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
        foreach ($this->messageList as $messageText) {
            if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) {
                echo($messageText . PHP_EOL);
            } else {
                /** @var FlashMessage $message */
                $message = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                    $messageText,
                    '',
                    FlashMessage::INFO
                );
                $messageQueue->addMessage($message);
            }
        }
    }

}
