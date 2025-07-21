<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('AQI Settings')" :subheading="__('Configure notification thresholds and recipients')">
        <livewire:aqi-settings-panel />
    </x-settings.layout>
</section>
