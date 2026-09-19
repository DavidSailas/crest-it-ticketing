<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add User</h2>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="flex items-center gap-2.5 px-6 sm:px-8 py-5 border-b border-gray-100">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 text-green-700 shrink-0">
                    <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z" /></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-gray-800">New Account</p>
                    <p class="text-xs text-gray-400">Personal info, login, and access details</p>
                </div>
            </div>

            <div class="p-6 sm:p-8">
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

                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6" novalidate>
                    @csrf

                    {{-- Personal info --}}
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Personal Info</p>
                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">First Name</label>
                                <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="e.g. Maria"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Last Name</label>
                                <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="e.g. Santos"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                            </div>
                        </div>
                    </div>

                    {{-- Login --}}
                    <div class="pt-5 border-t border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Login</p>
                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                                <input type="text" name="username" value="{{ old('username') }}" placeholder="e.g. maria.santos"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                                <p class="text-xs text-gray-400 mt-1">Letters, numbers, dashes, and underscores only.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}" placeholder="name@company.com"
                                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                                <p class="text-xs text-gray-400 mt-1">Used to sign in.</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                                <input type="password" name="password"
                                       class="block w-full sm:w-1/2 rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700" required>
                                <p class="text-xs text-gray-400 mt-1">At least 8 characters.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Assignment --}}
                    <div class="pt-5 border-t border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Assignment</p>
                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Department</label>
                                <select name="department_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white" required>
                                    <option value="" disabled @selected(old('department_id') === null)>Select department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                @error('department_id') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Position</label>
                                <select name="position_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-green-700 focus:ring-green-700 bg-white" required>
                                    <option value="" disabled @selected(old('position_id') === null)>Select position</option>
                                    @foreach($positions as $position)
                                        <option value="{{ $position->id }}" @selected((string) old('position_id') === (string) $position->id)>{{ $position->name }}</option>
                                    @endforeach
                                </select>
                                @error('position_id') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                                @if($positions->isEmpty())
                                    <p class="text-xs text-amber-600 mt-1.5">No positions yet — <a href="{{ route('admin.positions.index') }}" class="underline font-medium">add one first</a>.</p>
                                @endif
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
                                    @foreach(\App\Models\Asset::locations() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('location') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('location') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <label class="flex items-center gap-2.5 rounded-lg border border-amber-100 bg-amber-50/60 px-4 py-3 cursor-pointer">
                        <input type="checkbox" name="is_vip" value="1" class="rounded border-gray-300 text-amber-500 focus:ring-amber-500" @checked(old('is_vip'))>
                        <span class="text-sm text-gray-700">Mark as <span class="font-semibold">VIP</span> — tickets auto-escalate to Critical priority</span>
                    </label>

                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <a href="{{ route('admin.users.index') }}" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm hover:opacity-90 transition" style="background-color:#1a6b3c;">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
