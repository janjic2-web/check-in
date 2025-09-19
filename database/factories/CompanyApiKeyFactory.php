<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyApiKeyFactory extends Factory
{
    protected $model = CompanyApiKey::class;

    public function definition(): array
    {
        return [
            'company_id'    => Company::factory(),
            'key'          => self::makeKey(),
            'active'       => true,
            'is_superadmin'=> false,
            'expires_at'   => null,
        ];
    }

    /**
     * Generiše base64url ključ dužine 32B (URL-friendly, bez '=').
     */
    public static function makeKey(int $bytes = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    /**
     * State: superadmin API ključ.
     */
    public function superadmin(): self
    {
        return $this->state(fn () => ['is_superadmin' => true]);
    }

    /**
     * State: neaktivan API ključ.
     */
    public function inactive(): self
    {
        return $this->state(fn () => ['active' => false]);
    }

    /**
     * State: ključevi za konkretnu kompaniju.
     */
    public function forCompany(Company $company): self
    {
        return $this->state(fn () => ['company_id' => $company->id]);
    }
}
