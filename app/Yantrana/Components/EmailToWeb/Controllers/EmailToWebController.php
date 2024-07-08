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
        $credentials = $request->only(['username', 'password']);

        $clientManager = new ClientManager();

        $client = $clientManager->make([
            'host'          => 'mail.irriion.com',
            'port'          => 143,
            'encryption'    => null, // Try 'tls', 'ssl', or null
            'validate_cert' => false,
            'username'      => $credentials['username'],
            'password'      => $credentials['password'],
            'protocol'      => 'imap'
        ]);

        try {
            $client->connect();
            $inbox = $client->getFolder('INBOX');
            $messages = $inbox->messages()->all()->get();

            return view('email-to-web.emaillist', compact('messages'));
        } catch (\Webklex\PHPIMAP\Exceptions\ConnectionFailedException $e) {
            // Log the error message
            \Log::error("IMAP Connection Failed: " . $e->getMessage());

            return response()->json(['error' => 'IMAP connection failed.'], 500);
        } catch (\Exception $e) {
            // Log any other errors
            \Log::error("An error occurred: " . $e->getMessage());

            return response()->json(['error' => 'An error occurred.'], 500);
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
