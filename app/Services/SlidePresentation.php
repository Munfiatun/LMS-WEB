<?php

namespace App\Services;

class SlidePresentation
{
    /**
     * Semantic icon SVG paths mapped to concept keywords.
     * Used to add relevant visual cues to slide blocks.
     *
     * @var array<string, array{keywords: list<string>, svg: string}>
     */
    private const CONCEPT_ICONS = [
        'browser' => [
            'keywords' => ['browser', 'chrome', 'firefox', 'safari', 'halaman web', 'web page'],
            'svg' => 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM3.6 9h16.8M3.6 15h16.8M12 3a15.3 15.3 0 0 1 4 9 15.3 15.3 0 0 1-4 9 15.3 15.3 0 0 1-4-9 15.3 15.3 0 0 1 4-9Z',
        ],
        'server' => [
            'keywords' => ['server', 'backend', 'hosting', 'cloud'],
            'svg' => 'M2 5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5Zm0 10a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-4Zm4-8h.01M6 17h.01',
        ],
        'database' => [
            'keywords' => ['database', 'basis data', 'sql', 'mysql', 'postgresql', 'tabel data'],
            'svg' => 'M12 3C7 3 3 5 3 7v10c0 2 4 4 9 4s9-2 9-4V7c0-2-4-4-9-4Zm0 4c5 0 9-2 9-4M3 12c0 2 4 4 9 4s9-2 9-4',
        ],
        'user' => [
            'keywords' => ['pengguna', 'user', 'siswa', 'login', 'autentikasi', 'akun'],
            'svg' => 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
        ],
        'code' => [
            'keywords' => ['kode', 'pemrograman', 'programming', 'variabel', 'fungsi', 'function', 'syntax'],
            'svg' => 'm8 7-5 5 5 5m8-10 5 5-5 5m-3-14-2 18',
        ],
        'network' => [
            'keywords' => ['http', 'request', 'response', 'api', 'rest', 'endpoint', 'url', 'route'],
            'svg' => 'M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2v-4M9 21H5a2 2 0 0 1-2-2v-4m0-6h18M3 15h18',
        ],
        'security' => [
            'keywords' => ['keamanan', 'security', 'enkripsi', 'password', 'token', 'session', 'csrf'],
            'svg' => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z',
        ],
        'file' => [
            'keywords' => ['file', 'dokumen', 'berkas', 'upload', 'unduh', 'download'],
            'svg' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8ZM14 2v6h6M16 13H8m8 4H8m2-8H8',
        ],
        'learn' => [
            'keywords' => ['belajar', 'learning', 'materi', 'pelajaran', 'kurikulum', 'kompetensi'],
            'svg' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        ],
        'ai' => [
            'keywords' => ['ai', 'kecerdasan buatan', 'artificial intelligence', 'machine learning', 'deep learning', 'neural'],
            'svg' => 'M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1.27A7 7 0 0 1 14 22h-4a7 7 0 0 1-6.73-3H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2Zm-2 10a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm4 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z',
        ],
        'frontend' => [
            'keywords' => ['frontend', 'front-end', 'antarmuka', 'ui', 'interface', 'html', 'css', 'javascript'],
            'svg' => 'M4 3h16a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm0 5h16M7 3v5',
        ],
    ];

    /**
     * Terms recognized as important keywords for signaling/highlighting.
     *
     * @var list<string>
     */
    private const SIGNAL_TERMS = [
        'HTTP', 'HTTPS', 'HTML', 'CSS', 'JavaScript', 'PHP', 'Laravel', 'SQL',
        'API', 'REST', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE',
        'Frontend', 'Backend', 'Database', 'Server', 'Browser', 'Client',
        'Request', 'Response', 'Session', 'Cookie', 'Token', 'CSRF',
        'AI', 'Machine Learning', 'Deep Learning', 'Neural Network',
        'GPU', 'CPU', 'NLP', 'IoT',
        'Route', 'Controller', 'Model', 'View', 'Middleware',
        'Variabel', 'Fungsi', 'Array', 'Object', 'Class',
        'Boolean', 'String', 'Integer', 'Float',
    ];

