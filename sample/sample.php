<?php
namespace App\Http\Controllers;
use Xoxoday\Razorpay\Razorpay;

class TestController extends Controller
{
  public function contact()
  {
    //Pass the data accordingly in the $data array
    $data = array("name" => '', "email" => '', "prefix" => '', "mobile" => '', "upi_id" => '', "reference_id" => '', 'amount' => '');

    //Creates an object of Razorpay class
    $razor = new Razorpay();
    $request_id = $razor->createRazorPayout($data);


  }
}
