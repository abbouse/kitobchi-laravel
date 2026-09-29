<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BookEditionVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

class EditionVideoTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        $this->requireMysql();
        parent::setUp();
        Cache::flush();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
    }

    public function test_admin_uploads_video_it_is_processed_and_served_to_app(): void
    {
        if (! is_file('/usr/bin/ffmpeg')) {
            $this->markTestSkipped('ffmpeg yo\'q');
        }
        Storage::fake('public');
        $book = $this->makeBook($this->makeSeller(), $this->makeCategory());
        $book->refresh();
        $this->assertNotNull($book->edition_id, 'Kitob global katalogga ulangan bo\'lishi kerak');

        $admin = Admin::query()->forceCreate([
            'name' => 'Root', 'email' => 'r'.uniqid().'@t.uz', 'password' => bcrypt('x'), 'role' => 'superadmin', 'is_active' => 1,
        ]);
        $file = new UploadedFile(base_path('tests/Fixtures/sample.mp4'), 'promo.mp4', 'video/mp4', null, true);

        $this->actingAs($admin, 'panel')
            ->post("/boshqaruv/catalog/{$book->edition_id}/video", ['video' => $file])
            ->assertSessionHas('success');

        $video = BookEditionVideo::query()->where('edition_id', $book->edition_id)->firstOrFail();
        $this->assertSame(BookEditionVideo::STATUS_READY, $video->status, (string) $video->error);
        $this->assertNull($video->original_path, 'Asl katta fayl o\'chiriladi');
        Storage::disk('public')->assertExists([$video->sd_path, $video->hd_path, $video->poster_path]);
        $this->assertSame(3, $video->duration);

        $data = $this->getJson("/api/v1/kitobchi/share/product/{$book->id}?type=book")->assertOk()->json('data.video');
        $this->assertNotNull($data);
        $this->assertStringContainsString('sd_', $data['sd']);
        $this->assertStringContainsString('poster_', $data['poster']);
        $this->assertTrue($this->getJson("/api/v1/kitobchi/share/product/{$book->id}?type=book")->json('data.has_video'));

        $this->actingAs($admin, 'panel')->delete("/boshqaruv/catalog/{$book->edition_id}/video")->assertSessionHas('success');
        $this->assertNull($this->getJson("/api/v1/kitobchi/share/product/{$book->id}?type=book")->json('data.video'));
        $this->assertDatabaseMissing('book_edition_videos', ['edition_id' => $book->edition_id]);
        // Tag-tugi bilan: papkada hech narsa qolmaydi
        $this->assertFalse(Storage::disk('public')->exists("edition-videos/{$book->edition_id}"));
        Storage::disk('public')->assertMissing([$video->sd_path, $video->hd_path, $video->poster_path]);
    }
}
