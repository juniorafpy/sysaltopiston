@php
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div 
        x-data="{
            facturas: [],
            loading: false,
            codCliente: null,
            selectedFacturas: $wire.entangle('{{ $statePath }}'),
            
            async loadFacturas() {
                if (!this.codCliente) {
                    this.facturas = [];
                    return;
                }
                
                this.loading = true;
                try {
                    const response = await fetch('/api/facturas-pendientes/' + this.codCliente);
                    this.facturas = await response.json();
                } catch (error) {
                    console.error('Error loading facturas:', error);
                    this.facturas = [];
                } finally {
                    this.loading = false;
                }
            },
            
            toggleFactura(factura) {
                if (!this.selectedFacturas) {
                    this.selectedFacturas = [];
                }
                
                const index = this.selectedFacturas.findIndex(f => f.cod_factura == factura.cod_factura);
                
                if (index > -1) {
                    this.selectedFacturas.splice(index, 1);
                } else {
                    this.selectedFacturas.push({
                        cod_factura: factura.cod_factura,
                        monto_cuota: factura.saldo
                    });
                }
            },
            
            updateMonto(facturaId, monto) {
                const index = this.selectedFacturas.findIndex(f => f.cod_factura == facturaId);
                if (index > -1) {
                    this.selectedFacturas[index].monto_cuota = parseFloat(monto) || 0;
                }
            },
            
            isSelected(facturaId) {
                return this.selectedFacturas && this.selectedFacturas.some(f => f.cod_factura == facturaId);
            },
            
            getMonto(facturaId) {
                if (!this.selectedFacturas) return 0;
                const factura = this.selectedFacturas.find(f => f.cod_factura == facturaId);
                return factura ? factura.monto_cuota : 0;
            },
            
            getTotal() {
                if (!this.selectedFacturas) return 0;
                return this.selectedFacturas.reduce((sum, f) => sum + parseFloat(f.monto_cuota || 0), 0);
            },
            
            formatMoney(amount) {
                return new Intl.NumberFormat('es-PY').format(amount);
            }
        }"
        x-init="
            $watch('$wire.data.cod_cliente', (value) => {
                codCliente = value;
                loadFacturas();
            });
            codCliente = $wire.data.cod_cliente;
            loadFacturas();
        "
        class="space-y-4"
    >
        <!-- Loading State -->
        <div x-show="loading" class="flex items-center justify-center py-8">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && facturas.length === 0 && codCliente" class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-2">No hay facturas pendientes para este cliente</p>
        </div>

        <!-- No Client Selected -->
        <div x-show="!loading && !codCliente" class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <p class="mt-2">Seleccione un cliente para ver sus facturas pendientes</p>
        </div>

        <!-- Facturas List -->
        <div x-show="!loading && facturas.length > 0" class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
            <template x-for="factura in facturas" :key="factura.cod_factura">
                <div 
                    @click="toggleFactura(factura)"
                    :class="isSelected(factura.cod_factura) ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'"
                    class="border-2 rounded-lg p-4 cursor-pointer transition-all duration-200">
                    
                    <div class="flex items-start gap-3">
                        <!-- Checkbox -->
                        <div class="flex-shrink-0 pt-1">
                            <input 
                                type="checkbox" 
                                :checked="isSelected(factura.cod_factura)"
                                @click.stop="toggleFactura(factura)"
                                class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        </div>

                        <!-- Factura Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div>
                                    <h4 class="font-semibold text-gray-900" x-text="factura.numero_factura"></h4>
                                    <p class="text-sm text-gray-500" x-text="factura.fecha_emision"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">Saldo</p>
                                    <p class="font-bold text-lg text-gray-900" x-text="'Gs. ' + formatMoney(factura.saldo)"></p>
                                </div>
                            </div>

                            <!-- Monto a Pagar (solo si está seleccionada) -->
                            <div x-show="isSelected(factura.cod_factura)" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                                 x-transition:enter-end="opacity-100 transform translate-y-0"
                                 class="mt-3 pt-3 border-t border-gray-200">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Monto a Pagar
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">Gs.</span>
                                    <input 
                                        type="number"
                                        :value="getMonto(factura.cod_factura)"
                                        @input="updateMonto(factura.cod_factura, $event.target.value)"
                                        @click.stop
                                        :max="factura.saldo"
                                        min="0"
                                        class="w-full pl-12 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Total Selected -->
        <div x-show="selectedFacturas && selectedFacturas.length > 0" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="mt-4 pt-4 border-t-2 border-gray-200">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">
                    <span x-text="selectedFacturas.length"></span> factura(s) seleccionada(s)
                </span>
                <span class="text-lg font-bold text-primary-600" x-text="'Total: Gs. ' + formatMoney(getTotal())"></span>
            </div>
        </div>

    </div>
</x-dynamic-component>
