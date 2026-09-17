<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add User</h2>
    </x-slot>

    <div class="py-8 max-w-lg mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6 sm:p-8">
            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3.5">
                    <p class="text-sm font-semibold text-red-800 mb-1">Please fix the following:</p>
                    <ul class="text-sm text-red-700 list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5" novalidate>
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                    <input type="password" name="password" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
                    <select name="role" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white">
                        <option value="staff" @selected(old('role') === 'staff')>Staff</option>
                        <option value="it_support" @selected(old('role') === 'it_support')>IT Support</option>
                        <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Branch</label>
                    <select name="location" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white" required>
                        <option value="" disabled @selected(old('location') === null)>Select branch</option>
                        @foreach(\App\Models\Asset::LOCATIONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('location') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('location') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_vip" value="1" class="rounded border-gray-300 text-green-700 focus:ring-green-700" @checked(old('is_vip'))>
                    <span class="text-sm text-gray-600">Mark as VIP (tickets auto-escalate to Critical)</span>
                </label>

                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <a href="{{ route('admin.users.index') }}" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm" style="background-color:#1a6b3c;">Create User</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
