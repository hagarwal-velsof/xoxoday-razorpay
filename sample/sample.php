<?php
namespace App\Http\Controllers;
use Xoxoday\Razorpay\Razorpay;
use App\Jobs\RazorPayout;

class TestController extends Controller
{
  public function contact()
  {
    //Pass the data accordingly in the $data array
    $data = array("name" => '', "email" => '', "prefix" => '', "mobile" => '', "upi_id" => '', "reference_id" => '', 'amount' => '');

    //Creates an object of Razorpay class
    $razor = new Razorpay();
    $request_id = $razor->createRazorPayout($data);

    //Creates payout for the given user data
    dispatch(new RazorPayout($request_id));
    die('__');
  }
}
