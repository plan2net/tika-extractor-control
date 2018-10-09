<?php
declare(strict_types=1);

namespace Plan2net\TikaExtractorControl\Resource\Index;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
 * Class Indexer
 * @package Plan2net\TikaExtractorControl\Resource\Index
 * @author Wolfgang Klinger <wk@plan2.net>
 */
class Indexer extends \TYPO3\CMS\Core\Resource\Index\Indexer
{
    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @param ResourceStorage $storage
     */
    public function __construct(ResourceStorage $storage)
    {
        parent::__construct($storage);

        $this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika_extractor_control']);
    }

    /**
     * Extract metadata for given fileObject
     *
     * @param File $fileObject
     */
    public function extractMetaData(File $fileObject)
    {
        $currentMetaData = $fileObject->_getMetaData();
        $newMetaData = [
            0 => $currentMetaData
        ];

        // Loop through available extractors and fetch metadata for the given file.
        foreach ($this->getExtractionServices() as $service) {
            if ($this->isFileTypeSupportedByExtractor($fileObject, $service) && $service->canProcess($fileObject)) {
                $newMetaData[$service->getPriority()] = $service->extractMetaData($fileObject, $newMetaData);
            }
        }

        // Sort metadata by priority so that merging happens in order of precedence.
        ksort($newMetaData);

        // Merge the collected metadata.
        $metaData = [[]];
        foreach ($newMetaData as $data) {
            $metaData[] = $data;
        }
        $metaData = array_merge(...$metaData);

        // Exclude specified fields
        if (!empty($this->configuration['excludeMetadataFields'])) {
            $excludeMetadataFields = explode(',', $this->configuration['excludeMetadataFields']);
            foreach ($metaData as $field => $value) {
                if (\in_array($field, $excludeMetadataFields, true) === true) {
                    $metaData[$field] = $currentMetaData[$field] ?? '';
                }
            }
        }

        $fileObject->_updateMetaDataProperties($metaData);
        $this->getMetaDataRepository()->update($fileObject->getUid(), $metaData);
        $this->getFileIndexRepository()->updateIndexingTime($fileObject->getUid());
    }
}
