<?php

namespace Tests\Unit;

use App\Models\SplitPlan;
use App\Models\User;
use App\Models\UserCard;
use App\Services\SplitContractService;
use App\Services\SplitProfileService;
use App\Services\SplitScheduleService;
use App\Services\UserReputationService;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

class SplitManualLimitSafetyTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_manual_limit_still_requires_verified_phone_and_card(): void
    {
        $service = new SplitProfileService(Mockery::mock(UserReputationService::class));
        $method = new ReflectionMethod($service, 'hardBlockReasons');

        $unverified = Mockery::mock(User::class)->makePartial();
        $unverified->id = 41;
        $unverified->shouldReceive('isBlocked')->once()->andReturnFalse();
        $unverified->shouldReceive('hasVerifiedPhone')->once()->andReturnFalse();

        $reasons = $method->invoke($service, $unverified, 0, false, '', []);

        $this->assertContains('Foydalanuvchi akkaunti tasdiqlanmagan.', $reasons);
        $this->assertContains('Kamida bitta tasdiqlangan Paylov karta kerak.', $reasons);

        $verified = Mockery::mock(User::class)->makePartial();
        $verified->id = 42;
        $verified->shouldReceive('isBlocked')->once()->andReturnFalse();
        $verified->shouldReceive('hasVerifiedPhone')->once()->andReturnTrue();

        $this->assertSame([], $method->invoke($service, $verified, 1, false, '', []));
    }

    public function test_manual_limit_bypasses_plan_score_but_not_available_limit(): void
    {
        $user = new User;
        $user->id = 51;
        $plan = new SplitPlan(['min_confidence_score' => 95]);

        $profileService = Mockery::mock(SplitProfileService::class);
        $profileService->shouldReceive('getFreshProfile')
            ->twice()
            ->with($user, true)
            ->andReturn([
                'eligible' => true,
                'eligibility_reasons' => [],
                'manual_limit_active' => true,
                'manual_limit' => 500000,
                'computed_limit' => 500000,
                'available_limit' => 300000,
                'active_exposure' => 200000,
                'confidence_score' => 1,
            ]);

        $service = new SplitContractService(
            Mockery::mock(SplitScheduleService::class),
            $profileService,
        );
        $method = new ReflectionMethod($service, 'assertUserEligible');

        $profile = $method->invoke($service, $user, $plan, 300000);
        $this->assertTrue($profile['manual_limit_active']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Buyurtma summasi bo'sh limitdan katta.");
        $method->invoke($service, $user, $plan, 301000);
    }

    public function test_scored_limit_still_obeys_plan_confidence_threshold(): void
    {
        $user = new User;
        $user->id = 61;
        $plan = new SplitPlan(['min_confidence_score' => 80]);

        $profileService = Mockery::mock(SplitProfileService::class);
        $profileService->shouldReceive('getFreshProfile')
            ->once()
            ->with($user, true)
            ->andReturn([
                'eligible' => true,
                'eligibility_reasons' => [],
                'manual_limit_active' => false,
                'computed_limit' => 500000,
                'available_limit' => 500000,
                'confidence_score' => 70,
            ]);

        $service = new SplitContractService(
            Mockery::mock(SplitScheduleService::class),
            $profileService,
        );
        $method = new ReflectionMethod($service, 'assertUserEligible');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bu tarif uchun ishonch balli yetarli emas.');
        $method->invoke($service, $user, $plan, 300000);
    }

    public function test_temporary_card_cannot_be_used_for_split(): void
    {
        $user = new User;
        $user->id = 71;
        $card = new UserCard([
            'user_id' => 71,
            'provider_card_id' => 'card-71',
            'is_verified' => true,
            'is_temporary' => true,
        ]);

        $service = new SplitContractService(
            Mockery::mock(SplitScheduleService::class),
            Mockery::mock(SplitProfileService::class),
        );
        $method = new ReflectionMethod($service, 'assertCardUsable');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Split uchun yaroqli tasdiqlangan karta topilmadi.');
        $method->invoke($service, $user, $card);
    }

    public function test_profile_and_contract_mutations_share_same_user_lock_key(): void
    {
        $this->assertSame('split:user:77:mutation', SplitProfileService::mutationLockKey(77));
        $this->assertNotSame(
            SplitProfileService::mutationLockKey(77),
            SplitProfileService::mutationLockKey(78),
        );
    }
}
