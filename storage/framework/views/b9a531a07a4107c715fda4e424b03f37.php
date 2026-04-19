<?php
  use App\Helpers\TailAdminMenuIcon;
  $admin = auth('panel')->user();

  $navGroups = [
    [
      'label' => 'Asosiy',
      'items' => [
        ['label'=>'Dashboard','icon'=>'grid-1x2-fill','route'=>'panel.dashboard','match'=>'panel.dashboard'],
        $admin?->hasPermission('users') ? ['label'=>'Foydalanuvchilar','icon'=>'people-fill','route'=>'panel.users.index','match'=>'panel.users.*'] : null,
      ],
    ],
    [
      'label' => 'Mahsulotlar',
      'guard' => $admin?->hasPermission('books') || $admin?->hasPermission('stationery') || $admin?->hasPermission('settings'),
      'items' => [
        $admin?->hasPermission('books')     ? ['label'=>'Kitoblar','icon'=>'book-fill','route'=>'panel.books.index','match'=>'panel.books.*','badge'=>\App\Models\Books::where('is_approved',0)->count()] : null,
        $admin?->hasPermission('stationery')? ['label'=>'Kanstovar','icon'=>'pencil-fill','route'=>'panel.stationery.index','match'=>'panel.stationery.*','badge'=>\App\Models\Stationery::where('is_approved',0)->count()] : null,
        $admin?->hasPermission('settings')  ? ['label'=>'Kitob kategoriyalari','icon'=>'tags-fill','route'=>'panel.book-categories.index','match'=>'panel.book-categories.*'] : null,
        $admin?->hasPermission('settings')  ? ['label'=>'Kanstovar kategoriyalari','icon'=>'tag-fill','route'=>'panel.stationery-categories.index','match'=>'panel.stationery-categories.*'] : null,
        $admin?->hasPermission('settings')  ? ['label'=>'Reels','icon'=>'play-circle-fill','route'=>'panel.reels.index','match'=>'panel.reels.*'] : null,
        $admin?->hasPermission('settings')  ? ['label'=>'Market yangiliklari','icon'=>'newspaper','route'=>'panel.market-news.index','match'=>'panel.market-news.*'] : null,
      ],
    ],
    [
      'label' => 'Buyurtmalar',
      'guard' => $admin?->hasPermission('orders'),
      'items' => [
        $admin?->hasPermission('orders') ? ['label'=>'Buyurtmalar','icon'=>'bag-check-fill','route'=>'panel.orders.index','match'=>'panel.orders.*','badge'=>\App\Models\Sold::where('status','A')->count(),'badgeColor'=>'success'] : null,
        $admin?->hasPermission('orders') ? ['label'=>'Seller buyurtmalari','icon'=>'shop','route'=>'panel.seller-orders.index','match'=>'panel.seller-orders.*'] : null,
        $admin?->hasPermission('orders') ? ['label'=>'Kuryer buyurtmalari','icon'=>'truck','route'=>'panel.courier-orders.index','match'=>'panel.courier-orders.*'] : null,
      ],
    ],
    [
      'label' => 'Biznes',
      'items' => [
        $admin?->hasPermission('sellers')  ? ['label'=>'Sotuvchilar','icon'=>'shop-window','route'=>'panel.sellers.index','match'=>'panel.sellers.*','badge'=>\App\Models\Seller::where('status','pending')->count()] : null,
        $admin?->hasPermission('settings') ? (function(){ try{$c=\App\Models\SellerTransaction::where('status','pending')->count()+\App\Models\CourierTransaction::where('status','pending')->count();}catch(\Exception $e){$c=0;} return ['label'=>'Tranzaksiyalar','icon'=>'arrow-left-right','route'=>'panel.seller-transactions.index','match'=>'panel.seller-transactions.*','badge'=>$c]; })() : null,
        $admin?->hasPermission('couriers') ? ['label'=>'Kuryerlar','icon'=>'bicycle','route'=>'panel.couriers.index','match'=>'panel.couriers.*'] : null,
        $admin?->hasPermission('promocodes')? ['label'=>'Promokodlar','icon'=>'ticket-perforated-fill','route'=>'panel.promocodes.index','match'=>'panel.promocodes.*'] : null,
        $admin?->hasPermission('settings') ? ['label'=>'Reklamalar','icon'=>'megaphone-fill','route'=>'panel.seller-ads.index','match'=>'panel.seller-ads.*','badge'=>\App\Models\SellerAd::where('moderation','pending')->count()] : null,
      ],
    ],
    [
      'label' => "Sovg'alar",
      'guard' => $admin?->hasPermission('settings'),
      'items' => [
        (function(){ try{$c=\App\Models\GiftCertificate::where('status','pending_payment')->count();}catch(\Exception $e){$c=0;} return ['label'=>'Gift sertifikatlar','icon'=>'gift-fill','route'=>'panel.gift-certificates.index','match'=>'panel.gift-certificates.*','badge'=>$c]; })(),
        (function(){ try{$c=\App\Models\MysteryBoxSubscription::where('status','active')->where('next_delivery_at','<=',now())->count();}catch(\Exception $e){$c=0;} return ['label'=>'Mystery Box','icon'=>'box-seam-fill','route'=>'panel.mystery-box.subscriptions','match'=>'panel.mystery-box.*','badge'=>$c,'badgeColor'=>'danger']; })(),
      ],
    ],
    [
      'label' => 'Jamiyat',
      'guard' => $admin?->hasPermission('settings'),
      'items' => [
        (function(){ try{$c=\App\Models\BookClub::where('is_deleted',false)->where('created_at','>=',now()->subDay())->count();}catch(\Exception $e){$c=0;} return ['label'=>'Book Club','icon'=>'chat-quote-fill','route'=>'panel.book-club.index','match'=>'panel.book-club.index|panel.book-club.show|panel.book-club.edit','badge'=>$c,'badgeColor'=>'info']; })(),
        (function(){ try{$c=\App\Models\BookClubComment::where('kangaroo_ugc_status','pending_admin')->whereNull('parent_id')->count()+\App\Models\BookClub::where('is_deleted',false)->where('kangaroo_post_ugc_status','pending_admin')->count();}catch(\Exception $e){$c=0;} return ['label'=>'UGC navbati','icon'=>'stars','route'=>'panel.book-club.moderation-queue','match'=>'panel.book-club.moderation-queue','badge'=>$c,'badgeColor'=>'warning']; })(),
        ['label'=>'Chat kuzatuv','icon'=>'chat-dots-fill','route'=>'panel.chats.index','match'=>'panel.chats.*'],
        (function(){ try{$c=\App\Models\Report::where('status','pending')->count();}catch(\Exception $e){$c=0;} return ['label'=>'Shikoyatlar','icon'=>'flag-fill','route'=>'panel.reports.index','match'=>'panel.reports.*','badge'=>$c,'badgeColor'=>'danger']; })(),
      ],
    ],
    [
      'label' => 'Tizim',
      'items' => [
        $admin?->hasPermission('settings') ? (function(){ $c=\App\Models\BotTicket::where('status','queue')->count(); return ['label'=>'Support','icon'=>'headset','route'=>'panel.bot-tickets.index','match'=>'panel.bot-tickets.*','badge'=>$c,'badgeColor'=>'danger']; })() : null,
        $admin?->hasPermission('settings') ? ['label'=>'Push bildirishnomalar','icon'=>'bell-fill','route'=>'panel.fcm-notifications.index','match'=>'panel.fcm-notifications.*'] : null,
        $admin?->hasPermission('settings') ? ['label'=>'Sozlamalar','icon'=>'gear-fill','route'=>'panel.settings.index','match'=>'panel.settings.*'] : null,
        $admin?->hasPermission('settings') ? ['label'=>'Siyosatlar','icon'=>'file-earmark-text-fill','route'=>'panel.policies.index','match'=>'panel.policies.*'] : null,
        $admin?->hasPermission('settings') ? ['label'=>'Vakansiyalar','icon'=>'briefcase-fill','route'=>'panel.vacancies.index','match'=>'panel.vacancies.*'] : null,
        $admin?->hasPermission('settings') ? (function () {
            try {
                $c = \App\Models\CareerApplication::where('status', \App\Models\CareerApplication::STATUS_NEW)->count();
            } catch (\Exception $e) {
                $c = 0;
            }

            return ['label' => 'Karyera arizalari', 'icon' => 'envelope-paper-fill', 'route' => 'panel.career-applications.index', 'match' => 'panel.career-applications.*', 'badge' => $c, 'badgeColor' => 'danger'];
        })() : null,
        $admin?->hasPermission('admins')   ? ['label'=>'Adminlar','icon'=>'shield-fill-check','route'=>'panel.admins.index','match'=>'panel.admins.*'] : null,
        $admin?->hasPermission('admins')   ? ['label'=>'API Mijozlar','icon'=>'key-fill','route'=>'panel.api-clients.index','match'=>'panel.api-clients.*'] : null,
      ],
    ],
  ];
