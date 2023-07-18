<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class StripeKeyValidator implements Rule
{
    public function passes($attribute, $value)
    {
        $pattern = '/^(pk_live_|sk_test_|sk_live_|pk_test_)/';

        return preg_match($pattern, $value);
    }

    public function message()
    {
        return 'The :attribute is not a valid Stripe key.';
    }
}