<div x-data="{ open : false, src : '', auto: true, wide: false, styles: ''}">
    <template
        @img-popup.window="open = true; src = $event.detail.src; auto = $event.detail.auto; wide = $event.detail.wide; styles = $event.detail.styles"
        x-if="open"
    >
        <div class="modal-template">
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-10"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-10"
                class="modal"
                aria-modal="true"
                role="dialog"
                @click.self="open=false"
            >
                <div class="modal-dialog"
                    :class="{'modal-dialog-auto': auto, 'modal-dialog-xl': wide}"
                >
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button"
                                    class="btn btn-close"
                                    @click.stop="open=false"
                                    aria-label="Close"
                            >
                                <?php if (isset($component)) { $__componentOriginale5a015c7e462e0b96985c262dddd7f9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d = $attributes; } ?>
<?php $component = MoonShine\UI\Components\Icon::resolve(['icon' => 'x-mark','size' => '6'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('moonshine::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\MoonShine\UI\Components\Icon::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale5a015c7e462e0b96985c262dddd7f9d)): ?>
<?php $attributes = $__attributesOriginale5a015c7e462e0b96985c262dddd7f9d; ?>
<?php unset($__attributesOriginale5a015c7e462e0b96985c262dddd7f9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale5a015c7e462e0b96985c262dddd7f9d)): ?>
<?php $component = $__componentOriginale5a015c7e462e0b96985c262dddd7f9d; ?>
<?php unset($__componentOriginale5a015c7e462e0b96985c262dddd7f9d); ?>
<?php endif; ?>
                            </button>
                        </div>
                        <div class="modal-body">
                            <img @click.outside="open = false"
                                 src=""
                                 :src="src"
                                 :style="styles"
                                 alt=""
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div x-show="open" x-transition.opacity class="modal-backdrop"></div>
        </div>
    </template>
</div>
<?php /**PATH /var/www/www-root/data/www/kitobchi.com/vendor/moonshine/moonshine/src/Laravel/src/Providers/../../../UI/resources/views/shared/img-popup.blade.php ENDPATH**/ ?>