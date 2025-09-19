@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-lg py-8">
    <h2 class="text-2xl font-bold mb-6">Registracija kompanije</h2>
    <form method="POST" action="{{ route('signup.company_admin') }}">
        @csrf
        <input type="hidden" name="token" value="{{ old('token', $token ?? request('token')) }}">

        <div class="mb-4">
            <label for="company_display_name" class="block font-semibold">Naziv kompanije</label>
            <input type="text" name="company_display_name" id="company_display_name" value="{{ old('company_display_name') }}" class="form-input w-full @error('company_display_name') border-red-500 @enderror" required maxlength="255" autofocus>
            @error('company_display_name')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="name" class="block font-semibold">Ime</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-input w-full @error('name') border-red-500 @enderror" required maxlength="255">
            @error('name')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="surname" class="block font-semibold">Prezime</label>
            <input type="text" name="surname" id="surname" value="{{ old('surname') }}" class="form-input w-full @error('surname') border-red-500 @enderror" required maxlength="255">
            @error('surname')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="email" class="block font-semibold">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-input w-full @error('email') border-red-500 @enderror" required maxlength="255">
            @error('email')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="block font-semibold">Lozinka</label>
            <input type="password" name="password" id="password" class="form-input w-full @error('password') border-red-500 @enderror" required minlength="8">
            @error('password')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="block font-semibold">Potvrda lozinke</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-input w-full" required minlength="8">
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Registruj kompaniju</button>
    </form>
</div>
<script>
    // Fokus na prvo nevalidno polje
    document.addEventListener('DOMContentLoaded', function() {
        var errorField = document.querySelector('.border-red-500');
        if (errorField) {
            errorField.focus();
        }
    });
</script>
@endsection
