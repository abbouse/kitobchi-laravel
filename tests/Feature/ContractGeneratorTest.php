<?php

namespace Tests\Feature;

use App\Services\ContractGenerator;
use App\Support\Contracts\ContractTemplates;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use ZipArchive;

/** Shartnoma generatori: o'rinbosarlar to'liq almashtiriladi, sanalar avtomatik, ko'rinish sozlamasi ishlaydi. */
class ContractGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        (include base_path('database/migrations/2026_10_22_120000_create_contract_field_values_table.php'))->up();
    }

    private function xml(string $docx): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 't');
        file_put_contents($tmp, $docx);
        $z = new ZipArchive();
        $z->open($tmp);
        $out = $z->getFromName('word/document.xml');
        $z->close();
        unlink($tmp);

        return $out;
    }

    private function text(string $xml): string
    {
        preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/', $xml, $m);

        return html_entity_decode(implode('', $m[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    public function test_every_placeholder_is_filled_in_both_templates(): void
    {
        $gen = app(ContractGenerator::class);
        foreach (array_keys(ContractTemplates::all()) as $slug) {
            $xml = $this->xml($gen->render($slug, $gen->values($slug, CarbonImmutable::parse('2026-10-21'))));
            $this->assertDoesNotMatchRegularExpression('/\{\{|\}\}/', $xml, $slug);
            $this->assertStringContainsString('51503025990027', $this->text($xml), $slug);
        }
    }

    public function test_scooter_schedule_follows_contract_date(): void
    {
        $gen = app(ContractGenerator::class);
        $gen->save('skuter-ijarasi', ['haftalik_tolov' => '350 000', 'haftalik_tolov_sozda' => 'uch yuz ellik ming']);
        $text = $this->text($this->xml($gen->render('skuter-ijarasi', $gen->values('skuter-ijarasi', CarbonImmutable::parse('2026-10-21')))));

        $this->assertStringContainsString('«21» oktyabr 2026 yil', $text);
        $this->assertStringContainsString('21/10/2026', $text);   // 1-hafta boshi
        $this->assertStringContainsString('27/10/2026', $text);   // 1-hafta oxiri
        $this->assertStringContainsString('03/02/2027', $text);   // 16-hafta boshi (21.10 + 105 kun)
        $this->assertStringContainsString('09/02/2027', $text);   // 16-hafta oxiri
        $this->assertStringContainsString('«09» fevral 2027 yilgacha', $text);
        $this->assertStringContainsString('chorshanba kuni', $text); // 21.10.2026 — chorshanba
        $this->assertSame(18, substr_count($text, '350 000'), '3.1 + 2-ilova sarlavhasi + 16 qator');
        $this->assertStringContainsString('yakka tartibdagi tadbirkor', $text);
        $this->assertStringNotContainsString('Nizom asosida', $text);
    }

    public function test_party_values_and_xml_escaping(): void
    {
        $gen = app(ContractGenerator::class);
        $v = $gen->values('dokon-shartnomasi', CarbonImmutable::parse('2026-01-05'), ['sotuvchi_nomi' => 'OOO "Kitob & Co" <test>']);
        $xml = $this->xml($gen->render('dokon-shartnomasi', $v));
        $this->assertStringContainsString('Kitob &amp; Co', $xml);
        $this->assertStringContainsString('05.01.2026', $this->text($xml));
    }

    public function test_public_api_lists_and_downloads_and_respects_visibility(): void
    {
        $this->getJson('/api/v1/kitobchi/contracts')->assertOk()->assertJsonCount(2, 'data');
        $res = $this->get('/api/v1/kitobchi/contracts/skuter-ijarasi/download');
        $res->assertOk();
        $this->assertStringContainsString('attachment; filename="Kitobchi_skuter_ijarasi_shartnomasi_', $res->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('PK', $res->getContent());

        app(ContractGenerator::class)->save('skuter-ijarasi', [ContractGenerator::VISIBILITY_KEY => '0']);
        $this->getJson('/api/v1/kitobchi/contracts')->assertOk()->assertJsonCount(1, 'data');
        $this->get('/api/v1/kitobchi/contracts/skuter-ijarasi/download')->assertNotFound();
        $this->get('/api/v1/kitobchi/contracts/../../.env/download')->assertNotFound();
    }
}
