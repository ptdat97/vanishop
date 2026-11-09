<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Blocks;

use JsonException;

final class RichTextRenderer
{
    public static function render(string $value): string
    {
        $document = self::document($value);

        return $document === null ? nl2br(e($value)) : self::renderNode($document);
    }

    public static function textLength(string $value): int
    {
        $document = self::document($value);

        return mb_strlen($document === null ? $value : self::textContent($document));
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function document(string $value): ?array
    {
        try {
            $document = json_decode($value, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($document) && ($document['type'] ?? null) === 'doc' && is_array($document['content'] ?? null)
            ? $document
            : null;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function renderNode(array $node, int $depth = 0): string
    {
        if ($depth > 64) {
            return '';
        }

        if (($node['type'] ?? null) === 'text') {
            $content = e(is_string($node['text'] ?? null) ? $node['text'] : '');
            foreach ((array) ($node['marks'] ?? []) as $mark) {
                $content = match (is_array($mark) ? ($mark['type'] ?? '') : '') {
                    'bold' => '<strong>'.$content.'</strong>',
                    'italic' => '<em>'.$content.'</em>',
                    'strike' => '<s>'.$content.'</s>',
                    'code' => '<code>'.$content.'</code>',
                    default => $content,
                };
            }

            return $content;
        }

        $children = '';
        foreach ((array) ($node['content'] ?? []) as $child) {
            if (is_array($child)) {
                $children .= self::renderNode($child, $depth + 1);
            }
        }

        return match ($node['type'] ?? '') {
            'doc' => $children,
            'paragraph' => '<p class="mb-3 last:mb-0">'.$children.'</p>',
            'heading' => self::renderHeading($node, $children),
            'bulletList' => '<ul class="mb-3 list-disc space-y-1 pl-6">'.$children.'</ul>',
            'orderedList' => '<ol class="mb-3 list-decimal space-y-1 pl-6">'.$children.'</ol>',
            'listItem' => '<li>'.$children.'</li>',
            'blockquote' => '<blockquote class="my-3 border-l-2 border-slate-300 pl-4 text-slate-600">'.$children.'</blockquote>',
            'codeBlock' => '<pre class="my-3 overflow-x-auto bg-slate-100 p-3"><code>'.e(self::textContent($node)).'</code></pre>',
            'horizontalRule' => '<hr class="my-4 border-slate-200">',
            'hardBreak' => '<br>',
            default => $children,
        };
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function renderHeading(array $node, string $children): string
    {
        $level = is_array($node['attrs'] ?? null) ? ($node['attrs']['level'] ?? 2) : 2;
        $tag = $level === 3 ? 'h4' : 'h3';

        return "<{$tag} class=\"mb-3 font-semibold\">{$children}</{$tag}>";
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function textContent(array $node, int $depth = 0): string
    {
        if ($depth > 64) {
            return '';
        }

        if (($node['type'] ?? null) === 'text') {
            return is_string($node['text'] ?? null) ? $node['text'] : '';
        }

        if (($node['type'] ?? null) === 'hardBreak') {
            return "\n";
        }

        $text = '';
        foreach ((array) ($node['content'] ?? []) as $child) {
            if (is_array($child)) {
                $text .= self::textContent($child, $depth + 1);
            }
        }

        return $text;
    }
}
