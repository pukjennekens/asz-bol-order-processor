<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Bol.com account toevoegen') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4">
                        <h2 class="text-2xl font-semibold leading-tight mb-2">Nieuw Bol.com account toevoegen</h2>
                        <p>
                            Voeg hier een nieuw Bol.com account toe aan dit portaal.
                        </p>
                    </div>

                    <form action="{{ route('dashboard.settings.add') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="block mb-2">Naam</label>
                            <input type="text" name="name" id="name" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50" placeholder="Naam van het account" required>
                        </div>

                        <div class="mb-4">
                            <label for="client_id" class="block mb-2">Client ID</label>
                            <input type="text" name="client_id" id="client_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50" placeholder="Client ID van het account" required>
                        </div>

                        <div class="mb-4">
                            <label for="client_secret" class="block mb-2">Client Secret</label>
                            <input type="text" name="client_secret" id="client_secret" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50" placeholder="Client Secret van het account" required>
                        </div>

                        <div class="mt-4">
                            <x-primary-button type="submit">Voeg account toe</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
