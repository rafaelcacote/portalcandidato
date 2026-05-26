<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Carbon;

trait ParsesDatetimeLocalInAppTimezone
{
    /**
     * @param  list<string>  $fields
     */
    protected function parseDatetimeLocalFields(array $fields): void
    {
        $timezone = config('app.timezone');
        $parsed = [];

        foreach ($fields as $field) {
            $value = $this->input($field);

            if (! is_string($value) || $value === '') {
                continue;
            }

            $parsed[$field] = Carbon::parse($value, $timezone);
        }

        if ($parsed !== []) {
            $this->merge($parsed);
        }
    }
}
