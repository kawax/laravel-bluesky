<?php

namespace Revolution\Bluesky\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Revolution\Bluesky\Support\Identity;

class AtHandle implements ValidationRule
{
    /**
     * Indicates whether the rule should be implicit.
     */
    public bool $implicit = true;

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Identity::isHandle($value)) {
            $fail('The :attribute must be handle format.');
        }
    }
}
