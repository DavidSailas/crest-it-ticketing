<?php

namespace App\Support;

use App\Models\Supply;
use Illuminate\Support\HtmlString;

/**
 * Wraps the words someone searched for in <mark> so they can see why an item matched.
 * The text is escaped first, so it is safe to print with {!! !!}.
 */
class SearchHighlight
{
    public static function apply(?string $text, ?string $term): HtmlString
    {
        $text = (string) $text;
        $tokens = Supply::searchTokens($term);

        if ($text === '' || $tokens === []) {
            return new HtmlString(e($text));
        }

        // Longest words first so "680xl" is matched before "680".
        usort($tokens, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $pattern = '/('.implode('|', array_map(fn ($t) => preg_quote($t, '/'), $tokens)).')/iu';

        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return new HtmlString(e($text));
        }

        $html = '';
        foreach ($parts as $i => $part) {
            $html .= $i % 2 === 1
                ? '<mark class="rounded-sm bg-yellow-100 px-0.5 text-inherit">'.e($part).'</mark>'
                : e($part);
        }

        return new HtmlString($html);
    }
}
