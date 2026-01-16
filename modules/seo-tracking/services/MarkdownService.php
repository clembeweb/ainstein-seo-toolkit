<?php

namespace Modules\SeoTracking\Services;

/**
 * MarkdownService
 * Parser markdown semplice per report AI
 */
class MarkdownService
{
    /**
     * Converte markdown in HTML
     */
    public function parse(string $markdown): string
    {
        $html = $markdown;

        // Escape HTML entities first
        $html = htmlspecialchars($html, ENT_QUOTES, 'UTF-8');

        // Code blocks (```)
        $html = preg_replace_callback('/```(\w*)\n(.*?)```/s', function ($matches) {
            $lang = $matches[1] ?: 'text';
            $code = $matches[2];
            return '<pre class="language-' . $lang . '"><code>' . $code . '</code></pre>';
        }, $html);

        // Inline code
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

        // Headers
        $html = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $html);
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);

        // Bold and Italic
        $html = preg_replace('/\*\*\*(.+?)\*\*\*/s', '<strong><em>$1</em></strong>', $html);
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $html);
        $html = preg_replace('/___(.+?)___/s', '<strong><em>$1</em></strong>', $html);
        $html = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/_(.+?)_/s', '<em>$1</em>', $html);

        // Strikethrough
        $html = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $html);

        // Blockquotes
        $html = preg_replace('/^&gt; (.+)$/m', '<blockquote>$1</blockquote>', $html);

        // Horizontal rules
        $html = preg_replace('/^[-*_]{3,}$/m', '<hr>', $html);

        // Unordered lists
        $html = preg_replace_callback('/^([\t ]*[-*+] .+\n?)+/m', function ($matches) {
            $items = preg_split('/\n/', trim($matches[0]));
            $listItems = '';
            foreach ($items as $item) {
                $item = preg_replace('/^[\t ]*[-*+] /', '', $item);
                $listItems .= '<li>' . $item . '</li>';
            }
            return '<ul>' . $listItems . '</ul>';
        }, $html);

        // Ordered lists
        $html = preg_replace_callback('/^([\t ]*\d+\. .+\n?)+/m', function ($matches) {
            $items = preg_split('/\n/', trim($matches[0]));
            $listItems = '';
            foreach ($items as $item) {
                $item = preg_replace('/^[\t ]*\d+\. /', '', $item);
                $listItems .= '<li>' . $item . '</li>';
            }
            return '<ol>' . $listItems . '</ol>';
        }, $html);

        // Tables
        $html = preg_replace_callback('/^\|(.+)\|\n\|[-:\| ]+\|\n((?:\|.+\|\n?)+)/m', function ($matches) {
            $headers = array_map('trim', explode('|', trim($matches[1], '|')));
            $rows = explode("\n", trim($matches[2]));

            $table = '<table><thead><tr>';
            foreach ($headers as $header) {
                $table .= '<th>' . $header . '</th>';
            }
            $table .= '</tr></thead><tbody>';

            foreach ($rows as $row) {
                if (empty(trim($row))) continue;
                $cells = array_map('trim', explode('|', trim($row, '|')));
                $table .= '<tr>';
                foreach ($cells as $cell) {
                    $table .= '<td>' . $cell . '</td>';
                }
                $table .= '</tr>';
            }

            $table .= '</tbody></table>';
            return $table;
        }, $html);

        // Links [text](url)
        $html = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $html);

        // Paragraphs (double newlines)
        $html = preg_replace('/\n{2,}/', '</p><p>', $html);

        // Single line breaks
        $html = preg_replace('/\n/', '<br>', $html);

        // Wrap in paragraph if not starting with block element
        if (!preg_match('/^<(h[1-6]|ul|ol|pre|table|blockquote|hr)/', $html)) {
            $html = '<p>' . $html . '</p>';
        }

        // Clean up empty paragraphs
        $html = preg_replace('/<p>\s*<\/p>/', '', $html);

        // Fix nested tags issues
        $html = preg_replace('/<\/ul>\s*<ul>/', '', $html);
        $html = preg_replace('/<\/ol>\s*<ol>/', '', $html);
        $html = preg_replace('/<\/blockquote>\s*<blockquote>/', '<br>', $html);

        return $html;
    }

    /**
     * Estrae testo semplice da markdown
     */
    public function toPlainText(string $markdown): string
    {
        // Rimuovi code blocks
        $text = preg_replace('/```[\s\S]*?```/', '', $markdown);

        // Rimuovi inline code
        $text = preg_replace('/`([^`]+)`/', '$1', $text);

        // Rimuovi headers markers
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);

        // Rimuovi bold/italic
        $text = preg_replace('/[*_]{1,3}([^*_]+)[*_]{1,3}/', '$1', $text);

        // Rimuovi strikethrough
        $text = preg_replace('/~~(.+?)~~/', '$1', $text);

        // Rimuovi blockquote markers
        $text = preg_replace('/^>\s+/m', '', $text);

        // Rimuovi list markers
        $text = preg_replace('/^[\t ]*[-*+]\s+/m', '', $text);
        $text = preg_replace('/^[\t ]*\d+\.\s+/m', '', $text);

        // Estrai testo dai link
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text);

        // Rimuovi HR
        $text = preg_replace('/^[-*_]{3,}$/m', '', $text);

        // Normalizza whitespace
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Estrae sommario (prime N righe)
     */
    public function extractSummary(string $markdown, int $maxLength = 200): string
    {
        $text = $this->toPlainText($markdown);

        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        // Tronca alla fine di una frase se possibile
        $truncated = mb_substr($text, 0, $maxLength);
        $lastPeriod = mb_strrpos($truncated, '.');

        if ($lastPeriod !== false && $lastPeriod > $maxLength * 0.6) {
            return mb_substr($truncated, 0, $lastPeriod + 1);
        }

        // Altrimenti tronca alla fine di una parola
        $lastSpace = mb_strrpos($truncated, ' ');

        if ($lastSpace !== false) {
            return mb_substr($truncated, 0, $lastSpace) . '...';
        }

        return $truncated . '...';
    }
}
