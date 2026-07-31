<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;
use Throwable;

class ContentSanitizer
{
    public function sanitize(mixed $html): string
    {
        $html = $this->toHtml($html);
        $sanitized = str($html)->sanitizeHtml()->toString();

        return $this->hardenLinksAndMedia($sanitized);
    }

    private function toHtml(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            return $this->renderNode($content);
        }

        if (is_object($content)) {
            return $this->renderNode((array) $content);
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderNode(array $node): string
    {
        $type = $node['type'] ?? 'doc';
        $children = collect($node['content'] ?? [])
            ->map(fn (mixed $child): string => $this->renderNode(is_array($child) ? $child : (array) $child))
            ->implode('');

        return match ($type) {
            'doc' => $children,
            'paragraph' => "<p>{$children}</p>",
            'heading' => $this->renderHeading($node, $children),
            'blockquote' => "<blockquote>{$children}</blockquote>",
            'bulletList' => "<ul>{$children}</ul>",
            'orderedList' => "<ol>{$children}</ol>",
            'listItem' => "<li>{$children}</li>",
            'hardBreak' => '<br>',
            'image' => $this->renderImage($node),
            'text' => $this->renderText($node),
            default => $children,
        };
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderHeading(array $node, string $children): string
    {
        $level = (int) data_get($node, 'attrs.level', 2);
        $level = in_array($level, [2, 3, 4], true) ? $level : 2;

        return "<h{$level}>{$children}</h{$level}>";
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderImage(array $node): string
    {
        $src = e((string) data_get($node, 'attrs.src', data_get($node, 'attrs.id', '')));
        $alt = e((string) data_get($node, 'attrs.alt', ''));

        return filled($src) ? "<img src=\"{$src}\" alt=\"{$alt}\">" : '';
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderText(array $node): string
    {
        $text = e((string) ($node['text'] ?? ''));

        foreach ($node['marks'] ?? [] as $mark) {
            $mark = is_array($mark) ? $mark : (array) $mark;

            $text = match ($mark['type'] ?? null) {
                'bold' => "<strong>{$text}</strong>",
                'italic' => "<em>{$text}</em>",
                'underline' => "<u>{$text}</u>",
                'link' => $this->renderLink($mark, $text),
                default => $text,
            };
        }

        return $text;
    }

    /**
     * @param  array<string, mixed>  $mark
     */
    private function renderLink(array $mark, string $text): string
    {
        $href = e((string) data_get($mark, 'attrs.href', ''));
        $target = e((string) data_get($mark, 'attrs.target', ''));

        if (blank($href)) {
            return $text;
        }

        $targetAttribute = filled($target) ? " target=\"{$target}\"" : '';

        return "<a href=\"{$href}\"{$targetAttribute}>{$text}</a>";
    }

    private function hardenLinksAndMedia(string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $document = new DOMDocument;

        try {
            $document->loadHTML(
                '<!DOCTYPE html><html><body>'.$html.'</body></html>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
            );
        } catch (Throwable) {
            return '';
        }

        $xpath = new DOMXPath($document);

        foreach ($xpath->query('//a') ?: [] as $link) {
            if (! $link instanceof DOMElement) {
                continue;
            }

            $href = $link->getAttribute('href');

            if (filled($href) && ! $this->isSafeUrl($href)) {
                $link->removeAttribute('href');
            }

            if ($link->getAttribute('target') === '_blank') {
                $link->setAttribute('rel', 'noopener noreferrer');
            }
        }

        foreach ($xpath->query('//img') ?: [] as $image) {
            if (! $image instanceof DOMElement) {
                continue;
            }

            $src = $image->getAttribute('src');

            if (blank($src) || ! $this->isSafeUrl($src)) {
                $image->parentNode?->removeChild($image);
            }
        }

        $body = $document->getElementsByTagName('body')->item(0);
        $output = '';

        foreach ($body?->childNodes ?? [] as $childNode) {
            $output .= $document->saveHTML($childNode);
        }

        return trim($output);
    }

    private function isSafeUrl(string $url): bool
    {
        $url = Str::lower(trim($url));

        if (blank($url)) {
            return false;
        }

        if (Str::startsWith($url, ['/', '#', './', '../'])) {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true);
    }
}
