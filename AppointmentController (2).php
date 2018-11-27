<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use App\Appointment;
use Auth;
use Session;
use Response;
use View;
use App ;

class AppointmentController extends Controller
{
    //
    
    
    public function index()
    {
        //$appointments = \Appointment::all();
        $appointments = DB::table('appointment')
                     ->select('*')                 
                     ->get();
        
        return View::make('appointment.index')
            ->with('appointments', $appointments);
    }
    
    

    public function create()
    {
        //return View::make('appointment.create');
        return view('appointment/create');
    }

    public function store()
    {
        $rules = array(
            'phone'      => 'required',
            'appointment_time' => 'required|date'
        );
        $validator = Validator::make(Input::all(), $rules);

        if ($validator->fails()) {
            return Redirect::to('appointment/create')
                ->withErrors($validator)
                ->withInput(Input::except('password'));
        } else {
            // store
            $appointment = new Appointment;
            $appointment->name = Input::get('name');
            $appointment->phone = Input::get('phone');
            $appointment->appointment_time = Input::get('appointment_time');
            $appointment->save();

            // redirect
            Session::flash('message', 'Successfully created appointment!');
            return Redirect::to('appointments');
        }
    }

    public function destroy($id)
    {
        // delete
        $appointment = Appointment::find($id);
        $appointment ->delete();

        // redirect
        Session::flash('message', 'Successfully deleted the appointment');
        return Redirect::to('appointments');
    }
    
    public function sendReminder()
    {
        Log::info("Sending reminder for appointment $this->id: $this->name");
//        $client = new Services_Twilio(
//            Config::get('twilio.AccountSid'),
//            Config::get('twilio.AuthToken')
//        );
        
        $client = new Client(
            Config::get('twilio.AccountSid'),
            Config::get('twilio.AuthToken')
        );
        try {
            $call = $client->account->calls->create(
                getenv('TWILIO_FROM'),
                $this->phone,
                URL::to("reminders/webhook/{$this->id}"),
                array('Method' => 'GET', 'StatusCallbackMethod' => 'GET', 'StatusCallback' => URL::to("reminders/end/{$this->id}"))
            );
            $this->call_sid = $call->sid;
            $this->call_status = $call->status;
            Log::info("Started call $this->call_sid.");
        } catch (Services_Twilio_RestException $e) {
            $this->call_status = $e->getMessage();
            Log::info("Twilio error $this->call_status");
        }
        $this->save();
        return "Reminder call for $this->name is $this->call_status";
    }
}
