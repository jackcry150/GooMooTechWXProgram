<?php

namespace app\common\service;

class KnowledgeChunker
{
    public function chunk(string $title, string $content, int $maxChars = 650, int $overlapChars = 80): array
    {
        $title = trim(strip_tags($title));
        $text = $this->normalize($content);
        if ($text === '') {
            return [];
        }

        $maxChars = max(200, $maxChars);
        $overlapChars = max(0, min($overlapChars, intval($maxChars / 2)));
        $chunks = [];
        $length = mb_strlen($text, 'UTF-8');
        $offset = 0;

        while ($offset < $length) {
            $slice = mb_substr($text, $offset, $maxChars, 'UTF-8');
            $slice = trim($slice);
            if ($slice !== '') {
                $chunks[] = [
                    'title' => $title,
                    'content' => $title === '' ? $slice : ('标题：' . $title . "\n内容：" . $slice),
                ];
            }
            $offset += $maxChars - $overlapChars;
        }

        return $chunks;
    }

    private function normalize(string $content): string
    {
        $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = preg_replace('/\s+/u', ' ', $content);
        return trim((string) $content);
    }
}
