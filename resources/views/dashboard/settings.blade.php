<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Instellingen') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4">
                        <h2 class="text-2xl font-semibold leading-tight mb-2">Instellingen</h2>
                        <p>
                            Configureer hier de Bol.com accounts die wel of niet moeten worden toegevoegd aan dit portaal.
                        </p>
                    </div>

                    @if(\App\Models\BolAccount::all()->count() > 0)
                        <div class="relative overflow-x-auto">
                            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="text-left px-6 py-3">Naam</th>
                                        <th scope="col" class="text-left px-6 py-3">Client ID</th>
                                        <th scope="col" class="text-left px-6 py-3">Client Secret</th>
                                        <th></th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach(\App\Models\BolAccount::all() as $bolAccount)
                                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                            <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ $bolAccount->name }}</td>
                                            <td class="px-6 py-4">{{ substr($bolAccount->client_id, 0, 8) }}<strong>****</strong></td>
                                            <td class="px-6 py-4">{{ substr($bolAccount->client_secret, 0, 8) }}<strong>****</strong></td>
                                            <td>
                                                <a href="{{ route('dashboard.settings.delete', $bolAccount->id) }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" height="16" width="14" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.--><path opacity="1" fill="#ef4444" d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p>
                            Er zijn nog geen Bol.com accounts toegevoegd aan dit portaal. Voeg een nieuw account toe via de onderstaande knop.
                        </p>
                    @endif

                    <div class="mt-4">
                        <x-primary-button-link :href="route('dashboard.settings.add')">Voeg een nieuw account toe</x-primary-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
