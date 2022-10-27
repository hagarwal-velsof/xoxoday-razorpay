<?php 

namespace Xoxoday\Razorpay;

use Config;
use Illuminate\Support\Facades\Http;


class Razorpay
{
    public function createContact($name,$email,$mobile){

        $key_id = Config('app.razorpay_key_id'); // razorpay key id test credentials

        $key_secret = Config('app.razorpay_key_secret'); //razorpay key secret test credentials

        $url = Config('app.razorpay_api_url').'contacts';

        $data = array(
            'name' => $name,
            'email' => $email,
            'contact' => $mobile,
            'type' => Config('app.razorpay_contact_type')
        );

        $response = Http::withBasicAuth($key_id, $key_secret)->withHeaders([
            'Content-type' => 'application/json',
        ])->post($url, $data);

        

        if ($response->status() == 200) {
            $result = json_decode(json_encode($response->object()), true);
            if(isset($result['id'])){
                return  $result['id'];
            }else{
                return false;
            }
        }else{
            return false;
        }
    }


    public function createFundAccount($upi, $contact_id)
    {

        $key_id = Config('app.razorpay_key_id'); // razorpay key id test credentials

        $key_secret = Config('app.razorpay_key_secret'); //razorpay key secret test credentials

        $url = Config('app.razorpay_api_url') . 'fund_accounts';

        $data = array(
            'account_type' => 'vpa',
            'contact_id' => $contact_id,
            'vpa' => array(
                "address" => $upi,
            ),
        );

        $response = Http::withBasicAuth($key_id, $key_secret)->withHeaders([
            'Content-type' => 'application/json',
        ])->post($url, $data);

        if ($response->status() == 200) {
            $result = json_decode(json_encode($response->object()), true);
            if (isset($result['id'])) {
                return $result['id'];
            } else {
                return false;
            }
        } else {
            return false;
        }

    }



    public function createPayout($fa_id, $amount)
    {

        $key_id = Config('app.razorpay_key_id'); // razorpay key id test credentials

        $key_secret = Config('app.razorpay_key_secret'); //razorpay key secret test credentials

        $url = Config('app.razorpay_api_url') . 'payouts';

        $amount = $amount * 100; //coverting ruppees to paisa for razorpay payout api

        $data = array(
            'account_number' => Config('app.razorpay_account_number'),
            'fund_account_id' => $fa_id,
            'amount' => $amount,
            "currency" => "INR",
            "mode" => "UPI",
            "purpose" => "cashback",
            "queue_if_low_balance" => true,
        );

        $response = Http::withBasicAuth($key_id, $key_secret)->withHeaders([
            'Content-type' => 'application/json',
        ])->post($url, $data);

        if ($response->status() == 200) {
            $result = json_decode(json_encode($response->object()), true);
            if (isset($result['id'])) {
                return $result['id'];
            } else {
                return false;
            }
        } else {
            return false;
        }

    }





}