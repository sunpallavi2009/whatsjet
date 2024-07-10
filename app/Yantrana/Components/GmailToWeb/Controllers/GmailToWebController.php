<?php

namespace App\Yantrana\Components\GmailToWeb\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\GmailToWebLogin;
use App\Yantrana\Base\BaseController;
use Webklex\PHPIMAP\ClientManager;

class GmailToWebController extends BaseController
{
    /**
     * Show the credentials form view.
     *
     * @return \Illuminate\View\View
     */
    public function showCredentialsForm()
    {
        return view('gmail-to-web.gmailsettings');
    }

    /**
     * Fetch emails from Gmail using provided credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function fetchGmailsWithCredentials(Request $request)
    {
        try {
            // Validate and get username and password from the request
            $credentials = $request->validate([
                'username' => 'required|string|email',
                'password' => 'required|string',
            ]);

            // Gmail POP3 settings
            $host = 'pop.gmail.com';
            $port = '995'; // Gmail POP3 port

            // Save the credentials to the gmail_to_web_login table if they are new
            GmailToWebLogin::updateOrCreate(
                ['username' => $credentials['username']],
                [
                    'password' => $credentials['password'],
                    'host' => $host,
                    'port' => $port,
                ]
            );

            // Create an instance of ClientManager for POP3
            $clientManager = new ClientManager();

            // Attempt to connect to POP3 server
            $client = $clientManager->make([
                'host' => $host,
                'port' => $port,
                'encryption' => 'ssl', // Ensure SSL/TLS encryption
                'validate_cert' => true, // Validate SSL certificate
                'username' => $credentials['username'],
                'password' => $credentials['password'],
                'protocol' => 'pop3',
            ]);

            Log::info('Attempting to connect to POP3 server...');

            // Connect to the POP3 server
            if ($client->connect()) {
                Log::info('Connected to POP3 server.');

                // Fetch emails from the server
                $messages = $client->getFolder('INBOX')->messages()->all()->get();

                foreach ($messages as $message) {
                    Log::info('Email fetched: ' . $message->getSubject());
                    // Process email here as needed
                }

                Log::info('Emails fetched successfully.');
                session()->flash('success', "Emails fetched successfully.");
            } else {
                Log::error('Connection to POP3 server failed.');
                session()->flash('error', 'Connection to POP3 server failed.');
            }

            return redirect()->route('vendor.gmailtoweb.read.list_view');

        } catch (\Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
            return redirect()->back();
        }
    }
}
