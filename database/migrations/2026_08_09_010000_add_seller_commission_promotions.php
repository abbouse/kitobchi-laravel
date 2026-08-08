<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seller_commission_promotions')) {
            Schema::create('seller_commission_promotions', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('seller_id')->index();
                $table->string('type', 30);
                $table->unsignedTinyInteger('value')->default(0);
                $table->timestamp('starts_at');
                $table->timestamp('ends_at');
                $table->string('reason', 255);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->index(['seller_id', 'starts_at', 'ends_at'], 'seller_commission_promo_period_idx');
                $table->index(['seller_id', 'revoked_at'], 'seller_commission_promo_active_idx');
            });
        }

        if (Schema::hasTable('seller_transactions')) {
            Schema::table('seller_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('seller_transactions', 'baseCommissionPercent')) {
                    $table->unsignedTinyInteger('baseCommissionPercent')->nullable()->after('commissionPercent');
                }
                if (! Schema::hasColumn('seller_transactions', 'baseCommissionPrice')) {
                    $table->unsignedInteger('baseCommissionPrice')->default(0)->after('commissionPrice');
                }
                if (! Schema::hasColumn('seller_transactions', 'commissionBenefitAmount')) {
                    $table->unsignedInteger('commissionBenefitAmount')->default(0)->after('baseCommissionPrice');
                }
                if (! Schema::hasColumn('seller_transactions', 'commissionPromotionId')) {
                    $table->unsignedBigInteger('commissionPromotionId')->nullable()->after('commissionBenefitAmount')->index();
                }
                if (! Schema::hasColumn('seller_transactions', 'commissionSource')) {
                    $table->string('commissionSource', 30)->nullable()->after('commissionPromotionId');
                }
                if (! Schema::hasColumn('seller_transactions', 'commissionRuleDate')) {
                    $table->timestamp('commissionRuleDate')->nullable()->after('commissionSource');
                }
            });

            // Historical transactions already contain the final commission.
            // Treat it as both base and effective so old reports do not become
            // zero after the new snapshot columns are introduced.
            DB::table('seller_transactions')
                ->whereNull('baseCommissionPercent')
                ->update([
                    'baseCommissionPercent' => DB::raw('COALESCE(commissionPercent, 0)'),
                    'baseCommissionPrice' => DB::raw('COALESCE(commissionPrice, 0)'),
                    'commissionBenefitAmount' => 0,
                    'commissionSource' => 'legacy_snapshot',
                    'commissionRuleDate' => DB::raw('created_at'),
                ]);
        }

        // Old flow treated both NULL and 0 as "use global commission".
        // Normalizing it removes the ambiguity and makes true 0% possible only
        // through an explicit, auditable temporary promotion.
        if (Schema::hasTable('sellers') && Schema::hasColumn('sellers', 'commission_percent')) {
            DB::table('sellers')->where('commission_percent', 0)->update(['commission_percent' => null]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('seller_transactions')) {
            Schema::table('seller_transactions', function (Blueprint $table) {
                $columns = [
                    'baseCommissionPercent',
                    'baseCommissionPrice',
                    'commissionBenefitAmount',
                    'commissionPromotionId',
                    'commissionSource',
                    'commissionRuleDate',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('seller_transactions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('seller_commission_promotions');
    }
};
