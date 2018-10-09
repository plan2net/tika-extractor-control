<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'tika Extractor Control',
    'description' => 'Configuration options for tika metadata extraction',
    'category' => 'backend',
    'version' => '1.0.0',
    'state' => 'stable',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearcacheonload' => true,
    'author' => 'Wolfgang Klinger',
    'author_email' => 'wk@plan2.net',
    'author_company' => 'plan2net GmbH',
    'constraints' =>
        [
            'depends' =>
                [
                    'typo3' => '8.0.0-8.9.99',
                    'tika' => '',
                ],
            'suggests' =>
                [
                ],
            'conflicts' =>
                [
                ],
        ],
];

