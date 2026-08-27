<?php

namespace Imazed\HelpScoutBeacon\Support;

class JavaScript
{
    /**
     * JSON-encode a value for interpolation inside an inline <script>.
     *
     * HEX_TAG is the load-bearing flag: without it a user-controlled value
     * containing "</script>" terminates the script element early and turns
     * the rest of the payload into markup.
     */
    public static function encode(mixed $value): string
    {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE,
        );
    }
}
