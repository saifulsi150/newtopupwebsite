<x-filament-panels::page.simple>
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-600 dark:bg-red-950 dark:text-red-400">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ url('/admin/login') }}" wire:submit="authenticate" class="fi-form grid gap-y-6">
        @csrf

        <div class="grid gap-y-2">
            <label for="email" class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 text-sm font-medium leading-6 text-gray-950 dark:text-white">
                Email address <span class="text-red-500">*</span>
            </label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                wire:model="data.email"
                required
                autofocus
                autocomplete="email"
                class="fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-base text-gray-950 shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 dark:border-gray-700 dark:bg-white/5 dark:text-white sm:text-sm"
            />
        </div>

        <div class="grid gap-y-2">
            <label for="password" class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 text-sm font-medium leading-6 text-gray-950 dark:text-white">
                Password <span class="text-red-500">*</span>
            </label>
            <input
                id="password"
                name="password"
                type="password"
                wire:model="data.password"
                required
                autocomplete="current-password"
                class="fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-base text-gray-950 shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 dark:border-gray-700 dark:bg-white/5 dark:text-white sm:text-sm"
            />
        </div>

        <div class="flex items-center justify-between">
            <label class="fi-fo-checkbox flex items-center gap-x-3">
                <input
                    id="remember"
                    name="remember"
                    type="checkbox"
                    value="1"
                    wire:model="data.remember"
                    class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500 dark:border-gray-700 dark:bg-white/5"
                />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Remember me</span>
            </label>
        </div>

        <button
            type="submit"
            class="fi-btn relative inline-flex items-center justify-center font-semibold rounded-lg px-4 py-2.5 text-sm shadow-sm bg-amber-600 text-white hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:bg-amber-500 dark:hover:bg-amber-400 w-full transition"
        >
            Sign in
        </button>
    </form>
</x-filament-panels::page.simple>
