@php
    $statePath = $getStatePath();
@endphp

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div 
        x-data="{
            cuotas: [],
            loading: false,
            codCliente: null,
            selectedCuotas: $wire.entangle('{{ $statePath }}'),
            
            async loadCuotas() {
                if (!this.codCliente) {
                    this.cuotas = [];
                    return;
                }
                
                this.loading = true;
                try {
                    const response = await fetch('/api/facturas-pendientes/' + this.codCliente);
                    this.cuotas = await response.json();
                } catch (error) {
                    console.error('Error loading cuotas:', error);
                    this.cuotas = [];
                } finally {
                    this.loading = false;
                }
            },
            
            toggleCuota(cuota) {
                if (!this.selectedCuotas) {
                    this.selectedCuotas = [];
                }
                
                const index = this.selectedCuotas.findIndex(f => f.cod_factura == cuota.cod_factura && f.numero_cuota == cuota.numero_cuota);
                
                if (index === -1) {
                    const cuotasPrevias = this.cuotas.filter(c =>
                        c.cod_factura == cuota.cod_factura &&
                        c.numero_cuota < cuota.numero_cuota &&
                        c.saldo_pendiente > 0
                    );
                    if (cuotasPrevias.length > 0) {
                        const algunaSinSeleccionar = cuotasPrevias.some(c =>
                            !this.selectedCuotas.some(s => s.cod_factura == c.cod_factura && s.numero_cuota == c.numero_cuota)
                        );
                        if (algunaSinSeleccionar) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Cuotas anteriores pendientes',
                                text: 'Debe seleccionar primero la cuota ' + cuotasPrevias[0].numero_cuota + ' de la Factura ' + cuota.numero_factura + ' antes de cobrar esta cuota.',
                                confirmButtonText: 'Entendido'
                            });
                            return;
                        }
                    }
                }
                
                let nueva;
                if (index > -1) {
                    nueva = this.selectedCuotas.filter((_, i) => i !== index);
                } else {
                    nueva = [...this.selectedCuotas, {
                        cod_factura: cuota.cod_factura,
                        numero_cuota: cuota.numero_cuota,
                        monto_cuota: cuota.saldo_pendiente
                    }];
                }
                this.selectedCuotas = nueva;
                $wire.$refresh();
            },
            
            isSelected(codFactura, numeroCuota) {
                return this.selectedCuotas && this.selectedCuotas.some(f => f.cod_factura == codFactura && f.numero_cuota == numeroCuota);
            },
            
            getMonto(codFactura, numeroCuota) {
                if (!this.selectedCuotas) return 0;
                const item = this.selectedCuotas.find(f => f.cod_factura == codFactura && f.numero_cuota == numeroCuota);
                return item ? item.monto_cuota : 0;
            },
            
            getTotal() {
                if (!this.selectedCuotas) return 0;
                return this.selectedCuotas.reduce((sum, f) => sum + parseFloat(f.monto_cuota || 0), 0);
            },
            
            formatMoney(amount) {
                return new Intl.NumberFormat('es-PY').format(amount);
            }
        }"
        x-init="
            $watch('$wire.data.cod_cliente', (value) => {
                if (value !== codCliente) {
                    codCliente = value;
                    selectedCuotas = selectedCuotas || [];
                    loadCuotas();
                }
            });
            codCliente = $wire.data.cod_cliente;
            loadCuotas();
        "
        style="display: flex; flex-direction: column; gap: 1rem;"
    >
        <!-- Loading State -->
        <div x-show="loading" style="display: flex; align-items: center; justify-content: center; padding: 2rem 0;">
            <svg style="animation: spin 1s linear infinite; height: 2rem; width: 2rem; color: #2563eb;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && cuotas.length === 0 && codCliente" style="text-align: center; padding: 2rem 0; color: #6b7280;">
            <svg style="margin: 0 auto; height: 3rem; width: 3rem; color: #9ca3af;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p style="margin-top: 0.5rem;">No hay cuotas pendientes para este cliente</p>
        </div>

        <!-- No Client Selected -->
        <div x-show="!loading && !codCliente" style="text-align: center; padding: 2rem 0; color: #6b7280;">
            <svg style="margin: 0 auto; height: 3rem; width: 3rem; color: #9ca3af;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <p style="margin-top: 0.5rem;">Seleccione un cliente para ver sus facturas pendientes</p>
        </div>

        <!-- Cuotas List -->
        <div x-show="!loading && cuotas.length > 0" style="max-height: 400px; overflow-y: auto; padding-right: 0.5rem;">
            <template x-for="cuota in cuotas" :key="cuota.cod_factura + '-' + cuota.numero_cuota">
                <div 
                    @click="toggleCuota(cuota)"
                    :style="isSelected(cuota.cod_factura, cuota.numero_cuota) ? 'border-color: #059669; background-color: #d1fae5;' : 'border-color: #d1d5db; background-color: #ffffff;'"
                    style="border-width: 2px; border-style: solid; border-radius: 0.5rem; padding: 1rem; cursor: pointer; transition: all 0.15s ease; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); margin-bottom: 0.75rem;">
                    
                    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                        <!-- Selected Badge -->
                        <div style="flex-shrink: 0; padding-top: 0.25rem;">
                            <template x-if="isSelected(cuota.cod_factura, cuota.numero_cuota)">
                                <svg viewBox="0 0 24 24" style="width: 1.5rem; height: 1.5rem; fill: #059669;">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                            </template>
                            <template x-if="!isSelected(cuota.cod_factura, cuota.numero_cuota)">
                                <svg viewBox="0 0 24 24" style="width: 1.5rem; height: 1.5rem; fill: #d1d5db;">
                                    <circle cx="12" cy="12" r="10"/>
                                </svg>
                            </template>
                        </div>

                        <!-- Cuota Info -->
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <div>
                                    <h4 style="font-weight: 600; color: #111827;">
                                        <span x-text="cuota.numero_factura"></span>
                                        <span style="font-size: 0.875rem; color: #6b7280; font-weight: 400;"> — Cuota <span x-text="cuota.numero_cuota"></span></span>
                                    </h4>
                                    <p style="font-size: 0.875rem; color: #6b7280;">
                                        Vence: <span x-text="cuota.fecha_vencimiento"></span>
                                    </p>
                                </div>
                                <div style="text-align: right;">
                                    <p style="font-size: 0.75rem; color: #6b7280;">Saldo pendiente</p>
                                    <p style="font-weight: 700; font-size: 1.125rem; color: #111827;" x-text="'Gs. ' + formatMoney(cuota.saldo_pendiente)"></p>
                                </div>
                            </div>

                            <!-- Monto a Pagar -->
                            <div x-show="isSelected(cuota.cod_factura, cuota.numero_cuota)" 
                                 style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 2px solid #059669;">
                                <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #065f46; margin-bottom: 0.5rem;">
                                    Monto a Pagar
                                </label>
                                <div style="position: relative;">
                                    <span style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #374151; font-weight: 600; font-size: 0.875rem;">Gs.</span>
                                    <input 
                                        type="number"
                                        :value="getMonto(cuota.cod_factura, cuota.numero_cuota)"
                                        @click.stop
                                        readonly
                                        style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 2.75rem; background-color: #f3f4f6; border: 2px solid #059669; border-radius: 0.5rem; color: #111827; font-weight: 700; font-size: 1rem; outline: none; cursor: not-allowed;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Total Selected -->
        <div x-show="selectedCuotas && selectedCuotas.length > 0" 
             style="margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #059669;">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; background-color: #d1fae5; border-radius: 0.5rem; border: 2px solid #059669;">
                <span style="font-size: 0.875rem; font-weight: 600; color: #065f46;">
                    <span x-text="selectedCuotas.length"></span> cuota(s) seleccionada(s)
                </span>
                <span style="font-size: 1.25rem; font-weight: 800; color: #065f46;" x-text="'Total: Gs. ' + formatMoney(getTotal())"></span>
            </div>
        </div>

    </div>
</x-dynamic-component>