?>

<aside id="sidebar"
       class="kc-sidebar--brand fixed left-0 top-0 flex h-screen min-h-0 flex-col border-r px-4 text-slate-100 transition-all duration-300 ease-in-out sm:px-5"
       x-data="{}"
       :class="{
         'w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen,
         'w-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isMobileOpen,
         'translate-x-0': $store.sidebar.isMobileOpen,
         '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen
       }">

  
  <div class="kc-sidebar-head flex shrink-0 border-b border-white/10 pt-7 pb-6"
       :class="(!$store.sidebar.isExpanded && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'">
    <a href="<?php echo e(route('panel.dashboard')); ?>"
       class="flex min-h-10 items-center justify-center"
       :class="(!$store.sidebar.isExpanded && !$store.sidebar.isMobileOpen) ? 'w-full' : ''"
       @click="window.innerWidth < 1280 && $store.sidebar.setMobileOpen(false)">
      <img x-show="$store.sidebar.isExpanded || $store.sidebar.isMobileOpen"
           class="sidebar-logo-full h-9 w-auto max-w-[9.5rem] object-contain object-left opacity-[0.95] sm:h-10"
           src="<?php echo e(asset('images/logo/logo_white.png')); ?>"
           width="150"
           height="40"
           alt="kitobchi" />
      <span x-show="!$store.sidebar.isExpanded && !$store.sidebar.isMobileOpen"
            class="kc-sidebar-mark select-none"
            aria-hidden="true">k.</span>
    </a>
  </div>

  <nav class="kc-sidebar-nav no-scrollbar flex min-h-0 flex-1 flex-col overflow-y-auto overflow-x-hidden overscroll-contain py-4 duration-300 ease-linear">
      <div class="flex flex-col gap-4">
        <?php $__currentLoopData = $navGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php $items = array_values(array_filter($group['items'] ?? [], fn($i) => $i !== null)); ?>
          <?php if(empty($items)): ?> <?php continue; ?> <?php endif; ?>
          <?php if(isset($group['guard']) && !$group['guard']): ?> <?php continue; ?> <?php endif; ?>

          <div>
            <h2 class="kc-sidebar-group-title flex items-center"
                :class="(!$store.sidebar.isExpanded && !$store.sidebar.isMobileOpen) ? 'lg:justify-center' : 'justify-start'">
              <template x-if="$store.sidebar.isExpanded || $store.sidebar.isMobileOpen">
                <span class="kc-sidebar-group-label"><?php echo e($group['label']); ?></span>
              </template>
              <template x-if="!$store.sidebar.isExpanded && !$store.sidebar.isMobileOpen">
                <svg class="kc-sidebar-group-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z" fill="currentColor"/>
                </svg>
              </template>
            </h2>

            <ul class="kc-sidebar-group-list flex flex-col">
              <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                  $isActive  = request()->routeIs($item['match']);
                  $badge     = $item['badge'] ?? 0;
                  $badgeColor = $item['badgeColor'] ?? 'warning';
                  $badgeClass = match($badgeColor) {
                    'success' => 'ml-auto inline-flex min-h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-success-50 px-1.5 text-[11px] font-semibold text-success-700 dark:bg-success-500/15 dark:text-success-400',
                    'danger'  => 'ml-auto inline-flex min-h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-error-50 px-1.5 text-[11px] font-semibold text-error-600 dark:bg-error-500/15 dark:text-error-400',
                    'info'    => 'ml-auto inline-flex min-h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-blue-light-50 px-1.5 text-[11px] font-semibold text-blue-light-700 dark:bg-blue-light-500/15 dark:text-blue-light-400',
                    default   => 'ml-auto inline-flex min-h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-warning-50 px-1.5 text-[11px] font-semibold text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
                  };
                  $dotColor = match($badgeColor) {
                    'success' => 'bg-success-500',
                    'danger'  => 'bg-error-500',
                    'info'    => 'bg-blue-light-500',
                    default   => 'bg-warning-500',
                  };
                ?>
                <li class="<?php echo \Illuminate\Support\Arr::toCssClasses(['relative' => $badge > 0]); ?>">
                  <a href="<?php echo e(route($item['route'])); ?>"
                     class="menu-item group <?php echo e($isActive ? 'menu-item-active' : 'menu-item-inactive'); ?>"
                     :class="(!$store.sidebar.isExpanded && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'"
                     @click="window.innerWidth < 1280 && $store.sidebar.setMobileOpen(false)">

                    <span class="<?php echo e($isActive ? 'menu-item-icon-active' : 'menu-item-icon-inactive'); ?> [&_svg]:shrink-0">
                      <?php echo TailAdminMenuIcon::forBi($item['icon']); ?>

                    </span>

                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isMobileOpen"
                          class="menu-item-text flex items-center gap-2">
                      <?php echo e($item['label']); ?>

                    </span>

                    <?php if($badge > 0): ?>
                      <span x-show="$store.sidebar.isExpanded || $store.sidebar.isMobileOpen"
                            class="<?php echo e($badgeClass); ?>"><?php echo e($badge > 99 ? '99+' : $badge); ?></span>
                      <span x-show="!$store.sidebar.isExpanded && !$store.sidebar.isMobileOpen"
                            class="absolute right-0.5 top-1/2 size-2 -translate-y-1/2 rounded-full <?php echo e($dotColor); ?> ring-2 ring-[#152238] xl:right-1"></span>
                    <?php endif; ?>
                  </a>
                </li>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
  </nav>
</aside>
<?php /**PATH /Users/abbos/PROJECTS/MY/kitobchi-server/kitobchi/resources/views/panel/partials/sidebar.blade.php ENDPATH**/ ?>