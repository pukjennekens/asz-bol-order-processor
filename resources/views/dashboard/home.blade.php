<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-semibold leading-tight mb-2">Welkom op het dashboard</h2>
                    <p>
                        Navigeer via bovenstaande navigatiebalk naar de verschillende Bol.com accounts of voeg nieuwe accounts toe via de instellingen.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
