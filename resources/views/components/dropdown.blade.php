@props([
    'name' => null,
    'id' => null,
    'selected' => null,
    'options' => [],
    'placeholder' => 'Select Option',
    'submitOnChange' => false,
    'align' => 'left',
    'class' => '',
    'buttonClass' => '',
    'menuClass' => '',
    'width' => 'w-full',
])

@php
    $formattedOptions = [];
    $isList = array_is_list($options);
    foreach ($options as $key => $val) {
        if ($isList && !is_array($val)) {
            $formattedOptions[(string)$val] = (string)$val;
        } else {
            $formattedOptions[(string)$key] = (string)$val;
        }
    }
    
    $initialKey = (string) ($selected ?? (array_keys($formattedOptions)[0] ?? ''));
@endphp

<div x-data="{
        open: false,
        selected: '{{ addslashes($initialKey) }}',
        options: {{ json_encode($formattedOptions) }},
        selectOption(key) {
            this.selected = String(key);
            this.open = false;
            if (this.$refs.hiddenInput) {
                this.$refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            @if($submitOnChange)
                this.$nextTick(() => {
                    const form = this.$el.closest('form');
                    if (form) form.submit();
                });
            @endif
            this.$dispatch('change', this.selected);
        }
     }"
     @click.outside="open = false"
     :class="open ? 'z-50 relative' : 'relative z-10'"
     class="inline-block {{ $class }}">

    @if($name)
        <input type="hidden" 
               name="{{ $name }}" 
               @if($id) id="{{ $id }}" @endif 
               x-ref="hiddenInput" 
               :value="selected">
    @endif

    <button type="button" 
            @click="open = !open"
            class="flex items-center justify-between gap-2.5 rounded-xl border border-white/10 bg-white/5 px-3 py-1.5 text-[11px] text-zinc-200 hover:text-white transition font-mono focus:outline-none cursor-pointer {{ $buttonClass }}">
        <span x-text="options[selected] || options[String(selected)] || '{{ addslashes($placeholder) }}'"></span>
        <svg class="h-3 w-3 text-zinc-400 transition-transform duration-300 flex-shrink-0" 
             :class="{'rotate-180': open}" 
             fill="none" 
             viewBox="0 0 24 24" 
             stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-show="open"
         x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-300"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition cubic-bezier(0.16, 1, 0.3, 1) duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
         class="absolute {{ $align === 'right' ? 'right-0' : 'left-0' }} mt-1.5 min-w-[160px] max-h-60 overflow-y-auto rounded-xl border border-white/10 bg-zinc-900/95 backdrop-blur-2xl p-1 shadow-2xl z-[9999] flex flex-col gap-1 {{ $menuClass }}"
         style="display: none;">
         
        <template x-for="(label, key) in options" :key="key">
            <button type="button" 
                    @click="selectOption(key)"
                    class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-mono transition cursor-pointer"
                    :class="selected == key ? 'bg-white/10 text-white font-semibold' : 'text-zinc-400 hover:bg-white/5 hover:text-white'">
                <span x-text="label"></span>
            </button>
        </template>
    </div>
</div>
