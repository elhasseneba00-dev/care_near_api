<?php

namespace App\Support;

class Phone
{
    public static function normalize(string $phone): string
    {
        // Keep digits only (MVP). You can later add country code logic
        return preg_replace('/\D+/', '', $phone);
    }
}
