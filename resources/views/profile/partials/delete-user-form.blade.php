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

<x-modal name="confirm-user-deletion" maxWidth="md" labelledby="confirm-user-deletion-title" :show="$errors->userDeletion->isNotEmpty()" focusable>
    <form
        method="post"
        action="{{ route('profile.destroy') }}"
        x-data="{ busy: false }"
        x-on:submit="busy = true"
        x-on:pageshow.window="busy = false"
    >
        @csrf
        @method('delete')

        <div class="px-6 pb-5 pt-6 text-center sm:flex sm:items-start sm:gap-4 sm:text-left">
            <span class="mx-auto flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 sm:mx-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            </span>

            <div class="mt-3 min-w-0 flex-1 sm:mt-0.5">
                <h2 id="confirm-user-deletion-title" class="text-lg font-semibold leading-6 text-gray-900">Delete your account?</h2>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">
                    This permanently removes your account and tickets, and it can't be undone. Enter your password to confirm.
                </p>

                <div class="mt-4 text-left">
                    <label for="password" class="sr-only">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        data-autofocus
                        autocomplete="current-password"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-red-500 focus:ring-red-500"
                        placeholder="Your password"
                    />
                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end sm:gap-3">
            <button
                type="button"
                x-on:click="$dispatch('close')"
                x-bind:disabled="busy"
                class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-2 disabled:opacity-60 sm:py-2"
            >
                Cancel
            </button>

            <button
                type="submit"
                x-bind:disabled="busy"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70 sm:py-2"
            >
                <svg x-show="busy" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span x-text="busy ? 'Deleting…' : 'Delete account'">Delete account</span>
            </button>
        </div>
    </form>
</x-modal>
