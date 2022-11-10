<?php

return [
    'razorpay_key_id' => env('RAZORPAY_KEY_ID', ''),

    'razorpay_key_secret' => env('RAZORPAY_KEY_SECRET', ''),

    'razorpay_api_url' => env('RAZORPAY_API_URL', 'https://api.razorpay.com/v1/'),

    'razorpay_contact_type' => env('RAZORPAY_CONTACT_TYPE', 'customer'),

    'razorpay_account_number' => env('RAZORPAY_ACCOUNT_NUMBER', ''),

    'razorpay_fa_try' => env('RAZORPAY_FA_TRY', 3),
    
    'xorazorpay_multiple_reference_payout_allowed' => env('xorazorpay_multiple_reference_payout_allowed', '0')
];