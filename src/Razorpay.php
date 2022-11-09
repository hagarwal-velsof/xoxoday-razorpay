<?php

namespace Xoxoday\Razorpay;
use Xoxoday\Razorpay\Model\xorazorpay_request;
use App\Jobs\RazorPayout;
use Xoxoday\Razorpay\Model\xorazorpay_payout;

class Razorpay
{
    //createRazorPayout($data) creates the request in the xorazor_request table and return the request id
    public function createRazorPayout($data)
    {
        if ((!empty(Config('xorazorpay.xorazorpay_multiple_reference_payout_allowed')) ? Config('xorazorpay.xorazorpay_multiple_reference_payout_allowed') : '0') == 0) {
            $check_reference_id = xorazorpay_payout::where(['reference_id' => $data['reference_id']])->first();
            if ($check_reference_id) {
                $xorazorpay_request = new xorazorpay_request();
                $xorazorpay_request->data = json_encode($data);
                $xorazorpay_request->status = 2;
                $xorazorpay_request->error = 'Payout for the reference ID is already created';
                $xorazorpay_request->save();
                return $xorazorpay_request->id;
            }
        }
        if ($data) {
            $xorazorpay_request = new xorazorpay_request();
            $xorazorpay_request->data = json_encode($data);
            $xorazorpay_request->status = 0;
            $xorazorpay_request->save();
            dispatch(new RazorPayout($xorazorpay_request->id));
            return $xorazorpay_request->id;
        }
        return;
    }
}
