<div class="p-4 bg-gray-100 rounded-lg shadow mt-4">
    <div class="flex justify-between">
        <span class="font-bold">Subtotal:</span>
        <span>${{ number_format($subtotal, 2) }}</span>
    </div>
    <div class="flex justify-between">
        <span class="font-bold">IVA (10%):</span>
        <span>${{ number_format($iva, 2) }}</span>
    </div>
    <div class="flex justify-between text-lg font-bold">
        <span>Total:</span>
        <span>${{ number_format($total, 2) }}</span>
    </div>
</div>
