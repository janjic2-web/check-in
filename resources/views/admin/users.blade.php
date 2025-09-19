<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Lista zaposlenih
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th>Ime</th>
                                <th>Email</th>
                                <th>Status verifikacije</th>
                                <th>Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>{{ $user->fullName }}</td>
                                    <td>{{ $user->email ?? '-' }}</td>
                                    <td>
                                        @if (empty($user->email))
                                            <span class="bg-gray-300 text-gray-700 px-2 py-1 rounded">No email</span>
                                        @elseif ($user->hasVerifiedEmail())
                                            <span class="bg-green-200 text-green-800 px-2 py-1 rounded">Verified</span>
                                        @else
                                            <span class="bg-yellow-200 text-yellow-800 px-2 py-1 rounded">Unverified</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!empty($user->email) && !$user->hasVerifiedEmail())
                                            <form method="POST" action="{{ route('admin.users.resend', $user->id) }}">
                                                @csrf
                                                <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded">Resend verification email</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
