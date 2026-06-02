<?php

namespace App\Support;

final class IncidentEventLabel
{
    public static function for(string $eventType): string
    {
        $key = 'incidents.events.'.str_replace('.', '_', $eventType);
        $label = __($key);

        return $label === $key ? $eventType : $label;
    }
}
