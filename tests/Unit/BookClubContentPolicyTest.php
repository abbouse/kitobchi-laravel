<?php

namespace Tests\Unit;

use App\Services\BookClubContentPolicy;
use Tests\TestCase;

class BookClubContentPolicyTest extends TestCase
{
    private BookClubContentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new BookClubContentPolicy;
    }

    public function test_trusted_platform_ad_is_not_hard_blocked(): void
    {
        $result = $this->policy->inspect('Yangi kitob aksiyasi: https://instagram.com/kitobchi');

        $this->assertFalse($result['hard_risk']);
        $this->assertSame(['instagram.com'], $result['trusted_domains']);
        $this->assertSame([], $result['untrusted_domains']);
        $this->assertTrue($result['has_trusted_ad_destination']);
    }

    public function test_unknown_external_link_is_hard_blocked(): void
    {
        $result = $this->policy->inspect('Bepul sovrin uchun https://unknown-gift.ru/win sahifasiga kiring');

        $this->assertTrue($result['hard_risk']);
        $this->assertSame(['unknown-gift.ru'], $result['untrusted_domains']);
        $this->assertContains('untrusted_link', $result['signals']);
    }

    public function test_well_known_platform_mention_without_link_is_recognized(): void
    {
        $result = $this->policy->inspect('Instagram sahifamizda yangi kitoblar aksiyasi boshlandi');

        $this->assertContains('instagram', $result['trusted_platform_mentions']);
        $this->assertTrue($result['has_trusted_ad_destination']);
        $this->assertFalse($result['hard_risk']);
    }

    public function test_shortened_and_obfuscated_links_are_suspicious(): void
    {
        $shortened = $this->policy->inspect('Bu yerga kiring: https://bit.ly/free-book');
        $obfuscated = $this->policy->inspect('Sovrin uchun bad-domain[.]com ga kiring');

        $this->assertTrue($shortened['hard_risk']);
        $this->assertContains('shortened_link', $shortened['signals']);
        $this->assertTrue($obfuscated['hard_risk']);
        $this->assertContains('obfuscated_link', $obfuscated['signals']);
    }

    public function test_normal_book_discussion_has_no_risk_signal(): void
    {
        $result = $this->policy->inspect("Bu asardagi qahramonning qarori menga juda qiziq tuyuldi. Siz nima deb o'ylaysiz?");

        $this->assertFalse($result['hard_risk']);
        $this->assertSame([], $result['signals']);
    }

    public function test_obvious_profanity_and_scam_are_held_immediately(): void
    {
        $profanity = $this->policy->inspect('Sen haromi ekansan');
        $scam = $this->policy->inspect('100% daromad kafolat, pulni ikki baravar qilamiz');

        $this->assertTrue($profanity['hard_risk']);
        $this->assertContains('profanity', $profanity['signals']);
        $this->assertTrue($scam['hard_risk']);
        $this->assertContains('scam_pattern', $scam['signals']);
    }

    public function test_new_content_is_held_until_ai_decision(): void
    {
        config(['book_club_moderation.hold_pending' => true]);

        $state = $this->policy->initialState('Oddiy kitob tavsiyasi');

        $this->assertTrue($state['is_hidden_by_ai']);
        $this->assertSame('pending', $state['ai_moderation_status']);
    }
}
