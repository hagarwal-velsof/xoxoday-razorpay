<?php

namespace App\Jobs;

use App\Models\Cashback;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use PDO;
use Xoxoday\Razorpay\Model\xorazorpay_contact;
use Xoxoday\Razorpay\Model\xorazorpay_payout;
use Xoxoday\Razorpay\Model\xorazorpay_request;

class RazorPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $name;
    private $email;
    private $prefix;
    private $mobile;
    private $upi_id;
    private $amount;
    private $reference_id;
    private $request_id;
    //construct takes the request ID and fetches the data from the request table
    public function __construct($request_id)
    {
        $this->request_id = $request_id;
        $request_data = xorazorpay_request::where(['id' => $request_id])->first();
        if ($request_data) {
            $data = json_decode($request_data['data'], true);
            $this->name = !empty($data["name"]) ? $data["name"] : '';
            $this->email = !empty($data["email"]) ? $data["email"] : '';
            $this->prefix = !empty($data["prefix"]) ? $data["prefix"] : 0;
            $this->mobile = !empty($data["mobile"]) ? $data["mobile"] : 0;
            $this->upi_id = !empty($data["upi_id"]) ? $data["upi_id"] : '';
            $this->amount = !empty($data["amount"]) ? $data["amount"] : 0;
            $this->reference_id = !empty($data["reference_id"]) ? $data["reference_id"] : 0;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    //handle function is called when the job is called.
    public function handle()
    {
        if ((!empty(Config('xorazorpay.xorazorpay_multiple_reference_payout_allowed')) ? Config('xorazorpay.xorazorpay_multiple_reference_payout_allowed') : '0') == 0) {
            $check_reference_id = xorazorpay_payout::where(['reference_id' => $this->reference_id])->first();
            if ($check_reference_id) {
                xorazorpay_request::where('id', $this->request_id)->update(['error' => 'Duplicate reference ID not allowed', 'status' => '2']);
                return;
            }
        }
        //To check if the UPI ID already added to xorazorpay_contact table
        $xorazorpay_contact = xorazorpay_contact::where('upi_id', $this->upi_id)->first();

        //If the UPI id doesn't exist create a new contact
        if (!$xorazorpay_contact) {
            $xorazorpay_contact = new xorazorpay_contact();
            $xorazorpay_contact->name = $this->name;
            $xorazorpay_contact->email = $this->email;
            $xorazorpay_contact->prefix = $this->prefix;
            $xorazorpay_contact->mobile = $this->mobile;
            $xorazorpay_contact->upi_id = $this->upi_id;
            if (!$xorazorpay_contact->save()) {
                xorazorpay_request::where('id', $this->request_id)->update(['error' => 'Contact not created ', 'status' => '2']);
            }
        }
        if ($xorazorpay_contact['upi_id']) {
            if ($xorazorpay_contact['razorpay_contact_id'] == null) {       //Create a new contact if the razorpay_contact_id is null
                $contact_id =  $this->createContact($xorazorpay_contact['name'], $xorazorpay_contact['email'], $xorazorpay_contact['mobile']);
                if ($contact_id) {
                    xorazorpay_contact::where('id', $xorazorpay_contact['id'])->update(['razorpay_contact_id' => $contact_id]);        //Updating the razopay contact information to the xorazorpay_contact table
                }
                $xorazorpay_contact['razorpay_contact_id'] = $contact_id;
            }
            if ($xorazorpay_contact['razorpay_contact_id']) {
                $fa_id = $this->createFundAccount($this->upi_id, $xorazorpay_contact['razorpay_contact_id']);
                $fa_id = $this->createFundAccount($xorazorpay_contact['upi_id'], $xorazorpay_contact['razorpay_contact_id']);

                //To generate fa_id it razorpay API takes time. So, we are applying the retry method
                $try = 0;
                while ($fa_id == false) {
                    sleep(3);
                    if ($try >= Config('xorazorpay.razorpay_fa_try')) {
                        break;
                    } else {
                        $fa_id = $this->createFundAccount($xorazorpay_contact['upi_id'], $xorazorpay_contact['razorpay_contact_id']);
                        $try++;
                    }
                }
                if ($fa_id) {
                    xorazorpay_contact::where('id', $xorazorpay_contact['id'])->update(['razorpay_fund_account_id' => $fa_id]);
                    $transaction_id = $this->createPayout($fa_id, $this->amount);
                    if ($transaction_id) {
                        $xorazorpay_payouts = new xorazorpay_payout();
                        $xorazorpay_payouts->xorazorpay_contact_id = $xorazorpay_contact['id'];
                        $xorazorpay_payouts->reference_id = $this->reference_id;
                        $xorazorpay_payouts->amount = $this->amount;
                        $xorazorpay_payouts->status  = 1;
                        $xorazorpay_payouts->xorazorpay_request_id = $this->request_id;
                        $xorazorpay_payouts->transaction_id = $transaction_id;
                        if ($xorazorpay_payouts->save()) {
                            xorazorpay_request::where('id', $this->request_id)->update(['error' => '', 'status' => '1']);
                        }
                    } else {
                        xorazorpay_request::where('id', $this->request_id)->update(['error' => 'Transaction failled', 'status' => '2']);
                    }
                } else {
                    xorazorpay_request::where('id', $this->request_id)->update(['error' => 'Fund account Id not Created', 'status' => '2']);
                }
            } else {
                xorazorpay_request::where('id', $this->request_id)->update(['error' => 'Contact Id not Created', 'status' => '2']);
            }
        }
    }

    //CreateContact is used to create Razorpay Contact. It returns contact ID.
    public function createContact($name, $email, $mobile)
    {
        $key_id = !empty(Config('xorazorpay.razorpay_key_id')) ? Config('xorazorpay.razorpay_key_id') : ''; // razorpay key id credentials

        $key_secret = !empty(Config('xorazorpay.razorpay_key_secret')) ? Config('xorazorpay.razorpay_key_secret') : ''; //razorpay key secret credentials

        $url = (!empty(Config('xorazorpay.razorpay_api_url')) ?  Config('xorazorpay.razorpay_api_url') : 'https://api.razorpay.com/v1/') . 'contacts';

        $data = array(
            'name' => $name,
            'email' => $email,
            'contact' => $mobile,
            'type' => !empty(Config('xorazorpay.razorpay_contact_type')) ? Config('xorazorpay.razorpay_contact_type') : 'customer'
        );

        $response = Http::withBasicAuth($key_id, $key_secret)->withHeaders([
            'Content-type' => 'application/json',
        ])->post($url, $data);

        if ($response->status() == 200 || $response->status() == 201) {
            $result = json_decode(json_encode($response->object()), true);
            if (isset($result['id'])) {
                return  $result['id'];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //It uses the upi_id and razorpay contact ID. It returns razorpay fund account ID
    public function createFundAccount($upi, $contact_id)
    {

        $key_id = !empty(Config('xorazorpay.razorpay_key_id')) ? Config('xorazorpay.razorpay_key_id') : ''; // razorpay key id credentials

        $key_secret = !empty(Config('xorazorpay.razorpay_key_secret')) ? Config('xorazorpay.razorpay_key_secret') : ''; //razorpay key secret credentials

        $url = (!empty(Config('xorazorpay.razorpay_api_url')) ?  Config('xorazorpay.razorpay_api_url') : 'https://api.razorpay.com/v1/') . 'fund_accounts';

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


    //It takes the razorpay fund account and amount. It returns payout ID.
    public function createPayout($fa_id, $amount)
    {
        $key_id = !empty(Config('xorazorpay.razorpay_key_id')) ? Config('xorazorpay.razorpay_key_id') : ''; // razorpay key id credentials

        $key_secret = !empty(Config('xorazorpay.razorpay_key_secret')) ? Config('xorazorpay.razorpay_key_secret') : ''; //razorpay key secret credentials

        $url = (!empty(Config('xorazorpay.razorpay_api_url')) ?  Config('xorazorpay.razorpay_api_url') : 'https://api.razorpay.com/v1/') . 'payouts';

        $amount = $amount * 100; //coverting ruppees to paisa for razorpay payout api

        $data = array(
            'account_number' => !empty(Config('xorazorpay.razorpay_account_number')) ? Config('xorazorpay.razorpay_account_number') : '',
            'fund_account_id' => $fa_id,
            'amount' => $amount,
            "currency" => "INR",
            "mode" => !empty(Config('xorazorpay.razorpay_payout_mode')) ? Config('xorazorpay.razorpay_payout_mode') : "UPI", //Avaliable Options UPI, 
            "purpose" => !empty(Config('xorazorpay.razorpay_payout_purpose')) ? Config('xorazorpay.razorpay_payout_purpose') : "cashback", //Avaliable Options cashback, refund
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
