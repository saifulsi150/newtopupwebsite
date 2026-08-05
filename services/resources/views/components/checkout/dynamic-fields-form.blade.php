@props(['product'])

@if($product->dynamic_fields && count($product->dynamic_fields) > 0)
    <div class="space-y-4 my-4 p-4 border border-gray-200 rounded-lg bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-900">Enter Your Account Information</h3>
        
        @foreach($product->dynamic_fields as $field)
            <div>
                <label for="dynamic_{{ $loop->index }}" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ $field['label'] ?? 'Required Field' }}
                </label>
                <input 
                    type="text"
                    id="dynamic_{{ $loop->index }}"
                    name="dynamic_fields[{{ $field['key'] ?? $loop->index }}]"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                    placeholder="{{ $field['label'] ?? 'Enter value' }}"
                    required
                    data-field-key="{{ $field['key'] ?? $loop->index }}"
                />
            </div>
        @endforeach
    </div>
@endif