    /**
     * Interpret existing plain-text / Markdown structure without rewriting its content.
     *
     * @return array{layout: string, label: string, blocks: list<array<string, mixed>>, icon: string|null, signalTerms: list<string>}
     */
    public function present(string $title, ?string $content): array
    {
        $blocks = $this->blocks(str_replace(["\r\n", "\r"], "\n", $content ?? ''));
        $types = array_column($blocks, 'type');
        $items = array_values(array_filter($blocks, fn (array $block): bool => $block['type'] === 'item'));
        $layout = 'reading';

        // Determine layout using content heuristics
        if (in_array('code', $types, true)) {
            $layout = 'code';
        } elseif (in_array('image', $types, true)) {
            $layout = 'visual';
        } elseif (in_array('table', $types, true)) {
            $layout = 'comparison';
        } elseif (count($blocks) === 1 && $types[0] === 'quote') {
            $layout = 'quote';
        } elseif (preg_match('/\b(rangkuman|kesimpulan|intisari|summary|recap|yang perlu diingat)\b/iu', $title)) {
            $layout = 'summary';
        } elseif (preg_match('/\b(cek pemahaman|refleksi|checkpoint|evaluasi|latihan)\b/iu', $title)) {
            $layout = 'checkpoint';
        } elseif (count($items) >= 2 && preg_match('/\b(vs\.?|versus|perbandingan|perbedaan|dibandingkan)\b/iu', $title)
            && count(array_filter($items, fn (array $item): bool => $item['label'] !== '')) >= 2) {
            $layout = 'comparison';
        } elseif (count($items) >= 2 && ! in_array(false, array_column($items, 'ordered'), true)
            && preg_match('/\b(proses|alur|langkah|tahapan|mekanisme|sejarah|urutan|workflow|kronologi|prosedur|cara kerja)\b/iu', $title)) {
            $layout = 'process';
        } elseif (preg_match('/\b(contoh|studi kasus|penerapan|case study|ilustrasi|skenario|praktik)\b/iu', $title)) {
            $layout = 'example';
        } elseif (preg_match('/\b(tujuan|pengantar|pengenalan|pendahuluan|overview|apa (itu|yang)|definisi)\b/iu', $title)) {
            $layout = count($items) < 2 && count($blocks) <= 2 && mb_strlen($content ?? '') < 600 ? 'concept' : 'key-points';
        } elseif (count($blocks) <= 1 && mb_strlen($content ?? '') < 450) {
            $layout = 'concept';
        } elseif ($this->hasComparisonStructure($blocks)) {
            $layout = 'comparison';
        } elseif (count($items) >= 3 && ! in_array(false, array_column($items, 'ordered'), true)) {
            $layout = 'process';
        } elseif (count($items) >= 2 || (count($blocks) >= 2 && ! array_diff($types, ['paragraph', 'item']))) {
            $layout = 'key-points';
        }

        $labels = [
            'concept' => 'Kenali konsep', 'key-points' => 'Poin utama', 'process' => 'Ikuti alurnya',
            'comparison' => 'Bandingkan konsep', 'code' => 'Baca kodenya', 'example' => 'Contoh penerapan',
            'visual' => 'Amati visualnya', 'summary' => 'Yang perlu diingat', 'checkpoint' => 'Cek pemahaman',
            'quote' => 'Renungkan', 'reading' => 'Pahami bertahap',
        ];

        // Find matching concept icon
        $icon = $this->findConceptIcon($title.' '.($content ?? ''));

        // Extract signal terms found in content
        $signalTerms = $this->extractSignalTerms($content ?? '');

        return [
            'layout' => $layout,
            'label' => $labels[$layout],
            'blocks' => $blocks,
            'icon' => $icon,
            'signalTerms' => $signalTerms,
        ];
    }

