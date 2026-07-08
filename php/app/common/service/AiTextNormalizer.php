<?php

namespace app\common\service;

class AiTextNormalizer
{
    private const CANONICAL_MAP = [
        '草' => '操',
        '艹' => '操',
        '曹' => '操',
        '肏' => '操',
        '尼' => '你',
        '泥' => '你',
        '妮' => '你',
        '妳' => '你',
        '玛' => '妈',
        '媽' => '妈',
        '🐎' => '妈',
        '筆' => '逼',
        '煞' => '傻',
        '訂' => '订',
        '單' => '单',
        '錢' => '钱',
        '貨' => '货',
        '聯' => '联',
        '絡' => '络',
        '個' => '个',
        '幫' => '帮',
        '號' => '号',
        '碼' => '码',
        '隱' => '隐',
        '導' => '导',
        '數' => '数',
        '據' => '据',
        '發' => '发',
        '賠' => '赔',
        '償' => '偿',
        '訴' => '诉',
        '維' => '维',
        '權' => '权',
    ];

    private const PINYIN_MAP = [
        '操' => 'cao',
        '草' => 'cao',
        '曹' => 'cao',
        '艹' => 'cao',
        '你' => 'ni',
        '尼' => 'ni',
        '泥' => 'ni',
        '妈' => 'ma',
        '玛' => 'ma',
        '马' => 'ma',
        '逼' => 'bi',
        '比' => 'bi',
        '币' => 'bi',
        '傻' => 'sha',
        '沙' => 'sha',
        '煞' => 'sha',
        '退' => 'tui',
        '款' => 'kuan',
        '钱' => 'qian',
        '给' => 'gei',
        '打' => 'da',
        '回' => 'hui',
        '查' => 'cha',
        '朋' => 'peng',
        '友' => 'you',
        '买' => 'mai',
        '东' => 'dong',
        '西' => 'xi',
        '别' => 'bie',
        '人' => 'ren',
        '订' => 'ding',
        '单' => 'dan',
        '手' => 'shou',
        '机' => 'ji',
        '号' => 'hao',
        '地' => 'di',
        '址' => 'zhi',
        '微' => 'wei',
        '信' => 'xin',
        '私' => 'si',
        '下' => 'xia',
        '交' => 'jiao',
        '易' => 'yi',
        '便' => 'bian',
        '宜' => 'yi',
        '导' => 'dao',
        '出' => 'chu',
        '数' => 'shu',
        '据' => 'ju',
        '投' => 'tou',
        '诉' => 'su',
        '赔' => 'pei',
        '偿' => 'chang',
    ];

    public function normalize(string $text): array
    {
        $raw = mb_strtolower(trim($this->toHalfWidth($text)), 'UTF-8');
        $compact = $this->compact($raw);
        $canonical = $this->canonicalize($compact);
        $pinyinMeta = $this->toPinyinParts($canonical);
        $pinyinParts = $pinyinMeta['parts'];

        return [
            'raw' => $raw,
            'compact' => $compact,
            'canonical' => $canonical,
            'pinyin' => implode('', $pinyinParts),
            'pinyinInitials' => $this->initials($pinyinParts),
            'pinyinComplete' => $pinyinMeta['hanCount'] === $pinyinMeta['mappedHanCount'],
        ];
    }

    public function normalizeKeyword(string $word): string
    {
        return $this->normalize($word)['canonical'];
    }

    public function keywordViews(string $word): array
    {
        return $this->normalize($word);
    }

    private function toHalfWidth(string $text): string
    {
        if (function_exists('mb_convert_kana')) {
            return mb_convert_kana($text, 'asKV', 'UTF-8');
        }
        return $text;
    }

    private function compact(string $text): string
    {
        $text = preg_replace('/[\x{200B}-\x{200F}\x{FEFF}\s]+/u', '', $text);
        $text = preg_replace('/[^\p{Han}a-z0-9]+/u', '', (string) $text);
        return (string) $text;
    }

    private function canonicalize(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($chars)) {
            return $text;
        }

        $result = '';
        foreach ($chars as $char) {
            $result .= self::CANONICAL_MAP[$char] ?? $char;
        }
        return $result;
    }

    private function toPinyinParts(string $text): array
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($chars)) {
            return ['parts' => [], 'hanCount' => 0, 'mappedHanCount' => 0];
        }

        $parts = [];
        $hanCount = 0;
        $mappedHanCount = 0;
        foreach ($chars as $char) {
            if (preg_match('/^\p{Han}$/u', $char)) {
                $hanCount++;
            }
            if (isset(self::PINYIN_MAP[$char])) {
                $parts[] = self::PINYIN_MAP[$char];
                if (preg_match('/^\p{Han}$/u', $char)) {
                    $mappedHanCount++;
                }
            } elseif (preg_match('/^[a-z0-9]$/', $char)) {
                $parts[] = $char;
            } else {
                $parts[] = '|';
            }
        }
        return ['parts' => $parts, 'hanCount' => $hanCount, 'mappedHanCount' => $mappedHanCount];
    }

    private function initials(array $parts): string
    {
        $initials = '';
        foreach ($parts as $part) {
            $part = (string) $part;
            $initials .= $part === '|' ? '|' : substr($part, 0, 1);
        }
        return $initials;
    }
}
