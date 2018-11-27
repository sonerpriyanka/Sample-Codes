<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Cartalyst\Stripe\Stripe;

class checkoutController extends Controller
{
    //
	public function processCheckout(Request $request){
		$stripe = new Stripe();

		try{
			$charge = $stripe->charges()->create(
			[
				'source' => $request->stripeToken,
				'amount' => $request->amount,
				'currency' => 'USD',
				'receipt_email' => $request->email
			]
			);
		}catch(\Cartalyst\Stripe\Exception\CardErrorException $e){
			$message = $e->getMessage();
			return response("Credit card error. Try a different card or make sure you entered the correct details.",500);
		}
		
		if($charge["status"]=="succeeded"){
			$user = \Auth::user();
			$user->credit += $request->amount / 100;

			$leadsBought = intval($request->amount / 100 / 1.00);
			
			$entries = \App\Entry::where("status","LIKE","%unpaid%")->where("client_id",$user->id)->orderBy("created_at","ASC")->limit($leadsBought)->get();
			
			foreach($entries as $entry){
				$zapier = \App\Zapier::where("quiz_id",$entry->quiz_id)->first();
				if($zapier){
					\Curl::to($zapier->url)
					->withData([
						"name" => $entry->name,
						"email" => $entry->email,
						"status" => $entry->status,
						"affiliate" => $entry->affiliate_id,
						//"questions" => $zapierQuestionData
					])
					->asJson(true)
					->post();
				}
				
				if($entry->status == "qualified_unpaid"){
					$entry->status = "qualified";
				}else if($entry->status == "unqualified_unpaid"){
					$entry->status = "unqualified";
				}
				$entry->save();
			}
			
			$user->credit -= $entries->count();
			$user->save();
			
			return response($user->credit,200);
		}else{
			return response("Unknown error.",500);
		}
	}
}
