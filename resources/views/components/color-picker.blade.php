@props(['selected' => 'blue'])

<div class="flex flex-wrap gap-3">
    @foreach(\codearachnid\AwsSesObserver\Enums\SourceColor::cases() as $color)
        <label class="relative cursor-pointer">
            <input
                type="radio"
                name="color"
                value="{{ $color->value }}"
                wire:model="color"
                class="sr-only peer"
                @checked($selected === $color->value)
            />
            <span class="block w-8 h-8 rounded-full {{ $color->cssClass() }} ring-offset-2 dark:ring-offset-zinc-900 peer-checked:ring-2 peer-checked:ring-zinc-900 dark:peer-checked:ring-white peer-focus:ring-2 peer-focus:ring-zinc-500 transition"></span>
        </label>
    @endforeach
</div>