    /**
     * Check if blocks have a comparison structure (items with labels that form opposing pairs).
     *
     * @param  list<array<string, mixed>>  $blocks
     */
    private function hasComparisonStructure(array $blocks): bool
    {
        $labeledItems = array_filter($blocks, fn (array $b): bool => $b['type'] === 'item' && ($b['label'] ?? '') !== '');
        if (count($labeledItems) < 2) {
            return false;
        }

        $labels = array_map(fn (array $b): string => mb_strtolower($b['label']), $labeledItems);
        $comparisonPairs = [
            ['komputer', 'manusia'], ['frontend', 'backend'], ['client', 'server'],
            ['kelebihan', 'kekurangan'], ['pro', 'kontra'], ['input', 'output'],
            ['ml', 'dl'], ['machine learning', 'deep learning'],
        ];
        foreach ($comparisonPairs as [$a, $b]) {
            $foundA = false;
            $foundB = false;
            foreach ($labels as $label) {
                if (str_contains($label, $a)) {
                    $foundA = true;
                }
                if (str_contains($label, $b)) {
                    $foundB = true;
                }
            }
            if ($foundA && $foundB) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find the best matching concept icon SVG path based on content keywords.
     */
    private function findConceptIcon(string $text): ?string
    {
        $lower = mb_strtolower($text);
        $bestMatch = null;
        $bestScore = 0;

        foreach (self::CONCEPT_ICONS as $config) {
            $score = 0;
            foreach ($config['keywords'] as $keyword) {
                if (str_contains($lower, mb_strtolower($keyword))) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $config['svg'];
            }
        }

        return $bestScore > 0 ? $bestMatch : null;
    }

    /**
     * Extract known signal terms that appear in the content.
     *
     * @return list<string>
     */
    private function extractSignalTerms(string $content): array
    {
        $found = [];
        foreach (self::SIGNAL_TERMS as $term) {
            if (mb_stripos($content, $term) !== false && ! in_array($term, $found, true)) {
                $found[] = $term;
            }
        }

        return array_slice($found, 0, 8);
    }

    /**
     * Apply keyword signaling to text — wraps known terms in <mark> tags.
     * Safe for display: input text is expected to already be escaped.
     */
    public function signalText(string $escapedText): string
    {
        $terms = self::SIGNAL_TERMS;
        usort($terms, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        $pattern = '/(?<![\p{L}\p{N}&])('.implode('|', array_map(fn (string $t): string => preg_quote($t, '/'), $terms)).')(?![\p{L}\p{N}])/iu';
        $canonical = array_combine(array_map('mb_strtolower', self::SIGNAL_TERMS), self::SIGNAL_TERMS);
        $seen = [];

        return preg_replace_callback($pattern, function (array $match) use ($canonical, &$seen): string {
            $key = mb_strtolower($match[1]);
            $term = $canonical[$key] ?? $match[1];
            $isAcronym = $term === mb_strtoupper($term);
            if (isset($seen[$key]) || ($isAcronym && $match[1] !== $term)) {
                return $match[0];
            }
            $seen[$key] = true;

            return '<mark class="signal-term">'.$match[1].'</mark>';
        }, $escapedText) ?? $escapedText;
    }

    /** @return list<array<string, mixed>> */
    private function blocks(string $content): array
    {
        $lines = explode("\n", trim($content));
        $blocks = [];
        $paragraph = [];
        $flush = function () use (&$paragraph, &$blocks): void {
            if ($paragraph !== []) {
                foreach ($this->segments(implode("\n", $paragraph)) as $segment) {
                    $blocks[] = $this->textBlock('paragraph', $segment);
                }
                $paragraph = [];
            }
        };

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (preg_match('/^\s*(`{3,}|~{3,})([^\s]*)\s*$/u', $line, $fence)) {
                $flush();
                $code = [];
                while (++$i < count($lines) && ! preg_match('/^\s*'.preg_quote($fence[1], '/').'\s*$/', $lines[$i])) {
                    $code[] = $lines[$i];
                }
                $blocks[] = ['type' => 'code', 'text' => implode("\n", $code), 'language' => $fence[2]];
            } elseif (trim($line) === '') {
                $flush();
            } elseif (preg_match('/^\s*!\[([^\]]*)\]\(([^\s)]+)\)\s*$/u', $line, $image) && $this->safeImage($image[2])) {
                $flush();
                $blocks[] = ['type' => 'image', 'text' => $image[1], 'url' => $image[2]];
            } elseif (isset($lines[$i + 1]) && str_contains($line, '|')
                && preg_match('/^\s*\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)+\|?\s*$/', $lines[$i + 1])) {
                $flush();
                $headers = $this->cells($line);
                $rows = [];
                $i++;
                while (isset($lines[$i + 1]) && str_contains($lines[$i + 1], '|') && trim($lines[$i + 1]) !== '') {
                    $rows[] = $this->cells($lines[++$i]);
                }
                $blocks[] = ['type' => 'table', 'headers' => $headers, 'rows' => $rows];
            } elseif (preg_match('/^\s*(\d+[.)]|[-*•])\s+(.+)$/u', $line, $item)) {
                $flush();
                $text = $item[2];
                while (isset($lines[$i + 1]) && preg_match('/^\s{2,}\S/', $lines[$i + 1]) && ! preg_match('/^\s*(\d+[.)]|[-*•])\s/u', $lines[$i + 1])) {
                    $text .= "\n".trim($lines[++$i]);
                }
                $blocks[] = $this->textBlock('item', $text) + ['ordered' => ctype_digit($item[1][0]), 'marker' => $item[1]];
            } elseif (preg_match('/^\s*>\s?(.*)$/u', $line, $quote)) {
                $flush();
                $text = $quote[1];
                while (isset($lines[$i + 1]) && preg_match('/^\s*>\s?(.*)$/u', $lines[$i + 1], $next)) {
                    $text .= "\n".$next[1];
                    $i++;
                }
                $blocks[] = $this->textBlock('quote', $text);
            } elseif (preg_match('/^#{1,6}\s+(.+)$/u', $line, $heading)) {
                $flush();
                $blocks[] = $this->textBlock('heading', $heading[1]);
            } elseif (preg_match('/^(INFO|CONTOH|INGAT|PENTING|TIPS|PERHATIKAN):\s*(.+)$/u', trim($line), $callout)) {
                $flush();
                $blocks[] = ['type' => 'callout', 'label' => $callout[1], 'text' => $callout[2]];
            } else {
                $paragraph[] = $line;
            }
        }
        $flush();

        return $blocks;
    }

    /** @return list<string> */
    private function segments(string $text): array
    {
        if (mb_strlen($text) <= 550) {
            return [$text];
        }

        $sentences = preg_split('/(?<=[.!?])\\s+(?=[\\p{Lu}\\d])/u', $text) ?: [$text];
        $segments = [];
        $current = '';
        foreach ($sentences as $sentence) {
            if ($current !== '' && mb_strlen($current.' '.$sentence) > 550) {
                $segments[] = $current;
                $current = '';
            }
            $current .= ($current === '' ? '' : ' ').$sentence;
        }
        if ($current !== '') {
            $segments[] = $current;
        }

        return $segments;
    }

    /** @return array{type: string, label: string, text: string} */
    private function textBlock(string $type, string $text): array
    {
        $label = '';
        if (in_array($type, ['item', 'paragraph'], true) && preg_match('/^([^:\n]{2,65}):\s+(.+)$/su', $text, $parts)) {
            $label = $parts[1];
            $text = $parts[2];
        }

        return ['type' => $type, 'label' => $label, 'text' => $text];
    }

    /** @return list<string> */
    private function cells(string $line): array
    {
        return array_map('trim', explode('|', trim(trim($line), '|')));
    }

    private function safeImage(string $url): bool
    {
        return ! preg_match('/[\x00-\x20\\\\]/', $url)
            && ((str_starts_with($url, '/') && ! str_starts_with($url, '//'))
                || (in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['https', 'http'], true) && filter_var($url, FILTER_VALIDATE_URL)));
    }
}
