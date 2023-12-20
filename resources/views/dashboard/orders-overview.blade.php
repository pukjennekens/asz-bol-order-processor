<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $bolAccount->name }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ selectedOrderIds: [], orderIds: {{ json_encode($orderIds) }} }">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="post" action="{{ route('dashboard.process-orders') }}">
                        @csrf

                        <input type="hidden" name="bol_com_account_id" value="{{ $bolAccount->id }}">

                        <div class="flex justify-between items-center mb-8">
                            <h2 class="text-2xl font-semibold leading-tight">Order overzicht voor {{ $bolAccount->name }}</h2>

                            @if(!empty($orders))
                                <div class="inline-flex items-center gap-4">
                                    <span 
                                        class="text-sm font-bold"
                                        x-show="selectedOrderIds.length > 0"
                                        x-text="selectedOrderIds.length + ' geselecteerde order' + (selectedOrderIds.length > 1 ? 's' : '')"
                                    >
                                    </span>

                                    <div class="flex gap-2 items-center">
                                        <x-primary-button type="submit">Verwerk geselecteerde orders</x-primary-button>

                                        <x-dropdown>
                                            <x-slot name="trigger">
                                                <x-secondary-button type="button">
                                                    <svg xmlns="http://www.w3.org/2000/svg" height="16" width="16" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.--><path opacity="1" fill="#1E3050" d="M495.9 166.6c3.2 8.7 .5 18.4-6.4 24.6l-43.3 39.4c1.1 8.3 1.7 16.8 1.7 25.4s-.6 17.1-1.7 25.4l43.3 39.4c6.9 6.2 9.6 15.9 6.4 24.6c-4.4 11.9-9.7 23.3-15.8 34.3l-4.7 8.1c-6.6 11-14 21.4-22.1 31.2c-5.9 7.2-15.7 9.6-24.5 6.8l-55.7-17.7c-13.4 10.3-28.2 18.9-44 25.4l-12.5 57.1c-2 9.1-9 16.3-18.2 17.8c-13.8 2.3-28 3.5-42.5 3.5s-28.7-1.2-42.5-3.5c-9.2-1.5-16.2-8.7-18.2-17.8l-12.5-57.1c-15.8-6.5-30.6-15.1-44-25.4L83.1 425.9c-8.8 2.8-18.6 .3-24.5-6.8c-8.1-9.8-15.5-20.2-22.1-31.2l-4.7-8.1c-6.1-11-11.4-22.4-15.8-34.3c-3.2-8.7-.5-18.4 6.4-24.6l43.3-39.4C64.6 273.1 64 264.6 64 256s.6-17.1 1.7-25.4L22.4 191.2c-6.9-6.2-9.6-15.9-6.4-24.6c4.4-11.9 9.7-23.3 15.8-34.3l4.7-8.1c6.6-11 14-21.4 22.1-31.2c5.9-7.2 15.7-9.6 24.5-6.8l55.7 17.7c13.4-10.3 28.2-18.9 44-25.4l12.5-57.1c2-9.1 9-16.3 18.2-17.8C227.3 1.2 241.5 0 256 0s28.7 1.2 42.5 3.5c9.2 1.5 16.2 8.7 18.2 17.8l12.5 57.1c15.8 6.5 30.6 15.1 44 25.4l55.7-17.7c8.8-2.8 18.6-.3 24.5 6.8c8.1 9.8 15.5 20.2 22.1 31.2l4.7 8.1c6.1 11 11.4 22.4 15.8 34.3zM256 336a80 80 0 1 0 0-160 80 80 0 1 0 0 160z"/></svg>
                                                </x-secondary-button>
                                            </x-slot>

                                            <x-slot name="content">
                                                <div class="px-4 py-2">
                                                    <label for="is_parcel" class="inline-flex items-center gap-1">
                                                        <input 
                                                            type="checkbox"
                                                            name="is_parcel"
                                                            id="is_parcel"
                                                            class="form-checkbox h-3 w-3 text-gray-600" 
                                                        >
                                                        <span class="text-sm text-gray-600">Verwerk als pakket</span>
                                                    </label>
                                                </div>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if(!empty($orders))
                            <div class="relative overflow-x-auto">
                                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="text-left px-6 py-3">
                                                <input 
                                                    type="checkbox"
                                                    name="selectAll"
                                                    id="selectAll"
                                                    class="form-checkbox h-3 w-3 text-gray-600" 
                                                    @click="selectedOrderIds == orderIds ? selectedOrderIds = [] : selectedOrderIds = orderIds"
                                                    :checked="selectedOrderIds.length == orderIds.length"
                                                >
                                            </th>
                                            <th scope="col" class="text-left px-6 py-3">Order ID</th>
                                            <th scope="col" class="text-left px-6 py-3">Naam</th>
                                            <th scope="col" class="text-left px-6 py-3">Verzendadres</th>
                                            <th scope="col" class="text-left px-6 py-3">Item(s)</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach($orders as $order)
                                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                                <td class="px-6 py-4">
                                                    <input type="checkbox" name="order_ids[]" value="{{ $order->orderId }}" class="form-checkbox h-3 w-3 text-gray-600" x-model="selectedOrderIds">
                                                </td>
                                                <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ $order->orderId }}</td>
                                                <td class="px-6 py-4">{{ $order->billingDetails->firstName . ' ' . $order->billingDetails->surname }}</td>
                                                <td class="px-6 py-4 min-w-64">
                                                    {{ $order->shipmentDetails->firstName . ' ' . $order->shipmentDetails->surname }}<br>
                                                    {{ $order->shipmentDetails->streetName . ' ' . $order->shipmentDetails->houseNumber }}<br>
                                                    {{ $order->shipmentDetails->zipCode . ' ' . $order->shipmentDetails->city }}<br>
                                                    {{ $order->shipmentDetails->countryCode }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    <ul class="min-w-64">
                                                        @foreach($order->orderItems as $orderItem)
                                                            <li>{{ $orderItem->product->title }}</li>
                                                        @endforeach
                                                    </ul>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p>
                                Er zijn op dit moment geen openstaande orders voor dit account, we werken dit iedere 5 minuten bij.
                            </p>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
