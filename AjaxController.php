<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cookie;

use Illuminate\Support\Facades\Mail;
use App\Mail\NewLead;

use App\User;
use App\Package;
use App\Subscription;

use App\Http\Helpers\StripeHelper;

class AjaxController extends Controller
{
  public function verifyStripeCoupon(Request $request) {
    $stripeHelper = new StripeHelper();

    $coupon = $stripeHelper->getCoupon($request->input('coupon'), true);

    if ($coupon) {
      return response()->json([
        'status'  => 'success',
        'coupon'  => $coupon,
      ]);
    } else {
      $stripe_error = \Session::get('stripe_error');
      \Session::forget('stripe_error');

      return response()->json([
        'status'  => 'failure',
        'error'   => $stripe_error,
      ]);
    }
  }

  public function topUpCredits(Request $request) {
    $stripeHelper = new StripeHelper();

    $user = \Auth::user();

    if ($stripeHelper->createCharge($user->stripe_customer_id, $request->amount)) {
      $user->credit += $request->amount;

      $leadsBought = intval($request->amount / 1.00);

      $entries = \App\Entry::where('status', 'LIKE', '%unpaid%')->where('client_id', $user->id)->orderBy('created_at', 'ASC')->limit($leadsBought)->get();

      foreach ($entries as $entry) {
        $zapier = \App\Zapier::where('quiz_id', $entry->quiz_id)->first();

        if ($zapier) {
          \Curl::to($zapier->url)
          ->withData([
            'name' => $entry->name,
            'email' => $entry->email,
            'status' => $entry->status,
            'affiliate' => $entry->affiliate_id,
            //'questions' => $zapierQuestionData
          ])
          ->asJson(true)
          ->post();
        }

        if ($entry->status == 'qualified_unpaid') {
          $entry->status = 'qualified';
        } else if ($entry->status == 'unqualified_unpaid') {
          $entry->status = 'unqualified';
        }

        $entry->save();
      }

      $user->credit -= $entries->count();
      $user->save();

      return response()->json([
        'status' => 'success',
        'credit' => $user->credit,
      ]);
    } else {
      $error = \Session::get('stripe_error');
      \Session::forget('stripe_error');

      return response()->json([
        'status' => 'fail',
        'error'  => $error,
      ]);
    }
  }
}
