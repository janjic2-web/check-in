@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-xl py-8">
    <h2 class="text-2xl font-bold mb-6">Podešavanje kompanije</h2>
    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-3 mb-4 rounded">{{ session('success') }}</div>
    @endif
    <form method="POST" action="{{ route('admin.company.setup') }}">
        @csrf
        <div class="mb-4">
            <label for="display_name" class="block font-semibold">Naziv kompanije *</label>
            <input type="text" name="display_name" id="display_name" value="{{ old('display_name', $company->display_name ?? '') }}" class="form-input w-full @error('display_name') border-red-500 @enderror" required maxlength="255" autofocus>
            @error('display_name')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        <div class="mb-4">
            <label for="timezone" class="block font-semibold">Timezone</label>
            <input type="text" name="timezone" id="timezone" value="{{ old('timezone', $company->timezone ?? '') }}" class="form-input w-full">
        </div>
        <div class="mb-4">
            <label for="language" class="block font-semibold">Jezik</label>
            <input type="text" name="language" id="language" value="{{ old('language', $company->language ?? '') }}" class="form-input w-full">
        </div>
        <div class="mb-4">
            <label for="status" class="block font-semibold">Status</label>
            <input type="text" name="status" id="status" value="active" class="form-input w-full" readonly>
        </div>
        <hr class="my-6">
        <h3 class="font-bold mb-2">Pravni/Adresa (opciono)</h3>
        <div class="mb-4">
            <label for="legal_name" class="block">Pravno ime</label>
            <input type="text" name="legal_name" id="legal_name" value="{{ old('legal_name', $company->legal_name ?? '') }}" class="form-input w-full">
        </div>
        <div class="mb-4">
            <label for="vat_pib" class="block">PIB/VAT</label>
            <input type="text" name="vat_pib" id="vat_pib" value="{{ old('vat_pib', $company->vat_pib ?? '') }}" class="form-input w-full">
        </div>
        <div class="mb-4">
            <label for="address" class="block">Adresa</label>
            <input type="text" name="address" id="address" value="{{ old('address', $company->address ?? '') }}" class="form-input w-full">
        </div>
        <div class="mb-4">
            <label for="city" class="block">Grad</label>
            <input type="text" name="city" id="city" value="{{ old('city', $company->city ?? '') }}" class="form-input w-full">
        </div>
        <div class="mb-4">
            <label for="zip" class="block">Poštanski broj</label>
            <input type="text" name="zip" id="zip" value="{{ old('zip', $company->zip ?? '') }}" class="form-input w-full">
        </div>
        <div class="mb-4">
            <label for="country" class="block">Država</label>
            <input type="text" name="country" id="country" value="{{ old('country', $company->country ?? '') }}" class="form-input w-full">
        </div>
        <hr class="my-6">
        <h3 class="font-bold mb-2">Policy</h3>
        <div class="mb-4">
            <label for="allow_outside" class="block">Dozvoli check-in van zone</label>
            <input type="checkbox" name="allow_outside" id="allow_outside" value="1" {{ old('allow_outside', $company->allow_outside ?? false) ? 'checked' : '' }}>
        </div>
        <div class="mb-4">
            <label for="default_radius_m" class="block">Default radius (m) *</label>
            <input type="number" name="default_radius_m" id="default_radius_m" value="{{ old('default_radius_m', $company->default_radius_m ?? 50) }}" class="form-input w-full @error('default_radius_m') border-red-500 @enderror" min="1" required>
            @error('default_radius_m')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        <div class="mb-4">
            <label for="anti_spam_min_interval" class="block">Anti-spam min interval (min)</label>
            <input type="number" name="anti_spam_min_interval" id="anti_spam_min_interval" value="{{ old('anti_spam_min_interval', $company->anti_spam_min_interval ?? 5) }}" class="form-input w-full" min="0">
        </div>
        <div class="mb-4">
            <label for="min_inout_gap_min" class="block">Min IN/OUT gap (min)</label>
            <input type="number" name="min_inout_gap_min" id="min_inout_gap_min" value="{{ old('min_inout_gap_min', $company->min_inout_gap_min ?? 10) }}" class="form-input w-full" min="0">
        </div>
        <div class="mb-4">
            <label for="ble_min_rssi" class="block">BLE min RSSI</label>
            <input type="number" name="ble_min_rssi" id="ble_min_rssi" value="{{ old('ble_min_rssi', $company->ble_min_rssi ?? -70) }}" class="form-input w-full @error('ble_min_rssi') border-red-500 @enderror" min="-100" max="-30" required>
            @error('ble_min_rssi')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        <div class="mb-4">
            <label for="require_gps_checkin" class="block">Zahtevaj GPS check-in</label>
            <input type="checkbox" name="require_gps_checkin" id="require_gps_checkin" value="1" {{ old('require_gps_checkin', $company->require_gps_checkin ?? false) ? 'checked' : '' }}>
        </div>
        <div class="mb-4">
            <label for="offline_retention_hours" class="block">Offline retention (h)</label>
            <input type="number" name="offline_retention_hours" id="offline_retention_hours" value="{{ old('offline_retention_hours', $company->offline_retention_hours ?? 24) }}" class="form-input w-full" min="0">
        </div>
        <div class="flex gap-4 mt-8">
            <button type="submit" name="action" value="save" class="bg-blue-600 text-white px-4 py-2 rounded">Sačuvaj</button>
            <button type="submit" name="action" value="continue" class="bg-green-600 text-white px-4 py-2 rounded">Sačuvaj i nastavi</button>
        </div>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var errorField = document.querySelector('.border-red-500');
        if (errorField) {
            errorField.focus();
        }
    });
</script>
@endsection
