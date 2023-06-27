<?php
return [
    'language' => 'ro-RO',
    'sourceLanguage' => 'en-US',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'formatter' => [
            'class' => \yii\i18n\Formatter::class,
            'datetimeFormat' => 'dd/MM/yyyy HH:i',
            'currencyCode' => '$'
        ],
        'i18n' => [
            'translations' => [
//                'yii' => [
//                    'class' => \yii\i18n\PhpMessageSource::class,
//                    'basePath' => '@common/messages/yii',
//                ],
                'app*' => [
                    'class' => \yii\i18n\DbMessageSource::class,
//                    'basePath' => '@common/messages',
                    'on missingTranslation' => [
                       '\common\components\TranslationEventHandler',
                        'handleMissingTranslation'
                    ]
                ],
                '*' => [
                    'class' => \yii\i18n\DbMessageSource::class,
//                    'basePath' => '@common/messages',

                ]
            ],


        ]
    ]
];
