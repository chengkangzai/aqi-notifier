<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('WhatsApp Session')" :subheading="__('Manage WhatsApp connection and test messaging')">
        <livewire:whats-app-session-manager />
    </x-settings.layout>
</section>