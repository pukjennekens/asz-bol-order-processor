<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Handmatig labels aanmaken') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-semibold leading-tight mb-2">Handmatig labels aanmaken</h2>

                    <p class="mb-4">
                        Voer hieronder de adressen in die je wilt gebruiken voor het aanmaken van labels
                    </p>
                    
                    <div x-data="repeaterForm()">
                        <h2 class="text-xl font-bold mb-4">Adressen</h2>
                        
                        <template x-for="(field, index) in fields" :key="index">
                            <div class="mb-4 border p-4 rounded">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold mb-2">Adres <span x-text="index + 1"></span></h3>
                                    <button x-show="fields.length > 1" x-on:click="removeField(index)" class="inline-flex items-center px-4 py-2 bg-red-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-600 focus:bg-red-700 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">Verwijderen</button>
                                </div>

                                <div class="mb-2 flex items-center gap-2">
                                    <input type="checkbox" x-model="field.isParcel" x-on:change="validateField(index, 'isParcel')">
                                    <label class="font-bold">Pakket</label>
                                </div>

                                <div class="mb-2">
                                    <label class="font-bold mb-2">Naam</label>
                                    <input type="text" x-model="field.name" x-on:change="validateField(index, 'name')" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                    <span x-show="errors[index]?.name" class="text-red-500 text-sm" x-text="errors[index]?.name"></span>
                                </div>

                                <div class="mb-2">
                                    <label class="font-bold mb-2">Adres</label>
                                    <input type="text" x-model="field.street" x-on:change="validateField(index, 'street')" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                    <span x-show="errors[index]?.street" class="text-red-500 text-sm" x-text="errors[index]?.street"></span>
                                </div>

                                <div class="mb-2">
                                    <label class="font-bold mb-2">Postcode</label>
                                    <input type="text" x-model="field.zipcode" x-on:change="validateField(index, 'zipcode')" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                    <span x-show="errors[index]?.zipcode" class="text-red-500 text-sm" x-text="errors[index]?.zipcode"></span>
                                </div>

                                <div class="mb-2">
                                    <label class="font-bold mb-2">Woonplaats</label>
                                    <input type="text" x-model="field.city" x-on:change="validateField(index, 'city')" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                    <span x-show="errors[index]?.city" class="text-red-500 text-sm" x-text="errors[index]?.city"></span>
                                </div>

                                <div class="mb-2">
                                    <label class="font-bold mb-2">Land</label>
                                    <select class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50" x-model="field.country" x-on:change="validateField(index, 'country')">
                                        <option value="">Selecteer een land</option>
                                        <option value="NL">Nederland</option>
                                        <option value="BE">België</option>
                                    </select>
                                    <span x-show="errors[index]?.country" class="text-red-500 text-sm" x-text="errors[index]?.country"></span>
                                </div>

                                <div class="mb-2">
                                    <label class="font-bold mb-2">Telefoonnummer</label>
                                    <input type="text" x-model="field.phone_number" x-on:change="validateField(index, 'phone_number')" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                    <span x-show="errors[index]?.phone_number" class="text-red-500 text-sm" x-text="errors[index]?.phone_number"></span>
                                </div>

                                <div class="mb-2">
                                    <label class="font-bold mb-2">Email</label>
                                    <input type="email" x-model="field.email" x-on:change="validateField(index, 'email')" class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                    <span x-show="errors[index]?.email" class="text-red-500 text-sm" x-text="errors[index]?.email"></span>
                                </div>
                            </div>
                        </template>

                        <div class="flex items-center justify-between gap-4">
                            <button x-on:click="addField()" class="inline-flex items-center px-4 py-2 bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-600 focus:bg-blue-700 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">Adres toevoegen</button>

                            <div>

                                <button x-on:click="submit()" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" x-text="loading ? 'Bezig met versturen...' : 'Versturen'"></button>
                            </div>
                        </div>
                    </div>

                    <script>
                        function repeaterForm() {
                            return {
                                loading: false,
                                fields: [
                                    { name: '', street: '', zipcode: '', city: '', country: '', phone_number: '', email: '', isParcel: false }
                                ],
                                errors: [
                                    { name: '', street: '', zipcode: '', city: '', country: '', phone_number: '', email: '', isParcel: '' }
                                ],
                                addField() {
                                    this.fields.push({ name: '', street: '', zipcode: '', city: '', country: '', phone_number: '', email: '', isParcel: false });
                                    this.errors.push({ name: '', street: '', zipcode: '', city: '', country: '', phone_number: '', email: '', isParcel: '' });
                                },
                                removeField(index) {
                                    this.fields.splice(index, 1);
                                    this.errors.splice(index, 1);
                                },
                                validateField(index, field) {
                                    const value = this.fields[index][field];
                                    let error = '';

                                    if (field === 'isParcel') return;

                                    if (value.trim() === '') {
                                        const fieldName = field.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                                        error = `${fieldName} is verplicht.`;
                                    }

                                    this.errors[index][field] = error;
                                },
                                submit() {
                                    // Validate all fields
                                    this.fields.forEach((field, index) => {
                                        Object.keys(field).forEach(key => {
                                            this.validateField(index, key);
                                        });
                                    });

                                    // Check if there are any errors
                                    const hasErrors = this.errors.some(error => Object.values(error).some(value => value !== ''));
                                    if (hasErrors) return;

                                    // Submit the form
                                    console.log(this.fields);

                                    this.loading = true;

                                    const apiUrl = '{{ route('dashboard.manual-labels.post') }}';

                                    fetch(apiUrl, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        },
                                        body: JSON.stringify(this.fields)
                                    })
                                    // The response is a application/pdf file
                                    .then(response => response.blob())
                                    .then(blob => {
                                        this.loading = false;

                                        // Open a new window with the PDF, don't save it
                                        const url = window.URL.createObjectURL(blob);
                                        window.open(url, '_blank');

                                        // Re-enable the button
                                        this.loading = false;
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        this.loading = false;
                                    });
                                },
                            }
                        }
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
