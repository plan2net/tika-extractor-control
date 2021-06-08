<?php
declare(strict_types=1);

namespace Plan2net\TikaExtractorControl\Resource\Index;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function array_merge;

/**
 * Class Indexer
 *
 * @package Plan2net\TikaExtractorControl\Resource\Index
 * @author Wolfgang Klinger <wk@plan2.net>
 */
class Indexer extends \TYPO3\CMS\Core\Resource\Index\Indexer
{
    protected array $configuration = [];

    public function __construct(ResourceStorage $storage)
    {
        try {
            $this->configuration = GeneralUtility::makeInstance(ExtensionConfiguration::class)
                ->get('tika_extractor_control');
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException $e) {
            // Ignore
        }

        parent::__construct($storage);
    }

    public function extractMetaData(File $fileObject): void
    {
        if (empty($this->configuration['excludeMetadataFields'])) {
            parent::extractMetaData($fileObject);

            return;
        }

        $currentMetaData = $fileObject->getMetaData()->get();
        $metaData = array_merge($fileObject->getMetaData()->get(), $this->getExtractorService()->extractMetaData($fileObject));

        // Exclude specified fields but don't overwrite existing values
        $excludeMetadataFields = explode(',', $this->configuration['excludeMetadataFields']);
        foreach ($metaData as $field => $value) {
            if (\in_array($field, $excludeMetadataFields, true) === true) {
                $metaData[$field] = $currentMetaData[$field] ?? '';
            }
        }

        $fileObject->getMetaData()->add($metaData)->save();
        $this->getFileIndexRepository()->updateIndexingTime($fileObject->getUid());
    }
}
