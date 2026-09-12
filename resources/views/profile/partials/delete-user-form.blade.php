<div class="flex items-start gap-4">
    <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
        <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
    </div>
    <div class="flex-1">
        <p class="text-sm text-gray-700">Once your account is deleted, all of its data — including your tickets — will be permanently removed. This action cannot be undone.</p>

        <button
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="mt-4 inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-red-600 hover:bg-red-700 transition"
        >
            Delete Account
        </button>
    </div>
</div>

<x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
    <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
        @csrf
        @method('delete')

        <h2 class="text-lg font-semibold text-gray-800">
            Are you sure you want to delete your account?
        </h2>

        <p class="mt-1 text-sm text-gray-500">
            Please enter your password to confirm you would like to permanently delete your account.
        </p>

        <div class="mt-5">
            <label for="password" class="sr-only">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                class="block w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500 text-sm"
                placeholder="Password"
            />
            <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">
                Cancel
            </button>

            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-red-600 hover:bg-red-700">
                Delete Account
            </button>
        </div>
    </form>
</x-modal>
