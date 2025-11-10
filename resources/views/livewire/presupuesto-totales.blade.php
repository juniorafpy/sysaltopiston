<div class="p-4 bg-gray-100 rounded-md shadow-md mt-4">
    <h3 class="text-lg font-semibold">Resumen de Totales</h3>
    <div class="grid grid-cols-2 gap-4 mt-2">
        <div>
            <label class="font-medium">Total Gravada:</label>
            <span class="text-green-600 font-bold">${{ number_format($record->total_gravada, 2) }}</span>
        </div>
        <div>
            <label class="font-medium">Total IVA:</label>
            <span class="text-red-600 font-bold">${{ number_format($record->tot_iva, 2) }}</span>
        </div>
    </div>
</div>
