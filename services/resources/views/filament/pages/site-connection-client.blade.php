<x-filament::page>
    <form wire:submit="connect" class="space-y-6">
        {{ $this->form }}

        <div class="flex gap-3">
            <x-filament::button type="submit">
                Connect
            </x-filament::button>

            <x-filament::button type="button" color="success" wire:click="syncAllData">
                Data Sync
            </x-filament::button>

            <x-filament::button type="button" color="gray" wire:click="refreshStatus">
                Refresh Status
            </x-filament::button>
        </div>

        @if ($this->lastSyncMessage !== '')
            <div class="rounded-lg border p-3 text-sm {{ $this->lastSyncOk ? 'border-success-300 bg-success-50 text-success-700' : 'border-danger-300 bg-danger-50 text-danger-700' }}">
                <span class="font-semibold">{{ $this->lastSyncOk ? '✓ Sync successful:' : 'Sync failed:' }}</span>
                <span>{{ $this->lastSyncMessage }}</span>
            </div>
        @endif
    </form>
</x-filament::page>