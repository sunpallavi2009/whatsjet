<?php

namespace App\Yantrana\Components\TallyConnectSms\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Yantrana\Base\BaseController;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\AuthenticationFailedException;
use App\Yantrana\Components\GmailToWeb\GmailToWebEngine;

class TallyConnectSmsController extends BaseController
{

     /**
     * @var GmailToWebEngine - Contact Engine
     */
    protected $gmailToWebEngine;

    /**
     * Constructor
     *
     * @param  GmailToWebEngine  $gmailToWebEngine  - Contact Engine
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(GmailToWebEngine $gmailToWebEngine)
    {
        $this->gmailToWebEngine = $gmailToWebEngine;
    }

    public function showTallyConnectSMSView($groupUid = null)
    {
        validateVendorAccess('manage_contacts');
        $contactsRequiredEngineResponse = $this->gmailToWebEngine->prepareContactRequiredData($groupUid);

        // load the view
        return $this->loadView('tallyconnectsms.sms', $contactsRequiredEngineResponse->data());
    }

    public function showTallyConnectSendSms(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:10',
            'message' => 'required|string|min:20',
            // Add more validation rules as needed
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Generate OTP logic (if needed)
        // $otp_number = "0000";
        // $otp = $otp_number . " is your OTP for TallyConnects Software Activation. OTP is valid for 10 minutes. Regards TallyConnects";
        $message = $request->input('message');
        // dd($message);
        // $message = "0000 is your OTP for TallyConnects Software Activation. OTP is valid for 10 minutes. Regards TallyConnects";

        // SMS Gateway API configuration
        $api_key = '1563752033911917499';
        $contacts = $request->input('phone');
        $from = 'XLTALY';

        // Setup CURL request to SMS Gateway
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://www.smsgateway.center/SMSApi/rest/send",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query(array(
                'userId' => 'irriion',
                'password' => 'Excel@123#',
                'mobile' => '91' . $contacts,
                'msg' => $message,
                'senderId' => $from,
                'msgType' => 'text',
                'duplicateCheck' => 'true',
                'format' => 'json',
                'sendMethod' => 'simpleMsg',
            )),
            CURLOPT_HTTPHEADER => array(
                "apikey: " . $api_key,
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            // Handle CURL error
            Log::error('SMS sending CURL Error: ' . $err);
            // return redirect()->back()->with('error', 'Error sending SMS. Please try again later.');
            session()->flash('error', 'Error sending SMS. Please try again later. ' . $err);
        } else {
            // Decode JSON response
            $response_data = json_decode($response, true);
            if (isset($response_data['status']) && $response_data['status'] === 'success') {
                // SMS sent successfully
                session()->flash('success', 'SMS sent successfully.' );
                return redirect()->back()->with('success', 'SMS sent successfully.');
            } else {
                // SMS sending failed
                Log::error('SMS sending failed: ' . $response);
                session()->flash('error', 'Failed to send SMS. Please check your details and try again.' );
                return redirect()->back()->with('error', 'Failed to send SMS. Please check your details and try again.');
            }
        }
    }

    
}
