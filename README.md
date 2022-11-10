**Razorpay Package**

## What is Razorpay Api?

This API is used for making payouts or refunds.

## Installation

Require this package with composer. 

```sh
$ composer require xoxoday/razorpay
```

## Database tabel migration

Create xorazorpay_payouts, xorazorpay_contacts and xorazorpay_requests tables in your database.

```sh
$ php artisan migrate
```

## Publish package

Create config/xorazorpay.php and Jobs/RazorPayout.php file using the following artisan command:

```sh
$ php artisan vendor:publish  --tag="razorpay_files"
```

## Complete configuration

### Set your credentials

Open config/xoplum.php configuration file and set your credentials:

```php

return [
    'razorpay_key_id' => env('RAZORPAY_KEY_ID', 'Set your key ID'),

    'razorpay_key_secret' => env('RAZORPAY_KEY_SECRET', 'Set Your key secret'),

    'razorpay_api_url' => env('RAZORPAY_API_URL', 'https://api.razorpay.com/v1/'),

    'razorpay_contact_type' => env('RAZORPAY_CONTACT_TYPE', 'customer'),	

    'razorpay_account_number' => env('RAZORPAY_ACCOUNT_NUMBER', 'Set your Account Number'),

    'razorpay_fa_try' => env('RAZORPAY_FA_TRY', 3),
    'xorazorpay_multiple_reference_payout_allowed' => env('xorazorpay_multiple_reference_payout_allowed', '0')
];

```

## How to use

Refer code from the sample.php file and execute the functionality of the package
	