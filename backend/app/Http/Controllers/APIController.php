<?php

namespace App\Http\Controllers;

use App\Model\Appointment;
use App\Helper\Message;
use Illuminate\Http\Request;


use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

use Carbon\Carbon;
class APIController extends Controller
{

    public function demo(){
        $today = Carbon::now();
        return $today;
    }

    public function show(Request $request, $id){
        $appointment = Appointment::find($id);
        return $appointment;
    }


    public function index(){
        $body = Appointment::all();
        return Message::dataMessage($body)->response();
        
    }

    public function store(Request $request){
        if(! ($request->has("started_at") && $request->has("ended_at") ) ) 
            return Message::errorMessage(10008, "Appointment doesn't have started_at or ended_at dates")->response();
        $started_at = Carbon::createFromFormat('Y-m-d H:i:s', $request->input("started_at"));
        $ended_at = Carbon::createFromFormat('Y-m-d H:i:s', $request->input("ended_at"));
        $contact_info = $request->input("contact_info");

        $validation = $this->validateAppointment($started_at, $ended_at);
        if($validation != null)
            return $validation;
        
    
        $appointment = new Appointment;
        $appointment->started_at = $started_at;
        $appointment->ended_at = $ended_at;
        $appointment->contact_info = $contact_info;
        $appointment->save();    
        $body = Appointment::find($appointment->id);


        return Message::dataMessage($body)->response();
    }

    public function destroy(Request $request, $id){
        $appointment = Appointment::find($id);
        if($appointment == null)
            return Message::errorMessage(10007, "Appointment doesn't exist or has been deleted")->response();
        $appointment->delete();
        return Message::dataMessage($appointment)->response();   
    }

    public function update(Request $request , $id){

        $started_at = Carbon::createFromFormat('Y-m-d H:i:s', $request->input("started_at"));
        $ended_at = Carbon::createFromFormat('Y-m-d H:i:s', $request->input("ended_at"));
        $contact_info = $request->input("contact_info");
        $validation = $this->validateAppointment($started_at, $ended_at, $id);
        if($validation != null)
            return $validation;
        
        
        $appointment = Appointment::find($id);
        if($appointment == null)
            return Message::errorMessage(10007, "Appointment doesn't exist or has been deleted")->response();
        $appointment->started_at = $started_at;
        $appointment->ended_at = $ended_at;
        $appointment->contact_info = $contact_info;
        $appointment->save();    
        $body = $appointment;

        return Message::dataMessage($body)->response();
    }
    
    private function validateAppointment($started_at, $ended_at, $id = null){
        $startingWorkingHours = $started_at->copy()->startOfDay()->addHours(9);
        $endWorkingHours =$ended_at->copy()->startOfDay()->addHours(18);
        
        $checkForAppointment = null;
        if($id != null){
            
            $checkForAppointment = Appointment::where("started_at",">=",$started_at)
                                            ->where("ended_at","<=", $ended_at)
                                            ->where("id","!=",$id)
                                            ->get();    
        }else{
            $checkForAppointment = Appointment::where("started_at",">=",$started_at)
                                            ->where("ended_at","<=", $ended_at)
                                            ->get();    
        }
        

        if($started_at->diffInMinutes($ended_at) != 60)
            return Message::errorMessage(10001, "Appointment must be an hour long")->response();
        if($checkForAppointment->count() > 0)
            return Message::errorMessage(10002, "Appointment already set for this time range")->response();
        if(! $started_at->isWeekday())
            return Message::errorMessage(10003, "started_at must be during the weekday")->response();
        if(! $ended_at->isWeekday())
            return Message::errorMessage(10004, "ended_at must be during the weekday")->response();
        if(! $started_at->between($startingWorkingHours, $endWorkingHours) || !$ended_at->between($startingWorkingHours, $endWorkingHours))
            return Message::errorMessage(10005, "Appointment must be made during office hours")->response();
        if($started_at->gt($ended_at))
            return Message::errorMessage(10006, "ended_at cannot be greater than started_at")->response();
        return null;
    }
}
