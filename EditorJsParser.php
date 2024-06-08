<?php

declare(strict_types=1);

namespace App;

use function array_key_exists;
use function array_shift;
use function call_user_func_array;
use function implode;
use function json_decode;

final class EditorJsParser
{
    /**
     * @see https://github.com/Archakov06/codex-to-html
     * @see https://github.com/codex-team/editor.js?tab=readme-ov-file
     * @see https://github.com/editor-js/header
     * @see https://github.com/editor-js/nested-list
     * @see https://github.com/editor-js/marker
     * @see https://github.com/editor-js/table
     * @see https://github.com/editor-js/warning
     * @see https://github.com/editor-js/raw
     */
    public function __invoke(string $text): string|null
    {
        $renders = [
            'header' => [$this, 'renderHeader'],
            'list' => [$this, 'renderList'],
            'text' => [$this, 'renderMarker'],
            'table' => [$this, 'renderTable'],
            'warning' => [$this, 'renderWarning'],
            'paragraph' => [$this, 'renderParagraph'],
            'raw' => [$this, 'renderRaw'],
        ];

        $json = json_decode($text, true);
        if (! array_key_exists('blocks', $json)) {
            return null;
        }

        $html = '';

        foreach ($json['blocks'] as $block) {
            if (
                ! array_key_exists('type', $block) ||
                ! array_key_exists('data', $block) ||
                ! array_key_exists($block['type'], $renders)
            ) {
                continue;
            }

            $render = $renders[$block['type']];
            $data = $block['data'];
            $html .= call_user_func_array($render, $data);
        }

        return $html;
    }

    /**
     * @param int<1, 6> $level
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function renderHeader(string $text, int $level): string
    {
        return "<h${level}>${text}</h${level}>";
    }

    /** @SuppressWarnings(PHPMD.UnusedPrivateMethod) */
    private function renderParagraph(string $text): string
    {
        return "<p>{$text}</p>";
    }

    /**
     * @param list<array{content: string, items: list<array{content: string, items: list<mixed>}>}> $items
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function renderList(string $style, array $items): string
    {
        $html = '';
        foreach ($items as $item) {
            $child = '';
            if (! empty($item['items'])) {
                $child = $this->renderList($style, $item['items']);
            }

            $html .= "<li>{$item['content']}{$child}</li>";
        }

        if ($style === 'ordered') {
            return "<ol>{$html}</ol>";
        }

        return "<ul>{$html}</ul>";
    }

    /** @SuppressWarnings(PHPMD.UnusedPrivateMethod) */
    private function renderMarker(string $text): string
    {
        return "<p>{$text}</p>";
    }

    /**
     * @param list<list<string>> $content
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function renderTable(bool $withHeadings, array $content): string
    {
        $head = '';
        if ($withHeadings) {
            $array = array_shift($content);
            $s = '<td>' . implode('</td><td>', $array) . '</td>';
            $head = "<thead><tr>{$s}</tr></thead>";
        }

        $body = '';
        foreach ($content as $item) {
            $t = '<td>' . implode('</td><td>', $item) . '</td>';
            $body .= "<tr>{$t}</tr>";
        }

        $body = "<tbody>{$body}</tbody>";

        return "<table>{$head}{$body}</table>";
    }

    /** @SuppressWarnings(PHPMD.UnusedPrivateMethod) */
    private function renderWarning(string $title, string $message): string
    {
        return "<div role=\"alert\"><h4>{$title}</h4><p>{$message}</p></div>";
    }

    /** @SuppressWarnings(PHPMD.UnusedPrivateMethod) */
    private function renderRaw(string $html): string
    {
        return $html;
    }
}
