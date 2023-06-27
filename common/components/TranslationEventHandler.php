<?php

namespace common\components;

class TranslationEventHandler
{
    public static function handleMissingTranslation(\yii\i18n\MissingTranslationEvent $event) {
        $event->translatedMessage = '[[' . $event->message . '-' . $event->category . '-' . $event->language . ']]';
    }

}