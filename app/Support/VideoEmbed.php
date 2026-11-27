<?php

namespace App\Support;

class VideoEmbed
{
    /** Blok video + (opsional) paragraf keterangan miring atau paragraf kosong tepat di bawahnya */
    private const OEMBED_CAPTION = '~<figure class="media">\s*<oembed url="([^"]*)"></oembed>\s*</figure>'
        . '(?:\s*<p>\s*<(?:i|em)>((?:[^<]|<(?!/?(?:i|em)\b))*)</(?:i|em)>\s*</p>'
        . '|\s*<p>(?:&nbsp;|\s)*</p>)?~i';

    /** Paragraf yang hanya berisi satu link (boleh dibungkus <a>) */
    private const PARA_URL = '~<p>\s*(?:<a [^>]*>)?\s*(https?://[^\s<"]+)\s*(?:</a>)?\s*</p>~i';

    /**
     * Kenali URL video yang didukung.
     * Hasil: ['type' => youtube|short|tiktok, 'id' => ..., 'url' => URL kanonik] atau null.
     */
    public static function parse(string $url): ?array
    {
        $url = trim($url);

        // YouTube biasa
        if (preg_match(
            '~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/)|youtu\.be/)([\w-]{11})(?:[&?#/].*)?$~i',
            $url, $m
        )) {
            return ['type' => 'youtube', 'id' => $m[1], 'url' => 'https://www.youtube.com/watch?v=' . $m[1]];
        }

        // YouTube Shorts
        if (preg_match(
            '~^https?://(?:www\.|m\.)?youtube\.com/shorts/([\w-]{11})(?:[?#/].*)?$~i',
            $url, $m
        )) {
            return ['type' => 'short', 'id' => $m[1], 'url' => 'https://www.youtube.com/shorts/' . $m[1]];
        }

        // TikTok (hanya link lengkap: tiktok.com/@akun/video/123...)
        if (preg_match(
            '~^https?://(?:www\.|m\.)?tiktok\.com/@([\w.-]+)/video/(\d{8,25})(?:[?#/].*)?$~i',
            $url, $m
        )) {
            return [
                'type' => 'tiktok',
                'id'   => $m[2],
                'url'  => 'https://www.tiktok.com/@' . $m[1] . '/video/' . $m[2],
            ];
        }

        return null;
    }

    /** Dipertahankan agar kode lain yang memanggilnya tetap jalan. */
    public static function youtubeId(string $url): ?string
    {
        $v = self::parse($url);

        return ($v && in_array($v['type'], ['youtube', 'short'], true)) ? $v['id'] : null;
    }

    /** Dipakai saat menyimpan artikel (pengganti clean()). */
    public static function sanitize(string $html): string
    {
        // 1. Paragraf berisi link video -> blok video
        $html = self::embedPlainLinks($html);

        // 2. Lindungi blok video dari purifier dengan token
        $urls = [];
        $html = preg_replace_callback(self::OEMBED_CAPTION, function ($m) use (&$urls) {
            $v = self::parse(html_entity_decode($m[1]));
            if (! $v) {
                return '';
            }
            $urls[] = $v['url'];

            $caption = $m[2] ?? '';
            $plain   = trim(html_entity_decode(strip_tags($caption)), " \t\n\r\0\x0B\xC2\xA0");

            return '<p>[[VIDEO:' . (count($urls) - 1) . ']]</p>'
                . ($plain === '' ? '' : '<p><em>' . $caption . '</em></p>');
        }, $html);

        // 3. Sanitasi HTML (mews/purifier)
        [$html, $images] = ContentImages::protect($html);
        $html = clean($html);
        $html = ContentImages::restore($html, $images);

        // 4. Kembalikan blok video dengan URL kanonik yang sudah divalidasi
        return preg_replace_callback('~<p>\s*\[\[VIDEO:(\d+)\]\]\s*</p>~', function ($m) use ($urls) {
            $url = $urls[(int) $m[1]] ?? null;

            return $url ? self::figure($url) : '';
        }, $html);
    }

    /** Dipakai saat menampilkan artikel di halaman publik. */
    public static function render(string $html): string
    {
        $html = self::embedPlainLinks($html);

        return preg_replace_callback(self::OEMBED_CAPTION, function ($m) {
            $v = self::parse(html_entity_decode($m[1]));
            if (! $v) {
                return '';
            }

            $caption = $m[2] ?? '';
            $plain   = trim(html_entity_decode(strip_tags($caption)), " \t\n\r\0\x0B\xC2\xA0");

            return self::iframe($v, $plain === '' ? '' : $caption);
        }, $html);
    }

    public static function embedPlainLinks(string $html): string
    {
        return preg_replace_callback(self::PARA_URL, function ($m) {
            $v = self::parse(html_entity_decode($m[1]));

            return $v ? self::figure($v['url']) : $m[0];
        }, $html);
    }

    private static function figure(string $url): string
    {
        return '<figure class="media"><oembed url="' . e($url) . '"></oembed></figure>';
    }

    private static function iframe(array $v, string $caption = ''): string
    {
        $cap = $caption !== ''
            ? '<figcaption class="text-center text-muted small mt-2">' . $caption . '</figcaption>'
            : '';

        $allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';

        if ($v['type'] === 'tiktok') {
            $player = '<div style="max-width:340px;margin:0 auto"><iframe'
                . ' src="https://www.tiktok.com/embed/v2/' . $v['id'] . '"'
                . ' title="Video TikTok" loading="lazy" allowfullscreen allow="encrypted-media"'
                . ' style="display:block;width:100%;height:740px;border:0"></iframe></div>';
        } elseif ($v['type'] === 'short') {
            $player = '<div style="max-width:360px;margin:0 auto"><div class="ratio" style="--bs-aspect-ratio:177.78%"><iframe'
                . ' src="https://www.youtube-nocookie.com/embed/' . $v['id'] . '"'
                . ' title="YouTube Shorts" loading="lazy" allowfullscreen allow="' . $allow . '"'
                . ' referrerpolicy="strict-origin-when-cross-origin"></iframe></div></div>';
        } else {
            $player = '<div class="ratio ratio-16x9"><iframe'
                . ' src="https://www.youtube-nocookie.com/embed/' . $v['id'] . '"'
                . ' title="Video YouTube" loading="lazy" allowfullscreen'
                . ' referrerpolicy="strict-origin-when-cross-origin"></iframe></div>';
        }

        return '<figure class="video-embed my-4">' . $player . $cap . '</figure>';
    }
}