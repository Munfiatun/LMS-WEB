<?php

namespace Tests\Unit;

use App\Services\SlidePresentation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SlidePresentationTest extends TestCase
{
    #[DataProvider('layouts')]
    public function test_layout_follows_source_structure(string $title, string $content, string $expected): void
    {
        $result = (new SlidePresentation)->present($title, $content);

        $this->assertSame($expected, $result['layout']);
        $this->assertSame($result, (new SlidePresentation)->present($title, $content));
    }

    public static function layouts(): array
    {
        return [
            'title only' => ['Topik baru', '', 'concept'],
            'short introduction' => ['Topik baru', 'Pengantar singkat.', 'concept'],
            'independent points' => ['Konsep', "- Komponen pertama\n- Komponen kedua", 'key-points'],
            'objectives are not a process' => ['Tujuan Pembelajaran', "1. Memahami konsep.\n2. Menganalisis materi.", 'key-points'],
            'ordered process' => ['Alur permintaan', "1. Mengirim permintaan\n2. Memproses data\n3. Mengembalikan hasil", 'process'],
            'unordered process stays conservative' => ['Alur permintaan', "- Komponen pertama\n- Komponen kedua", 'key-points'],
            'comparison labels' => ['Model A vs. Model B', "- Model A: Berbasis aturan.\n- Model B: Berbasis data.\n- Keduanya memiliki keterbatasan.", 'comparison'],
            'table' => ['Pilihan model', "| A | B |\n| --- | --- |\n| Pertama | Kedua |", 'comparison'],
            'code' => ['Contoh route', "```php\nRoute::get('/materi', fn () => view('materi'));\n```\n\nPenjelasan kode.", 'code'],
            'image' => ['Diagram', "![Hubungan komponen](/storage/diagram.png)\n\nPenjelasan gambar.", 'visual'],
            'example' => ['Contoh penerapan', 'Kasus dari guru.', 'example'],
            'summary' => ['Rangkuman & Intisari', "- Ingat konsep pertama.\n- Ingat konsep kedua.", 'summary'],
            'checkpoint' => ['Cek pemahaman', "Apa komponen penerima?\nA. Pilihan pertama\nB. Pilihan kedua", 'checkpoint'],
            'quote' => ['Refleksi belajar', '> Belajar melalui praktik.', 'quote'],
            'explicit callout' => ['Pembahasan', "## Teori\nPenjelasan.\n\nPENTING: Catatan dari guru.", 'reading'],
        ];
    }

    public function test_code_keeps_indentation_and_does_not_interpret_html(): void
    {
        $code = "<script>alert('example')</script>\n    <div>Isi</div>";
        $result = (new SlidePresentation)->present('Kode HTML', "```html\n{$code}\n```");

        $this->assertSame($code, $result['blocks'][0]['text']);
    }

    public function test_more_than_five_points_and_continuations_are_preserved(): void
    {
        $content = "1. Awal\n   Penjelasan lanjutan\n2. Kedua\n3. Ketiga\n4. Keempat\n5. Kelima\n6. Keenam\n7. Terakhir";
        $result = (new SlidePresentation)->present('Langkah praktik', $content);

        $this->assertCount(7, $result['blocks']);
        $this->assertSame("Awal\nPenjelasan lanjutan", $result['blocks'][0]['text']);
        $this->assertSame('Terakhir', $result['blocks'][6]['text']);
    }

    public function test_unknown_markup_and_unsafe_image_urls_remain_literal_text(): void
    {
        foreach (['![x](javascript:alert)', '![x](data:image/svg+xml,evil)', '![x](//external.test/img.png)', '<img src=x onerror=alert(1)>'] as $source) {
            $result = (new SlidePresentation)->present('Materi', $source);
            $this->assertSame('paragraph', $result['blocks'][0]['type']);
            $this->assertSame($source, $result['blocks'][0]['text']);
        }
    }

    public function test_table_keeps_all_rows_and_cells(): void
    {
        $result = (new SlidePresentation)->present('Perbandingan', "| A | B |\n| --- | --- |\n| Satu | Dua |\n| Tiga | Empat |");

        $this->assertSame(['A', 'B'], $result['blocks'][0]['headers']);
        $this->assertSame([['Satu', 'Dua'], ['Tiga', 'Empat']], $result['blocks'][0]['rows']);
    }

    public function test_long_prose_is_segmented_without_losing_any_sentence(): void
    {
        $sentences = array_fill(0, 16, 'Kalimat sumber ini harus tetap lengkap dan dapat dibaca oleh siswa.');
        $result = (new SlidePresentation)->present('Materi panjang', implode(' ', $sentences));

        $this->assertGreaterThan(1, count($result['blocks']));
        $this->assertSame(implode(' ', $sentences), implode(' ', array_column($result['blocks'], 'text')));
    }

    public function test_null_content_is_a_statement_slide(): void
    {
        $result = (new SlidePresentation)->present('Judul saja', null);

        $this->assertSame('concept', $result['layout']);
        $this->assertSame([], $result['blocks']);
    }

    public function test_signal_text_highlights_each_term_once_without_breaking_markup(): void
    {
        $result = (new SlidePresentation)->signalText(e('Class dan View: class <b>HTML</b> lalu HTML lagi.'));

        $this->assertSame(1, substr_count($result, '<mark class="signal-term">Class</mark>'));
        $this->assertSame(1, substr_count($result, '<mark class="signal-term">View</mark>'));
        $this->assertSame(1, substr_count($result, '<mark class="signal-term">HTML</mark>'));
        $this->assertStringContainsString('&lt;b&gt;', $result);
        $this->assertStringNotContainsString('<b>', $result);
        $this->assertSame(3, substr_count($result, '<mark'));
    }

    public function test_signal_text_matches_acronyms_only_in_uppercase(): void
    {
        $result = (new SlidePresentation)->signalText('Siswa get nilai, lalu kirim GET ke api dan API.');

        $this->assertStringContainsString('Siswa get nilai', $result);
        $this->assertStringContainsString('<mark class="signal-term">GET</mark>', $result);
        $this->assertStringContainsString('ke api dan <mark class="signal-term">API</mark>', $result);
    }
}
