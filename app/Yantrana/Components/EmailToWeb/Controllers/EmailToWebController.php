<?php
/**
* ContactController.php - Controller file
*
* This file is part of the Contact component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\EmailToWeb\Controllers;

use App\Models\EmailToWeb;
use Illuminate\Http\Request;
use Webklex\PHPIMAP\ClientManager;
use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseRequest;
use Illuminate\Support\Facades\Gate;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequestTwo;
use Illuminate\Database\Query\Builder;
use App\Yantrana\Components\EmailToWeb\EmailToWebEngine;

class EmailToWebController extends BaseController
{
    /**
     * @var EmailToWebEngine - Contact Engine
     */
    protected $emailToWebEngine;

    /**
     * Constructor
     *
     * @param  EmailToWebEngine  $emailToWebEngine  - Contact Engine
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(EmailToWebEngine $emailToWebEngine)
    {
        $this->emailToWebEngine = $emailToWebEngine;
    }

    /**
     * list of Contact
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function showEmailToWebView($groupUid = null)
    {
        validateVendorAccess('manage_contacts');
        $contactsRequiredEngineResponse = $this->emailToWebEngine->prepareContactRequiredData($groupUid);

        // load the view
        return $this->loadView('email-to-web.list', $contactsRequiredEngineResponse->data());
    }

    public function showCredentialsForm()
    {
        return view('email-to-web.emailsettings');
    }

    public function fetchEmailsWithCredentials(Request $request)
    {
        try {
            // Validate and get username and password from the request
            $credentials = $request->validate([
                'username' => 'required|string|email',
                'password' => 'required|string',
            ]);
    
            \Log::info('Attempting to connect to IMAP server...');
    
            // Create an instance of ClientManager
            $clientManager = new ClientManager();
    
            // Attempt to connect to IMAP server
            $client = $clientManager->make([
                'host'          => 'irriion.com',
                'port'          => 993,
                'encryption'    => 'ssl',
                'validate_cert' => true,
                'username'      => $credentials['username'],
                'password'      => $credentials['password'],
                'protocol'      => 'imap',
            ]);
    
            \Log::info('Attempting to connect...');
    
            $client->connect(); // Connect to the IMAP server
    
            \Log::info('Connected to IMAP server.');
    
            $inbox = $client->getFolder('INBOX'); // Get the INBOX folder
            $messages = $inbox->messages()->all()->get(); // Fetch all messages
    
            \Log::info('Fetched messages from INBOX.');
    
            $newEmailsCount = 0;
            $existingEmailsCount = 0;
    
            foreach ($messages as $message) {
                $receivedDate = $message->getDate();
                $from = $message->getFrom();
                $fromEmail = $from[0]->mail;
    
                // Check if email already exists in the database
                $existingEmail = EmailToWeb::where('received_at', $receivedDate)
                    ->where('from_email', $fromEmail)
                    ->first();
    
                if (!$existingEmail) {
                    $email = new EmailToWeb();
    
                    $email->vendors__id = 1;
                    
                    // Process From email address
                    $email->from_email = $fromEmail;
    
                    // Process To email address
                    $to = $message->getTo();
                    $email->to_email = $to[0]->mail;
    
                    $email->subject = $message->getSubject();
                    $email->body = $message->getTextBody(); // Example: Get text body of email
    
                    // Handle attachments (example for PDF)
                    $attachments = $message->getAttachments();
                    foreach ($attachments as $attachment) {
                        if ($attachment->getMimeType() === 'application/pdf') {
                            try {
                                // Save PDF to storage and store path in database
                                $path = $attachment->save(storage_path('app/public/attachments'));
                
                                // Verify and log the saved path
                                \Log::info('PDF saved to: ' . $path);
                
                                // Store the path in the database field
                                $email->pdf_attachment = $path;
                            } catch (\Exception $e) {
                                \Log::error('Error saving PDF attachment: ' . $e->getMessage());
                                // Handle error as needed
                            }
                        }
                        // Add logic for other attachment types if needed
                    }
    
                    // Save received date
                    $email->received_at = $receivedDate;
    
                    $email->save();
                    $newEmailsCount++;
                } else {
                    \Log::info('Email already exists in database. Skipping...');
                    $existingEmailsCount++;
                }
            }
    
            \Log::info('Emails saved to database.');
    
            session()->flash('success', "Emails fetched successfully. New emails: $newEmailsCount, Existing emails: $existingEmailsCount");
    
            return redirect()->route('vendor.emailtoweb.read.list_view');
    
        } catch (\Webklex\PHPIMAP\Exceptions\ConnectionFailedException $e) {
            \Log::error("IMAP Connection Failed: " . $e->getMessage());
            session()->flash('error', 'IMAP connection failed.');
            return redirect()->back();
        } catch (\Exception $e) {
            \Log::error("An error occurred: " . $e->getMessage());
            session()->flash('error', 'An error occurred.');
            return redirect()->back();
        }
    }
    


    // public function fetchEmailsWithCredentials(Request $request)
    // {
    //     try {
    //         // Validate and get username and password from the request
    //         $credentials = $request->validate([
    //             'username' => 'required|string|email',
    //             'password' => 'required|string',
    //         ]);

    //         \Log::info('Attempting to connect to IMAP server...');

    //         // Create an instance of ClientManager
    //         $clientManager = new ClientManager();

    //         // Attempt to connect to IMAP server
    //         $client = $clientManager->make([
    //             'host'          => 'irriion.com',
    //             'port'          => 993,
    //             'encryption'    => 'ssl',
    //             'validate_cert' => true,
    //             'username'      => $credentials['username'],
    //             'password'      => $credentials['password'],
    //             'protocol'      => 'imap',
    //         ]);

    //         \Log::info('Attempting to connect...');

    //         $client->connect(); // Connect to the IMAP server

    //         \Log::info('Connected to IMAP server.');

    //         $inbox = $client->getFolder('INBOX'); // Get the INBOX folder
    //         $messages = $inbox->messages()->all()->get(); // Fetch all messages

    //         \Log::info('Fetched messages from INBOX.');

    //         foreach ($messages as $message) {
    //             $email = new EmailToWeb();

    //             $email->vendors__id = 1;
                
    //             // Process From email address
    //             $from = $message->getFrom();
    //             $email->from_email = $from[0]->mail;

    //             // Process To email address
    //             $to = $message->getTo();
    //             $email->to_email = $to[0]->mail;

    //             $email->subject = $message->getSubject();
    //             $email->body = $message->getTextBody(); // Example: Get text body of email

    //             // Handle attachments (example for PDF)
    //             $attachments = $message->getAttachments();
    //             foreach ($attachments as $attachment) {
    //                 if ($attachment->getMimeType() === 'application/pdf') {
    //                     try {
    //                         // Save PDF to storage and store path in database
    //                         $path = $attachment->save(storage_path('app/public/attachments'));
            
    //                         // Verify and log the saved path
    //                         \Log::info('PDF saved to: ' . $path);
            
    //                         // Store the path in the database field
    //                         $email->pdf_attachment = $path;
    //                     } catch (\Exception $e) {
    //                         \Log::error('Error saving PDF attachment: ' . $e->getMessage());
    //                         // Handle error as needed
    //                     }
    //                 }
    //                 // Add logic for other attachment types if needed
    //             }

    //             // Save received date
    //             $email->received_at = $message->getDate();

    //             $email->save();
    //         }

    //         \Log::info('Emails saved to database.');

    //         session()->flash('success', 'Emails fetched and saved successfully.');

    //         return redirect()->route('vendor.emailtoweb.read.list_view');

    //     } catch (\Webklex\PHPIMAP\Exceptions\ConnectionFailedException $e) {
    //         \Log::error("IMAP Connection Failed: " . $e->getMessage());
    //         session()->flash('error', 'IMAP connection failed.');
    //         return redirect()->back();
    //     } catch (\Exception $e) {
    //         \Log::error("An error occurred: " . $e->getMessage());
    //         session()->flash('error', 'An error occurred.');
    //         return redirect()->back();
    //     }
    // }

    /**
     * list of Contact
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function prepareEmailToWebList($groupUid = null)
    {
        validateVendorAccess('manage_contacts');
        // respond with dataTables preparations
        return $this->emailToWebEngine->prepareEmailToWebDataTableSource($groupUid);
    }


    public function EmailToWebData($emailIdOrUid)
    {
        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        $processReaction = $this->emailToWebEngine->prepareEmailToWebUpdateData($emailIdOrUid);
        // dd($processReaction);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    public function processEmailToWebDelete($emailIdOrUid, BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        $processReaction = $this->emailToWebEngine->processEmailToWebDelete($emailIdOrUid);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    public function selectedEmailToWebDelete(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');

        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        $request->validate([
            'selected_emailtoweb' => 'required|array'
        ]);
        // ask engine to process the request
        $processReaction = $this->emailToWebEngine->processSelectedEmailToWebDelete($request);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
    
}
