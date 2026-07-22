<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Password::defaults() is registered in AppServiceProvider. Any code that
     * calls Password::default() picks up min-12, letters+digits+symbols,
     * mixed case. `uncompromised()` is production-only.
     */
    public function test_strong_password_passes_default_policy(): void
    {
        $validator = Validator::make(
            ['pw' => 'Str0ng-P4ssword!'],
            ['pw' => ['required', Password::default()]],
        );

        $this->assertTrue($validator->passes(), 'Compliant password should pass');
    }

    /**
     * @dataProvider weakPasswords
     */
    public function test_weak_passwords_fail_default_policy(string $pw, string $reason): void
    {
        $validator = Validator::make(
            ['pw' => $pw],
            ['pw' => ['required', Password::default()]],
        );

        $this->assertTrue($validator->fails(), "Weak password '$pw' should fail ($reason)");
    }

    public static function weakPasswords(): array
    {
        return [
            'too short' => ['Ab1!ok',            'less than 12 chars'],
            'no digits' => ['NoDigitsAnywhere!', 'letters + symbol but no digit'],
            'no symbols' => ['NoSymbols123ABCD',  'alnum only'],
            'no mixed case' => ['all-lower-1234!!',  'no uppercase letter'],
            'no letters' => ['12345678!@#$',      'digits + symbols only'],
        ];
    }
}
