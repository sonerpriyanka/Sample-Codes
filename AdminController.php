<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

use Auth;
use DB;
use Mail;


use App\User;
use App\Entry;
use App\Package;
use App\Subscription;

use Carbon\Carbon;
use App\Http\Helpers\StripeHelper;

class AdminController extends Controller
{
	public function __construct(){
		$this->middleware('admin');
   
	}
    //
	public function dashboard(Request $request){
   
   

		// Scripts to enable Vixen.
		\DB::table('users')->where('id', 131)->update(['is_active' => 1]);
		\DB::table('subscriptions')->where('user_id', 131)->update(array('is_active' => 1));

		\DB::table('users')->where('id', 130)->update(['is_active' => 1]);
		\DB::table('subscriptions')->where('user_id', 130)->update(array('is_active' => 1));
	    
		$quizzes = \App\Quiz::all();
		
		return view("admin.dashboard",[
			'quizzes' => $quizzes
		]);
	}

	public function viewSingleLead($id){
		$user = Auth::user();
		$lead = \App\Entry::where('id',$id)->first();

		if(!$lead)
			return redirect("/leads");

		$quizData = json_decode($lead->data,1);
		$quiz = \App\Quiz::find($lead->quiz_id);
		$questions = \App\Question::where("quiz_id",$lead->quiz_id)->get();

		return view("client.leadinfo",[
			//"user" => $user,
			"lead" => $lead,
			"quiz" => $quiz,
			"quizData" => $quizData,
			"questions" => $questions,
		]);
	}

	public function newQuiz(){
		$clients = \App\User::all();

		return view("admin.newquiz",[
			'clients' => $clients
		]);
	}

	public function duplicateQuiz($id){
		$quiz = \App\Quiz::find($id);

		$questions = \App\Question::where("quiz_id",$quiz->id)->get();
		$qualifierApp = \App\Qualifier::where("quiz_id",$quiz->id)->first();

		$questionIndex = [];
		$answerIndex = [];
		$newQuiz = $quiz->replicate();
		$newQuiz->save();


		foreach($questions as $question){
			$newQuestion = $question->replicate();
			$newQuestion->quiz_id = $newQuiz->id;
			$newQuestion->save();
			$questionIndex[$question->id] = $newQuestion->id;

			foreach($question->answers as $answer){
				echo $answer->question_id;
				$newAnswer = $answer->replicate();
				$newAnswer->save();
				$answerIndex[$answer->id] = $newAnswer->id;
				$newAnswer->question_id = $newQuestion->id;
				$newAnswer->save();
			}

			if($newQuestion->qualifier != null){
				$qualifier = json_decode($newQuestion->qualifier,1);
				foreach($qualifier as $key=>$qualify){
					$qualifier[$key]["question"] = $questionIndex[$qualify["question"]];

					if($qualifier[$key]["condition"] == "==" || $qualifier[$key]["condition"] == "!="){
						$qualifier[$key]["value"] = $answerIndex[$qualifier[$key]["value"]];
					}
				}
				$newQuestion->qualifier = json_encode($qualifier);
			}

			$newQuestion->save();
		}


		$newQualifier = $qualifierApp->replicate();
		$newQualifier->save();
		$newQualifier->quiz_id = $newQuiz->id;
		$conditions = json_decode($newQualifier->conditions,1);
		foreach($conditions as $condition){
			foreach($condition["condition"] as $singleCondition){
				$singleCondition["question"] = $questionIndex[$singleCondition["question"]];

				if($singleCondition["condition"] == "==" || $singleCondition["condition"] == "!="){
					$singleCondition["answer"] = $answerIndex[$singleCondition["answer"]];
				}
			}
		}

		return response("Success!",200);
	}
  
