<?php


namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use App\User;
use App\Appointment;
use Auth;
use Session;
use Log;
use Twilio;
use Services_Twilio;
use Twilio\Services_Twilio_Twiml;
use App\Url;
use Redirect;
Use Twilio\Twiml;
use Response;
use Closure;
use Illuminate\Http\RedirectResponse;

class RemindersController extends Controller
{

    public function getWebhook($id)
    {
        // Initial TwiML request
        //$appointment = Appointment::find($id);
//        $appointment = DB::table('appointment')
//                     ->select('*')
//                     ->where('id', $id)                     
//                     ->get();
        //$this->updateCallStatus(Input::get('CallStatus'));
        $appointment_date = date("F j, Y, g:i a",strtotime('2017-05-30 00:00:00'));
//        $response = new Services_Twilio_Twiml();
//        $gather = $response->gather(array(
//            'action' => "http://drminder.local/reminders/respond/1",
//            'method' => 'GET',
//            'numDigits' => '1'
//        ));
//        $gather->say("Wuff wuff.  This is a reminder from paws and purrs.  Your appointment is scheduled for tomorrow, $appointment_date.  Press one to confirm or two to reschedule.", array('loop' => 3));
        echo "Wuff wuff.  This is a reminder from paws and purrs.  Your appointment is scheduled for tomorrow, $appointment_date.  Press one to confirm or two to reschedule.";
    }
    
    public function updateCallStatus($status)
    {
//        $this->call_status = $status;
//        $this->save();
    }

    public function update1(Request $request,$app_id)
    {       
        $entered_num = $_REQUEST['Digits'];
        $response = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        if($entered_num == '1')
        {
            $affected = \App\Appointment::where('id', $app_id)
                        ->update(['status' => '2']);
            $response .= "<Response><Say>Your Appointment has been confirmed . Thank you .</Say></Response>";  
        }
        else
        {
            $affected = \App\Appointment::where('id', $app_id)
                        ->update(['status' => '3']);
            $response .= "<Response><Say>Your Appointment has been cancelled for now .Call us at office for reschedule . Thank You .</Say></Response>"; 
        }
       
             
        return Response::make($response, '200')->header('Content-Type', 'text/xml');
    }
    
    public function getRespond($id){
        // TwiML request in response to Dialpad Gather
//        $digits = Input::get('Digits');
//        $response = new Services_Twilio_Twiml();
//        if ($digits == "1")
//            $response->say("Excellent!  We'll see you tomorrow.   Goodbye.");
//        else
//            $response->say("Please call our office to reschedule.  Goodbye.");
        echo "$response";
    }

    public function getEnd($id)
    {
         
        //$appointment = Appointment::find($id);
//        $appointment = DB::table('appointment')
//                     ->select('*')
//                     ->where('id', $id)                     
//                     ->get();
//       // $appointment->updateCallStatus(Input::get('CallStatus'));
//        $response = new Services_Twilio_Twiml();
        echo "$response";
        //echo "test";
    }
    
    public function getSend($id)
    {
        //$appointment = Appointment::find($id);
        
        $appointment = DB::table('appointment')
                     ->select('*')
                     ->where('id', $id)                     
                     ->get();
             
        $result = $this->sendReminder($appointment);
        Session::flash('message', $result);
        return Redirect::to('appointment');
    }
    
    public function sendReminder($appointment)
    {echo "fjjjf";
        Log::info("Sending reminder for appointment ". $appointment[0]->id .": ".$appointment[0]->name);
        $client = new Services_Twilio(
            getenv('TWILIO_SID'),
            getenv('TWILIO_TOKEN')
        );
        
        $appointment = DB::table('appointment')
                     ->select('*')
                     ->where('id', $appointment[0]->id)                     
                     ->get();
        //$this->updateCallStatus(Input::get('CallStatus'));
        $appointment_date = date("F j, Y, g:i a",strtotime('2017-05-20 00:00:00'));
        
//        $response = new Services_Twilio_Twiml();
//        $gather = $response->gather(array(
//            'action' => URL::to("reminders/respond/$appointment[0]->id"),
//            'method' => 'GET',
//            'numDigits' => '1'
//        ));
        //$gather->say("Wuff wuff.  This is a reminder from paws and purrs.  Your appointment is scheduled for tomorrow, $appointment_date.  Press one to confirm or two to reschedule.", array('loop' => 3));

        try {
//            $call = Twilio::call($appointment[0]->phone, function ($message) {
//                $message->say('Hello how r uh');
//            });
            
            
            //$response1 = new Services_Twilio();
            $message = "Congrats! Here's your golden ticket.";
           $call = $client->account->calls->create(
                getenv('TWILIO_FROM'),
                $appointment[0]->phone,
               //"http://demo.twilio.com/docs/voice.xml",
                   //"http://drminder.local/voice.xml",
                   //"http://drminder.local/reminders/webhook/1",
                   //URL::to("reminders/webhook/{$appointment[0]->id}"),
                //array('Method' => 'GET', 'StatusCallbackMethod' => 'GET', 'StatusCallback' => ("http://drminder.local/reminders/end/1") )                          
                   "http://drminder.local/reminders/outbound/+919893568099"
            );
                
                
            $appointment[0]->call_sid = $call->sid;
            $appointment[0]->call_status = $call->status;
            Log::info("Started call ".$appointment[0]->call_sid);
        } catch (Services_Twilio_RestException $e) {
            $appointment[0]->call_status = $e->getMessage();
            Log::info("Twilio error ".$appointment[0]->call_status);
        }
        //$appointment->save();
        return "Reminder call for ".$appointment[0]->name." is ".$appointment[0]->call_status;
    }
    
    function outbound($salesPhone)
    {
        $sayMessage = 'Thanks for contacting our sales department. Our
        next available representative will take your call.';

        $twiml = new Services_Twilio_Twiml();
        $twiml->say($sayMessage, array('voice' => 'alice'));
        $twiml->dial($salesPhone);

        $response = Response::make($twiml, 200);
        $response->header('Content-Type', 'text/xml');
        return $response;
    }
    
}

?>
