<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Admin Panel
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    Dobrodošli, {{ $user->fullName }}!
                    @if (!$user->hasVerifiedEmail() && !empty($user->email))
                        <div class="mt-4 p-4 bg-yellow-100 text-yellow-800 rounded">
                            Vaš email nije verifikovan. <a href="{{ route('verification.notice') }}" class="underline">Verifikujte email</a> da biste imali pun pristup panelu.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