  	public function deleteQuiz($id){
		$quiz = DB::delete('DELETE q,questions,qualifiers,answers,entries FROM `quizzes` AS q
                  LEFT JOIN questions ON questions.quiz_id = q.id
                  LEFT JOIN qualifiers ON qualifiers.quiz_id = q.id
                  LEFT JOIN answers ON answers.question_id = questions.id
                  LEFT JOIN entries ON entries.quiz_id = q.id
                  where q.id = "'.$id.'"');

		return response("Success",200);
	}
  
	public function createQuiz(Request $request){
		$this->validate($request, [
			"quiz_name" => "required|max:255",
			"client_id" => "required|numeric|min:1",
		]);

		$quiz = \App\Quiz::create([
			'name' => $request->quiz_name,
			'user_id' => $request->client_id
		]);

		if(!$quiz)
			return response("error",500);
		else
			return response($quiz->id,200);
	}

	public function editQuiz($id, Request $request){
		$quiz = \App\Quiz::find($id);
		$questions = \App\Question::where("quiz_id",$id)
			->orderBy('index', 'asc')
			->get();
		
		$clients = \App\User::all();

		// @Date: 2018/03/31
		// Custom Scripts to update doulbe quotes to single quotes;

		foreach ($questions as $question) {
			$answers = \App\Answer::where("question_id", $question->id)->get();
			foreach ($answers as $answer) {
				$new_content = str_replace('"', "'", $answer['content']);

				\App\Answer::where('id', $answer->id)->update(['content' => $new_content]);
			}
		}

		return view("admin.editquiz",[
			"quiz" => $quiz,
			"questions" => $questions,
			"error" => ($request->has('error'))?$request->error:'',
			"clients" => $clients
		]);
	}

	public function saveQuizSettings($id, Request $request){
		$this->validate($request, [
			"name" => "required|max:255"
		]);

		$quiz = \App\Quiz::find($id);
		$quiz->name =  $request->name;
		$quiz->allowBack = ($request->allowBack)?true:false;
		$quiz->lead_price = $request->lead_price;
		$quiz->user_id = $request->client_id;
		$quiz->img_percent = $request->img_percent;
		$quiz->img_saved_width = $request->img_saved_width;

		$quiz->right_img_percent = $request->right_img_percent;
		$quiz->right_img_saved_width = $request->right_img_saved_width;

		$quiz->save();

		$content = \App\Content::firstOrCreate([
			"quiz_id" => $id ]);
		$content->optinContent = $request->optinContent;
		$content->save();

		$zapier = \App\Zapier::firstOrCreate([
			"quiz_id" => $id
		]);

		$zapier->url = $request->zapier_url;
		$zapier->save();

		return response("Success!",200);
	}

	public function configureQuiz($id, Request $request){
		$quiz = \App\Quiz::find($id);
		$questions = \App\Question::where("quiz_id",$id)->get();

		if($questions->count() == 0)
			return redirect("/readinessritual/edit/".$quiz->id."?error=1");

		$firstQuestionWithAnswer;
		$hasAtLeastOneQuestionWithOneAnswer = false;
		foreach($questions as $question){
			if($question->type != 'text'){
				if(isset($question->answers[0])){
					$firstQuestionWithAnswer = $question;
					$hasAtLeastOneQuestionWithOneAnswer = true;
					break;
				}
			}
		}

		if(!$hasAtLeastOneQuestionWithOneAnswer)
			return redirect("/readinessritual/edit/".$quiz->id."?error=1");

		$qualifier = \App\Qualifier::where("quiz_id",$id)->first();

		if(!$qualifier){
			$qualifier = \App\Qualifier::create([
				'quiz_id' => $quiz->id,
				'conditions' => json_encode([
					[
						"condition" => [
							[
								"question" => $firstQuestionWithAnswer->id,
								"condition" => "==",
								"answer" => $firstQuestionWithAnswer->answers[0]->id
							]
						],
						"destination" => "",
                        "label"=>""
					]
				])
			]);
		}

		return view("admin.editlogic",[
			"quiz" => $quiz,
			"questions" => $questions,
			"qualifier" => $qualifier
		]);
	}

	public function publishQuiz($id){
		$quiz = \App\Quiz::find($id);

		$qualifier = \App\Qualifier::where("quiz_id",$quiz->id)->first();
		$conditions = json_decode($qualifier->conditions,1);
		if(!$conditions || sizeof($conditions) == 0){
			return redirect(url('/readinessritual/config/'.$quiz->id));
		}

		return view("admin.publishquiz",[
			"quiz" => $quiz
		]);
	}

	public function saveQualifier(Request $request){
		$this->validate($request, [
			"qualifier" => "required",
			"quiz_id" => "required|numeric",
			//"unqualified_url" => "required"
		]);

		$qualifier = \App\Qualifier::where("quiz_id",$request->quiz_id)->first();

		$qualifier->conditions = $request->qualifier;
		$qualifier->unqualified_url = $request->unqualified_url;

		if($qualifier->save())
			return response("Success",200);
		else
			return response("Error",500);
	}

	public function saveQuestionQualifier(Request $request){
		$this->validate($request, [
			"qualifier" => "required",
			"question_id" => "required|numeric",
			//"unqualified_url" => "required"
		]);

		$question = \App\Question::find($request->question_id);

		$question->qualifier = $request->qualifier;

		if($question->save())
			return response("Success",200);
		else
			return response("Error",500);
	}

	public function manageLeads(){
		$leads = \App\Entry::paginate(40);

		return view("admin.listleads",[
			"leads" => $leads
		]);
	}

	public function voidLead($id){
		$lead = \App\Entry::find($id);
		$lead->status = 'void';
		$lead->save();
		if($lead->client){
			$client = \App\User::find($lead->client_id);
			//++$client->credit;
			$client->save();
		}

		return response("Success!",200);
	}

	public function addQuestion(Request $request, $quiz_id){
		$this->validate($request, [
			"type" => "required",
		]);

		$lastQuestion = \App\Question::where("quiz_id",$quiz_id)->orderBy("index","DESC")->first();

		if($lastQuestion)
			$lastQuestionIndex = $lastQuestion->index;
		else
			$lastQuestionIndex = -1;

		$question = \App\Question::create([
			"type" => $request->type,
			"index" => $lastQuestionIndex + 1,
			"quiz_id" => $quiz_id,
			"content" => ""
		]);

		return response(json_encode([
			"id" => $question->id,
			"index" => $question->index,
			"content" => $question->content,
			"type" => $question->type,
			"answers" => [],
			"qualifier" => [],
			"image_url" => ""
		]),200);
	}

	public function deleteQuestion(Request $request, $question_id){
		$this->validate($request, [

		]);

		$question = \App\Question::find($question_id);

		$qualifier = \App\Qualifier::where('quiz_id',$question->quiz_id)->first();
		$conditions = '';

		if($qualifier){
			$conditions = json_decode($qualifier->conditions,1);

			foreach($conditions as $conditionKey=>$condition){
				foreach($condition["condition"] as $key=>$singleCondition){
					if($singleCondition["question"] == $question->id){
						$foundQuestion = false;
						$currentQuestion = 0;
						while(!$foundQuestion){
							$firstQuestion = \App\Question::where("quiz_id", $question->quiz_id)->offset($currentQuestion)->first();
							if(!$firstQuestion){
								unset($conditions[$conditionKey]["condition"][$key]);

								if(sizeof($conditions[$conditionKey]["condition"]) == 0){
									unset($conditions[$conditionKey]);

									if(sizeof($conditions) == 0){
										$conditions = false;
									}
								}

								$foundQuestion = true;
							}else{
								if( isset( $firstQuestion->answers[0] ) ){
									$conditions[$conditionKey]["condition"][$key]["question"] = $firstQuestion->id;
									$conditions[$conditionKey]["condition"][$key]["condition"] = "==";
									$conditions[$conditionKey]["condition"][$key]["answer"] = $firstQuestion->answers[0]->id;
									$foundQuestion = true;
								}else{
									++$currentQuestion;
								}
							}
						}
						//unset($conditions[$conditionKey]["condition"][$key]);
					}
				}
			}
		}

		if($conditions === false){
			$qualifier->delete();
		}else{
			if($conditions != ''){
				$qualifier->conditions = json_encode($conditions);
				$qualifier->save();
			}
		}

		$question->delete();

		return response("Success",200);
	}

	public function setQuestion(Request $request, $question_id){
		$this->validate($request, [

		]);

		$question = \App\Question::find($question_id);
		$question->content = $request->content;
		$question->save();

		return response("Success",200);
	}
  
  	public function setResponseRequired(Request $request, $question_id){
    
		$question = \App\Question::find($question_id);
		$question->response_required = $request->response_required;
		$question->save();

		return response("Success",200);
	}


	public function sortQuestion(Request $request, $question_id) {
		$question = \App\Question::find($question_id);
		$index = $question->index;

		$question_replaced = \App\Question::find($request->question_changed_id);

		$question->index = $question_replaced->index;
		$question_replaced->index = $index;

		$question->save();
		$question_replaced->save();

		return response("Success",200);
	}

	public function saveQuestionImgPercent(Request $request, $question_id){
    
		$question = \App\Question::find($question_id);
		$question->img_percent		 = $request->img_percent;
		$question->img_saved_width	 = $request->img_saved_width;

		$question->save();

		return response("Success",200);
	}

	public function deleteQuestionImage(Request $request, $question_id){
		$question = \App\Question::where('id',$question_id)->first();
		$question->image_url = "";
		$question->save();

		return response("Success",200);
	}

	public function uploadQuestionImage(Request $request){
		$image = $request->file('image');
		$imageFileName = str_random(6) . time() . '.' . $image->getClientOriginalExtension();
		$s3 = \Storage::disk('s3');
		$filePath = 'question-images/' . $imageFileName;
		$s3->put($filePath, file_get_contents($image), 'public');

		$finalpath = $s3->url($filePath);
		$question = \App\Question::find($request->question_id);
		$question->image_url = $finalpath;
		$question->save();

		return response($finalpath, 200);
	}

	public function deleteQuizImage(Request $request, $quiz_id){
		$quiz = \App\Quiz::where('id',$quiz_id)->first();
		$quiz->banner_url = "";
		$quiz->save();

		return response("Success",200);
	}

	public function uploadQuizImage(Request $request){
		$image = $request->file('image');
		$imageFileName = str_random(6) . time() . '.' . $image->getClientOriginalExtension();
		//print_r($imageFileName); die;
    	
    	$s3 = \Storage::disk('s3');
		$filePath = 'quiz-images/' . $imageFileName;
		$s3->put($filePath, file_get_contents($image), 'public');

		$finalpath = $s3->url($filePath);
    	
    	$quiz = \App\Quiz::find($request->quiz_id);
		$quiz->banner_url = $finalpath;
		$quiz->save();

		return response($finalpath, 200);
	}

	public function deleteQuizRightImage(Request $request, $quiz_id){
		$quiz = \App\Quiz::where('id',$quiz_id)->first();
		$quiz->right_banner_url = "";
		$quiz->save();

		return response("Success",200);
	}

	public function uploadQuizRightImage(Request $request){
		$image = $request->file('image');
		$imageFileName = str_random(6) . time() . '.' . $image->getClientOriginalExtension();
    	$s3 = \Storage::disk('s3');
		
		$filePath = 'quiz-images/' . $imageFileName;
		$s3->put($filePath, file_get_contents($image), 'public');

		$finalpath = $s3->url($filePath);
    	
    	$quiz = \App\Quiz::find($request->quiz_id);
		$quiz->right_banner_url = $finalpath;
		$quiz->save();

		return response($finalpath, 200);
	}

	public function addAnswer(Request $request, $question_id){
		$this->validate($request, [
			"type" => "required",
		]);

		$question = \App\Question::find($question_id);
		$lastAnswer = \App\Answer::where("question_id",$question_id)->orderBy("index","DESC")->first();

		if($lastAnswer)
			$lastAnswerIndex = $lastAnswer->index;
		else
			$lastAnswerIndex = -1;

		$content = ($request->type == "other")?"Other":"";

		if($lastAnswer && $lastAnswer->type == "other"){
			if($request->type == "other")
				return response(json_encode(["error"=>"Already have an 'Other' answer."]),500);

			$lastAnswer->index = $lastAnswer->index + 1;
			$lastAnswer->save();

			$answer = \App\Answer::create([
				"type" => $request->type,
				"index" => $lastAnswerIndex,
				"question_id" => $question_id,
				"content" => $content
			]);
		}else{
			$answer = \App\Answer::create([
				"type" => $request->type,
				"index" => $lastAnswerIndex + 1,
				"question_id" => $question_id,
				"content" => $content
			]);
		}

		return response(json_encode([
			"id" => $answer->id,
			"index" => $answer->index,
			"content" => $answer->content,
			"type" => $answer->type
		]),200);
	}

	public function setAnswer(Request $request, $answer_id){
		$this->validate($request, [
		]);

		$content = str_replace('"', "'", $request->content);
		
		$answer = \App\Answer::find($answer_id);
		$answer->content = $content;
		$answer->save();

		return response("Success",200);
	}

	public function setAnswerValue(Request $request, $answer_id){
		$this->validate($request, [
		]);

		$val = str_replace('"', "'", $request->value);
		
		$answer = \App\Answer::find($answer_id);
		$answer->value = $val;
		$answer->save();

		return response("Success",200);
	}

	public function deleteAnswer(Request $request, $answer_id){
		$this->validate($request, [

		]);

		$answer = \App\Answer::find($answer_id);

		$qualifier = \App\Qualifier::where("quiz_id",$answer->question->quiz_id)->first();
		if(!$qualifier){

		}else{
			$conditions = json_decode($qualifier->conditions,1);

			foreach($conditions as $conditionKey=>$condition){
				foreach($condition["condition"] as $key=>$singleCondition){
					if($singleCondition["answer"] == $answer->id){
						$foundAnswer = false;
						//$currentQuestion = 0;
						while(!$foundAnswer){
							$firstAnswer = \App\Answer::where("question_id",$answer->question->id)->where('id','!=',$answer->id)->first();
							//$firstQuestion = \App\Question::where("quiz_id", $question->quiz_id)->offset($currentQuestion)->first();
							if(!$firstAnswer){
								unset($conditions[$conditionKey]["condition"][$key]);

								if(sizeof($conditions[$conditionKey]["condition"]) == 0){
									unset($conditions[$conditionKey]);

									if(sizeof($conditions) == 0){
										$conditions = false;
									}
								}

								$foundAnswer = true;
							}else{
								$conditions[$conditionKey]["condition"][$key]["question"] = $firstAnswer->question_id;
								//$conditions[$conditionKey]["condition"][$key]["condition"] = "==";
								$conditions[$conditionKey]["condition"][$key]["answer"] = $firstAnswer->id;
								$foundAnswer = true;
							}
						}
						//unset($conditions[$conditionKey]["condition"][$key]);
					}
				}
			}

			if(isset($conditions) && $conditions === false){
				$qualifier->delete();
			}else{
				$qualifier->conditions = json_encode($conditions);
				$qualifier->save();
			}

		}

		$answer->delete();

		return response("Success",200);
	}

	public function manageClients(){
		//where("email","NOT LIKE","kim@kimklaveracademy.com")->
		$clients = \App\User::paginate(20);

		return view("admin.manageclients",[
			"clients" => $clients
		]);
	}

	public function addClient(Package $package){

		// data to pass to the registration form
        $data = [];
        $packageGroups = $package->get()->groupBy('type');
        $data['paypal_subscribed'] = session()->get('paypal_agreement') ? 1 : 0;

        return view('admin.addclient', compact('data', 'packageGroups'));
        
	}

	public function deleteClient($id){
		\App\User::find($id)->delete();

		return response("Success!",200);
	}

	public function editClient($id){

		$data = [];
        $data['paypal_subscribed']	 = session()->get('paypal_agreement') ? 1 : 0;
        $packages_list				 = Package::where('is_active', 1)->where('type', 'main')->get();
		$active_packages			 = Subscription::where("user_id", $id)->where("is_active", 1)->pluck("package_id")->toArray();

		$client = User::find($id);

		return view("admin.editclient", compact('data', 'packages_list', 'active_packages', 'client'));
	}

	public function processEditClient($id, Request $request){
		
		// Log::info($request);

		$this->validate($request, [
			"first_name"	 => "required|max:255|regex:/[a-zA-Z _]*/",
			"last_name"		 => "required|max:255|regex:/[a-zA-Z _]*/",
			"email"			 => "required|max:255|email",
			"package"		 => "required"
		]);

		//$starting_credit = (!$request->starting_credit)?0:$request->starting_credit;
		$stripeHelper = new StripeHelper();

		$user 		  = User::find($id);
		$data 		  = $request;
		$payment_type = $data['payment_type'];
		$stripe_customer_id  = $data['customer_id'];

		$credit = 0;
		$is_active_client = 0; // Make active client when it's charged successfully.

		$package 	 = $data['package'];
		$package_id  = null;			 // This is the new Package ID.
		$old_package_id = null; // This is the old package ID.

		$subscription = null;

		if ($payment_type == 'stripe' && isset($stripe_customer_id) && !is_null($stripe_customer_id)) {
			$is_new_client		= false;

			$active_customer = $stripeHelper->getCustomer($stripe_customer_id);
			
			// Log::info(' /****** Customer Info - start ******/');
   			// Log::info($active_customer);
			// Log::info(' /****** Customer Info - end ******/');

			// Log::info('Customer ID : ' . $stripe_customer_id);

			if (!$active_customer) {
				$name = $data['first_name'] . ' ' . $data['last_name'];

				// If this user don't have stripe account activated, then create it again with the inputs.
				$stripeCustomer = $stripeHelper->createCustomer('', $data['email'], $name);
				// Log::info($stripeCustomer['subscriptions']['data']);

	            if (!isset($stripeCustomer['id']) && $stripeCustomer['error_msg']) {

	                session()->put('error', 'Oops!  Check the numbers, the exp date and CVC and try again?');
	                return redirect()->back();
	            } else {
	            	$stripe_customer_id = $stripeCustomer['id'];
	            	$is_new_client		= true;
	            }
			} else {

				$customer = $active_customer;

				$main_package_obj = Package::where('name', $package)->first();
				$package_id = $main_package_obj->id;

				// create subscriptions in DB
				if($package_id == 1){
					$old_package_id = 2;
				} elseif ($package_id == 2){
					$old_package_id = 1;
				}

				if (!is_null($customer['default_source'])) {
				    // subscribe customer to stripe plans
				    if ($package == 'unlimited') {
				        
		          		// 1. Charge for the leads that used for this week, until now.
		 		    	$package_obj = Package::where('name', $package)->first();
		 		    	$ondemand_package_obj = Package::where('name', 'ondemand')->first();

						$fromDate = Carbon::now()->subDay()->startOfWeek(); // or ->format(..)
		                $tillDate = Carbon::now();

		                $leads_count = \App\Entry::whereBetween('created_at', [$fromDate, $tillDate])
		                    ->where('client_id', $user->id)
		                    ->get()
		                    ->count();

		                $price = $ondemand_package_obj->price * $leads_count;

		                if ($price > 0) {
		                    $charges = $stripeHelper->createCharge($stripe_customer_id, $price);
		                    // Log::info('Ondemand Plan is ended for this customer - ' . $user->id . ' - Charged Price : ' . $price );
		                }

		                // 2. Update DB - Make Inactive for the ondemand plan for this customer.
		                // Ondemand plans' package id - 2;
		                Subscription::where('user_id', $user->id)
		                    ->where('package_id', $old_package_id)
		                    ->update([
		                    	'is_active' => 0,
		                    	'ends_at'	=> Carbon::now()
		                    	]);

		                // 3. Subscribe this customer to unlimited plan.
		                // Check if already subscribed, if then just reactivate it.
		                // if not, subscribe.
						$unlimitedPackage = Subscription::where('user_id', $user->id)
		                    ->where('package_id', $package_id)
		                    ->first();

		                if ($unlimitedPackage) {

			                if (($unlimitedPackage['stripe_customer_id'] != $stripe_customer_id)) {
			                	// if stored stripe customer id is different with the input Stripe Customer ID,
			                	// Then use the customer with the input stripe cus_id.
			                	if ($stripeHelper->getCustomer($stripe_customer_id)) {
				                	$subscription = $stripeHelper->subscribeCustomer($stripe_customer_id, $package_obj->stripe_plan, 1);
				                	if (!isset($subscription['id']) && $subscription['error_msg']) {
				                		session()->put('error', 'Oops!  Check the numbers, the exp date and CVC and try again?');
				                		return redirect()->back();
				                	}
				                }

			                } else {
			                	if ( $unlimitedPackage['stripe_subscription_id']) {
			                		$getSubscription = $stripeHelper->getSubscription($unlimitedPackage['stripe_customer_id'], $unlimitedPackage['stripe_subscription_id']);

				                	if ($getSubscription) {
				                		$subscription = $stripeHelper->reactivateSubscription($unlimitedPackage['stripe_customer_id'], $unlimitedPackage['stripe_subscription_id']);
				                	} else {
				                		// Subscribed before but already ended.
				                		if ($stripeHelper->getCustomer($unlimitedPackage['stripe_customer_id'])) {
				                			$subscription = $stripeHelper->subscribeCustomer($unlimitedPackage['stripe_customer_id'], $package_obj->stripe_plan, 1);
				                		}
				                	}
			                	}
			                }
			            } else {
			            	if ($stripeHelper->getCustomer($stripe_customer_id)) {
			                	$subscription = $stripeHelper->subscribeCustomer($stripe_customer_id, $package_obj->stripe_plan, 1);
			                	if (!isset($subscription['id']) && $subscription['error_msg']) {
			                		session()->put('error', 'Oops!  Check the numbers, the exp date and CVC and try again?');
			                		return redirect()->back();
			                	}
			                }
			            }


						// Log::info($subscription);

						if (!is_null($subscription) && isset($subscription['id'])) {

							$is_active_client = 1;
			                // 4. Update DB - Make Active for the unlimited plan for this customer.
			                if ($unlimitedPackage['id']) {
			                	Subscription::where('user_id', $user->id)
				                    ->where('package_id', $package_id)
				                    ->update([
				                    	'stripe_customer_id'	 => $stripe_customer_id,
				                    	'stripe_subscription_id' => $subscription['id'],
				                    	'stripe_plan_id'		 => $package_obj->stripe_plan,
				                    	'is_active' 			 => 1,
				                    	'created_at' => Carbon::createFromTimestamp($subscription['created']),
				                        'updated_at' => Carbon::createFromTimestamp($subscription['current_period_start']),
				                        'billing_period_ends_at' => Carbon::createFromTimestamp($subscription['current_period_end'])
				                    	]);
			                } else {
			                	Subscription::create([
			                		'user_id'		 		 => $user->id,
			                		'stripe_customer_id'	 => $stripe_customer_id,
			                		'stripe_subscription_id' => $subscription['id'],
			                    	'stripe_plan_id'		 => $package_obj->stripe_plan,
			                		'package_id'			 => $package_id,
			                		'is_active'				 => 1,
			                		'created_at' => Carbon::createFromTimestamp($subscription['created']),
			                        'updated_at' => Carbon::createFromTimestamp($subscription['current_period_start']),
			                        'billing_period_ends_at' => Carbon::createFromTimestamp($subscription['current_period_end'])
			                		]);
			                }
		                }

				    } else if ($package == 'ondemand') {
				    	
				    	$is_active_client = 1;

		            	// 1. Cancel current subscription selected at period end.
				    	$subscription = Subscription::where('user_id', $user->id)
		                    ->where('package_id', $old_package_id)->first();

		                if ($subscription && $subscription->stripe_subscription_id) {
		                	$getSubscription = $stripeHelper->getSubscription($subscription->stripe_customer_id, $subscription->stripe_subscription_id);

		                	if ($getSubscription) {
		                		$stripeHelper->cancelSubscription($subscription->stripe_customer_id, $subscription->stripe_subscription_id, true);
		                	}
		                }

				    	// 2. Update DB - Update billing ends date for the unlimited plan of this customer.
				    	Subscription::where('user_id', $user->id)
		                    ->where('package_id', $old_package_id)
		                    ->update([
		                    	'is_active'	=> 0,
				                'ends_at'   => $subscription['billing_period_ends_at']
		                    	]);

		                // 3. Update DB - Make Active for the ondemand plan.
		                // We will make active for the ondemand plan for this customer with Schedule (Cron Job)
		                $ondemandPackage = Subscription::where('user_id', $user->id)
		                    ->where('package_id', $package_id)
		                    ->first();
		                if ($ondemandPackage['id']) {
		                	// updated_at will point the day that this ondemand plan will start.
		                	// It's just the billing ends of the unlimited plan.
		                	Subscription::where('user_id', $user->id)
			                    ->where('package_id', $package_id)
			                    ->update([
			                    	'stripe_customer_id'	 => $stripe_customer_id,
			                    	'is_active' 			 => 1,
			                    	'updated_at' 			 => $subscription['billing_period_ends_at'],
			                    	'ends_at'				 => null
			                    	]);
		                } else {
		                	Subscription::create([
		                		'user_id'		 		 => $user->id,
		                		'stripe_customer_id'	 => $stripe_customer_id,
		                		'package_id'			 => $package_id,
		                		'is_active'				 => 1,
		                		'updated_at'			 => $subscription['billing_period_ends_at'],
		                		'ends_at'				 => null
		                		]);
		                }
				    }
				}
			}
		}

    	$email = $user['email'];

    	if ($user['email'] != $data['email']) {
    		$user_with_input = User::where('email', $data['email'])->first();
    		if ($user_with_input === null) {
    			$email = $data['email'];
    		} else {
    			Session::flash('success', 'User already exists !');
    			Session::flash('alert-class', 'alert-danger');
				return redirect()->back();
    		}
    	}

		$user->fill([
			'first_name'	 => $data->first_name,
			'last_name'		 => $data->last_name,
			'email'			 => $email,
			'password'		 => ($data->password)?bcrypt($data->password):$user->password,
			'payment_type'		 => $payment_type,
			'stripe_customer_id' => $stripe_customer_id ? $stripe_customer_id : '',
			'is_active'			 => $is_active_client ? $is_active_client : 0
			//'credit' => $starting_credit
		]);

		$user->save();

		// return response("Success!",200);
		Session::flash('success', 'User successfully Updated !');
		return redirect()->back();
	}

	public function processAddClient(Request $request){

		// Log::info($request);

        $stripeHelper = new StripeHelper();
		$data = $request;

		$this->validate($request, [
			"first_name"	 => "required|max:255|regex:/[a-zA-Z _]*/",
			"last_name"		 => "required|max:255|regex:/[a-zA-Z _]*/",
			"email"			 => "required|max:255|unique:users|email",
			"password"		 => "min:6"

		]);

		//$starting_credit = (!$request->starting_credit)?0:$request->starting_credit;
		$stripeHelper = new StripeHelper();
	    $credit = 0;
	    $is_active = 0;
	    $is_active_unlimited = 0;
	    $is_active_ondemand = 0;

	    $payment_type		 = $data['payment_type'];
	    $package 			 = $data['package'];
	    $stripe_customer_id	 = '';
	    $striperes 			 = '';

	    $additional_package = isset($data['additional_package']) && !empty($data['additional_package'])? $data['additional_package']:'';

	    if ($payment_type == 'stripe' && isset($data['customer_id']) && !is_null($data['customer_id'])) {
	   	
	   		$stripe_customer_id = $data['customer_id'];
	      	$name = $data['first_name'] . ' ' . $data['last_name'];

			// 1. Check if Customer ID already exists in stripe Dashboard, if not create new one.;
			$active_customer = $stripeHelper->getCustomer($stripe_customer_id);
			
			// Log::info(' /****** Customer Info - start ******/');
   			// Log::info($active_customer);
			// Log::info(' /****** Customer Info - end ******/');

	        if (!$active_customer) {
	            // create stripe customer with the email and name.
	            $stripeCustomer = $stripeHelper->createCustomer('', $data['email'], $name);
	            // Log::info($stripeCustomer);

	            if (!isset($stripeCustomer['id']) && $stripeCustomer['error_msg']) {
	                session()->put('error', 'Oops!  Check the numbers, the exp date and CVC and try again?');
	                return redirect()->back();
	            } else {
	                $stripe_customer_id = $stripeCustomer['id'];
	            }
       		} else {
       			// If Exist Customer;
       			$customer = $active_customer;

       			if (!is_null($customer['default_source'])) {
	       			// 2. Charge for Additional Packs;
					if ($additional_package) {
		                $additional_package_obj = Package::where('name', $additional_package)->first();
		                if ($additional_package_obj->price > 0) {
		                    $charge = $stripeHelper->createCharge($stripe_customer_id, $additional_package_obj->price);

		                    if (!isset($charge['id'])) {
		                        session()->put('error', 'Oops!  Check the numbers, the exp date and CVC and try again?');
		                        return redirect()->back();
		                    }
		                }
		            }


		            // 3. Charge for Main Packages
		            // subscribe customer to stripe plans
		            if ($package == 'unlimited') {
		            	$unlimited_package = Package::where('name', $package)->first();

		                // subscribe customer to `unlimited` plan, it will have no trials and will be charged immediately.
		                if ($stripeHelper->getCustomer($stripe_customer_id)) {
		                	$striperes = $stripeHelper->subscribeCustomer($stripe_customer_id, $unlimited_package->stripe_plan, 1, false, 'now');

		                	if (!isset($striperes['id']) && $striperes['error_msg']) {
		                		session()->put('error', 'Oops!  Check the numbers, the exp date and CVC and try again?');
		                		return redirect()->back();
		                	}
		                }

		                // Log::info(' /****** $striperes - start ******/');
		                // Log::info($striperes);
						// Log::info(' /****** $striperes - end ******/');

		                $is_active = 1;
		                $is_active_unlimited = 1;

		            } else if ($package == 'ondemand') {
		                // Charge for ondemand Pckage - $price/lead
		                // It will be charged at the end of the week with the leads the customer used.
		                // Check kernel.php
		                $is_active = 1;
		                $is_active_ondemand = 1;
		            }
		        } else {
		        	$is_active = 0;
		        	$is_active_unlimited = 0;
		        	$is_active_ondemand	 = 0;
		        }
       		}
    	}

        $user = User::create([
            'name' => $data['name'] ? $data['name'] : strtolower($data['first_name']) . '_' . strtolower($data['last_name']),
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'address_line_1' => '',
            'apt_suite' => '',
            'city' => '',
            'country' => '',
            'state' => '',
            'zip' => '',
            'payment_type' => $payment_type,
            'stripe_customer_id' => $stripe_customer_id,
            'credit' => $credit,
            'is_active' => $is_active,
            'role' => 'client',
        ]);

        if ($package) {

	        $package_ids = [];
	        $main_package_obj = Package::where('name', $package)->first();
	        $package_ids[] = $main_package_obj->id;

	        if ($additional_package) {
	            $additional_package_obj = Package::where('name', $additional_package)->first();
	            $package_ids[] = $additional_package_obj->id;
	        }
	        // create subscriptions in DB
	        $user->subscriptions()->attach($package_ids);
	    }

        if ($package == 'ondemand') {
            Subscription::where('user_id', $user->id)
                ->update([
                    'stripe_customer_id' => $stripe_customer_id,
                    'is_active'			 => $is_active_ondemand
                ]);

        } elseif ($package == 'unlimited') {
            if ($additional_package) {
                Subscription::where('user_id', $user->id)
                    ->where('package_id', $additional_package_obj->id)
                    ->update([
                        'stripe_customer_id' => $stripe_customer_id
                    ]);

                if ($striperes) {
                	Subscription::where('user_id', $user->id)
	                    ->where('package_id', $main_package_obj->id)
	                    ->update([
	                        'stripe_customer_id' => $stripe_customer_id,
	                        'stripe_subscription_id' => $striperes['id'],
	                        'stripe_plan_id' => $striperes['plan']['id'],
	                        'quantity' => $striperes['quantity'],
	                        'is_active'			 => $is_active_unlimited,
	                        'created_at' => Carbon::createFromTimestamp($striperes['created']),
	                        'updated_at' => Carbon::createFromTimestamp($striperes['current_period_start']),
	                        'billing_period_ends_at' => Carbon::createFromTimestamp($striperes['current_period_end'])
	                    ]);
                } else {
                	Subscription::where('user_id', $user->id)
	                    ->where('package_id', $main_package_obj->id)
	                    ->update([
	                        'stripe_customer_id' => $stripe_customer_id,
	                        'is_active'			 => $is_active_unlimited
	                    ]);
                }
                
            } else {
            	if ($striperes) {
                Subscription::where('user_id', $user->id)
                    ->where('package_id', $main_package_obj->id)
                    ->update([
                        'stripe_customer_id' => $stripe_customer_id,
                        'stripe_subscription_id' => $striperes['id'],
                        'stripe_plan_id' => $striperes['plan']['id'],
                        'quantity' => $striperes['quantity'],
                        'is_active'			 => $is_active_unlimited,
                        'created_at' => Carbon::createFromTimestamp($striperes['created']),
                        'updated_at' => Carbon::createFromTimestamp($striperes['current_period_start']),
                        'billing_period_ends_at' => Carbon::createFromTimestamp($striperes['current_period_end'])
                    ]);
                } else {
                	Subscription::where('user_id', $user->id)
	                    ->where('package_id', $main_package_obj->id)
	                    ->update([
	                        'stripe_customer_id' => $stripe_customer_id,
	                        'is_active'			 => $is_active_unlimited
	                    ]);
                }
            }
        }

		// return response("Success!",200);
		Session::flash('success', 'User successfully Added !');
		return redirect()->back();
	}



	public function packages(Package $package)
	{
		return view('admin.packages.index', ['packageGroups' => $package->all()->groupBy('type')]);
	}

	public function packageUpdate($id, Request $request, Package $package )
    {
        
        $request->session()->forget('package_id');
        $request->session()->put('package_id', $id);

        if ($id == 1) {
            // Unlimited Subscription Plan
            $this->validate($request, [
                "description" => "required",
                "big_description" => "required",
                "stripe_plan" => "required"
            ]);

            $stripeHelper = new StripeHelper();
            $plan = $stripeHelper->getPlan($request->stripe_plan);

            if ($plan) {
                $inputs = $request->all();
                $inputs['price'] = $plan['amount'] / 100;
                $inputs['is_active'] = isset($inputs['is_active']) ?  1 : 0 ; 
                $package->find($id)->update($inputs);

                return redirect()->back()->with('success', 'Package successfully Updated');
            } else {
                return redirect()->back()->with('error', 'Oops!  Check the plan ID and try again?');
            }

        } else {
            
            // Fixed Price Packages
            $this->validate($request, [
                "description" => "required",
                "big_description" => "required",
                "price" => "required|regex:/^\d*(\.\d{1,2})?$/"
            ]);

            $inputs = $request->all();
            $inputs['is_active'] = isset($inputs['is_active']) ?  1 : 0 ; 
            $package->find($id)->update($inputs);

            return redirect()->back()->with('success', 'Package successfully Updated');
        }
    }
}
