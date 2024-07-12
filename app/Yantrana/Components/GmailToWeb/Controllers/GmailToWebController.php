<?php

namespace App\Yantrana\Components\GmailToWeb\Controllers;

// use PhpImap\Mailbox as ImapMailbox;
// use PhpImap\IncomingMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\GmailToWebLogin;
use App\Models\GmailToWeb;
use App\Yantrana\Base\BaseController;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\AuthenticationFailedException;
use App\Yantrana\Components\GmailToWeb\GmailToWebEngine;

class GmailToWebController extends BaseController
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

    /**
     * Show the credentials form view.
     *
     * @return \Illuminate\View\View
     */

    public function showSMSView($groupUid = null)
    {
        validateVendorAccess('manage_contacts');
        $contactsRequiredEngineResponse = $this->gmailToWebEngine->prepareContactRequiredData($groupUid);

        // load the view
        return $this->loadView('sms.sms', $contactsRequiredEngineResponse->data());
    }

    public function showGmailToWebView($groupUid = null)
    {
        validateVendorAccess('manage_contacts');
        $contactsRequiredEngineResponse = $this->gmailToWebEngine->prepareContactRequiredData($groupUid);

        // load the view
        return $this->loadView('gmail-to-web.list', $contactsRequiredEngineResponse->data());
    }

    public function showCredentialsForm()
    {
        return view('gmail-to-web.gmailsettings');
    }

    /**
     * Fetch gmails from Gmail using provided credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */


    public function fetchGmailsWithCredentials(Request $request)
    {
        try {
            $credentials = $request->validate([
                'username' => 'required|string|email',
                'password' => 'required|string',
            ]);

            $host = 'imap.gmail.com';
            $port = '993';

            Log::info('Attempting to connect to imap server...');
            Log::info('Username: ' . $credentials['username']);

            GmailToWebLogin::updateOrCreate(
                ['username' => $credentials['username']],
                [
                    'password' => encrypt($credentials['password']), // Store encrypted password
                    'host' => $host,
                    'port' => $port,
                ]
            );

            $clientManager = new ClientManager();

            $client = $clientManager->make([
                'host' => $host,
                'port' => $port,
                'encryption' => 'ssl',
                'validate_cert' => true,
                'username' => $credentials['username'],
                'password' => $credentials['password'],
                'protocol' => 'imap',
            ]);

            $client->connect(); 

            Log::info('Connected to imap server.');

            $inbox = $client->getFolder('INBOX'); // Get the INBOX folder
            $messages = $inbox->messages()->all()->get(); // Fetch all messages

            Log::info('Fetched messages from INBOX.');

            $newGmailsCount = 0;
            $existingGmailsCount = 0;

            foreach ($messages as $message) {
                $receivedDate = $message->getDate();
                $from = $message->getFrom();
                $fromEmail = $from[0]->mail;

                // Check if email already exists in the database
                $existingGmail = GmailToWeb::where('received_at', $receivedDate)
                    ->where('from_email', $fromEmail)
                    ->first();

                if (!$existingGmail) {
                    $email = new GmailToWeb();

                    $email->vendors__id = 1;

                    // Process From email address
                    $email->from_email = $fromEmail;

                    // Process To email address
                    $to = $message->getTo();
                    $email->to_email = $to[0]->mail;

                    $email->subject = $message->getSubject();
                    $email->body = $message->getTextBody(); // Example: Get text body of email

                    // Initialize attachment details
                    $attachmentsData = [];

                    // Handle attachments
                    $attachments = $message->getAttachments();
                    foreach ($attachments as $attachment) {
                        // Save different types of attachments
                        switch ($attachment->getMimeType()) {
                            case 'application/pdf':
                                $pdfPath = $attachment->save(storage_path('app/public/attachments'));
                                $attachmentsData[] = [
                                    'type' => 'pdf',
                                    'original_name' => $attachment->getName(),
                                    'path' => $pdfPath,
                                    'url' => asset('storage/attachments/' . basename($pdfPath)), // Store PDF URL in array
                                ];
                                break;
                            case 'image/jpeg':
                            case 'image/png':
                                $imagePath = $attachment->save(storage_path('app/public/images'));
                                $attachmentsData[] = [
                                    'type' => 'image',
                                    'original_name' => $attachment->getName(),
                                    'path' => $imagePath,
                                    'url' => asset('storage/images/' . basename($imagePath)), // Store image URL in array
                                ];
                                break;
                            case 'application/msword':
                            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                                $docPath = $attachment->save(storage_path('app/public/documents'));
                                $attachmentsData[] = [
                                    'type' => 'document',
                                    'original_name' => $attachment->getName(),
                                    'path' => $docPath,
                                    'url' => asset('storage/documents/' . basename($docPath)), // Store document URL in array
                                ];
                                break;
                            default:
                                // Log unsupported MIME types
                                Log::warning("Unsupported attachment type: " . $attachment->getMimeType());
                                // Handle other types or skip
                                break;
                        }
                    }

                    // Save attachment details to database
                    $email->attachments = json_encode($attachmentsData); // Store attachments data as JSON

                    // Save received date
                    $email->received_at = $receivedDate;

                    $email->save();
                    $newGmailsCount++;
                } else {
                    Log::info('Gmail already exists in database. Skipping...');
                    $existingGmailsCount++;
                }
            }

            Log::info('Gmails saved to database.');

            session()->flash('success', "Gmails fetched successfully. New gmails: $newGmailsCount, Existing gmails: $existingGmailsCount");

            return redirect()->route('vendor.gmailtoweb.read.list_view');

        } catch (ConnectionFailedException $e) {
            Log::error('Connection failed: ' . $e->getMessage());
            session()->flash('error', 'Connection failed: ' . $e->getMessage());
        } catch (AuthenticationFailedException $e) {
            Log::error('Authentication failed: ' . $e->getMessage());
            session()->flash('error', 'Authentication failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function prepareGmailToWebList($groupUid = null)
    {
        validateVendorAccess('manage_contacts');
        // respond with dataTables preparations
        return $this->gmailToWebEngine->prepareGmailToWebDataTableSource($groupUid);
    }

    public function GmailToWebData($gmailIdOrUid)
    {
        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        $processReaction = $this->gmailToWebEngine->prepareGmailToWebUpdateData($gmailIdOrUid);
        // dd($processReaction);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    public function processGmailToWebDelete($gmailIdOrUid, BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        $processReaction = $this->gmailToWebEngine->processGmailToWebDelete($gmailIdOrUid);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    public function selectedGmailToWebDelete(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');

        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        $request->validate([
            'selected_gmailtoweb' => 'required|array'
        ]);
        // ask engine to process the request
        $processReaction = $this->gmailToWebEngine->processSelectedGmailToWebDelete($request);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

}
