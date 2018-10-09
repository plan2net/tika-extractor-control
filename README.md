# tika_extractor_control
Configuration options for tika metadata extraction

## What does it do?

The TYPO3 extension package `"apache-solr-for-typo3/tika"` includes a MetaDataExtractor that automatically extracts file metadata when you add a file to FAL.
This extension adds an option to exclude specified fields from extraction (e.g. you don't want the `title` field to be filled automatically) and a CommandController (callable via shell or as Scheduler task) action to remove a previously extracted value (e.g. empty the `title` field if it has been filled automatically by the tika MetaDataExtractor).

## Requirements

A running version of `"apache-solr-for-typo3/tika"`

## Extension configuration

Set `excludeMetadataFields` to e.g. `title,alternative` to exclude these fields from being filled by the tika (or any other) MetaDataExtractor.

*Attention!* 

The `excludeMetadataFields` option does not distinguish between the tika MetaDataExtractor or any other extractor configured.

## CommandController

Call `extbase falindexer:removeextractedvalue <storage ID> <path/in/storage/> --fields=<list,of,fields> --include-subfolders=<0|1>` via shell or create a Scheduler task to reset the specified fields for all files in the given directory.

The default values for the optional flags are as follows:
* `--fields=title`
* `--include-subfolders=0`
