<?php
/**
* ContactController.php - Controller file
*
* This file is part of the Contact component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\EmailToWeb\Controllers;

use Illuminate\Http\Request;
use Webklex\PHPIMAP\ClientManager;
use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseRequest;
use Illuminate\Support\Facades\Gate;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequestTwo;
use Illuminate\Database\Query\Builder;
use App\Yantrana\Components\Contact\ContactEngine;

class EmailToWebController extends BaseController
{
    /**
     * @var ContactEngine - Contact Engine
     */
    protected $contactEngine;

    /**
     * Constructor
     *
     * @param  ContactEngine  $contactEngine  - Contact Engine
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(ContactEngine $contactEngine)
    {
        $this->contactEngine = $contactEngine;
    }

    /**
     * list of Contact
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function showEmailToWebView($groupUid = null)
    {
        validateVendorAccess('manage_contacts');
        $contactsRequiredEngineResponse = $this->contactEngine->prepareContactRequiredData($groupUid);

        // load the view
        return $this->loadView('email-to-web.list', $contactsRequiredEngineResponse->data());
    }

    public function showCredentialsForm()
    {
        return view('email-to-web.emailsettings');
    }


    public function fetchEmailsWithCredentials(Request $request)
{
    // Validate and get username and password from the request
    $credentials = $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    // Create an instance of ClientManager
    $clientManager = new ClientManager();

    // Attempt to connect to IMAP server
    try {
        $client = $clientManager->make([
            'host'          => 'mail.irriion.com',
            'port'          => 143,
            'encryption'    => null, // Try 'tls', 'ssl', or null based on server requirements
            'validate_cert' => false, // Temporary disable certificate validation (not recommended for production)
            'username'      => $credentials['username'],
            'password'      => $credentials['password'],
            'protocol'      => 'imap',
        ]);

        $client->connect(); // Connect to the IMAP server

        $inbox = $client->getFolder('INBOX'); // Get the INBOX folder
        $messages = $inbox->messages()->all()->get(); // Fetch all messages

        // Return the view with fetched messages
        return view('email-to-web.emaillist', compact('messages'));
    } catch (\Webklex\PHPIMAP\Exceptions\ConnectionFailedException $e) {
        \Log::error("IMAP Connection Failed: " . $e->getMessage()); // Log connection error
        return response()->json(['error' => 'IMAP connection failed.'], 500); // Return error response
    } catch (\Exception $e) {
        \Log::error("An error occurred: " . $e->getMessage()); // Log other errors
        return response()->json(['error' => 'An error occurred.'], 500); // Return generic error response
    }
}


    /**
     * list of Contact
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function prepareContactList($groupUid = null)
    {
        validateVendorAccess('manage_contacts');
        // respond with dataTables preparations
        return $this->contactEngine->prepareContactDataTableSource($groupUid);
    }
    
}
