<?php
/**
* CampaignController.php - Controller file
*
* This file is part of the Campaign component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\AutoCampaign\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequest;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request; 
// use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Yantrana\Components\AutoCampaign\AutoCampaignEngine;

class AutoCampaignController extends BaseController
{
    /**
     * @var AutoCampaignEngine - Campaign Engine
     */
    protected $autoCampaignEngine;

    /**
     * Constructor
     *
     * @param  AutoCampaignEngine  $autoCampaignEngine  - Campaign Engine
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(AutoCampaignEngine $autoCampaignEngine)
    {
        $this->autoCampaignEngine = $autoCampaignEngine;
    }

    /**
     * list of Campaign
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function showAutoCampaignView()
    {
        // load the view
        return $this->loadView('autocampaign.list');
    }

    /**
     * Campaign process delete
     *
     * @param  mix  $campaignUid
     * @return json object
     *---------------------------------------------------------------- */
    public function campaignStatusData($campaignUid, BaseRequest $request)
    {
        // ask engine to process the request
        $processReaction = $this->campaignEngine->prepareCampaignData($campaignUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * list of Campaign
     *
     * @return json object
     *---------------------------------------------------------------- */


    public function getNextCampaignId()
    {
        $lastCampaign = DB::table('whatsapp_message_logs')
                        ->orderBy('campaigns__id', 'desc')
                        ->first();
    
        $nextCampaignId = $lastCampaign ? $lastCampaign->campaigns__id + 1 : 1;
    
        return response()->json(['nextCampaignId' => $nextCampaignId]);
    }
    
    public function fetchAndSend(Request $request)
{
    try {
        $campaigns__id = $request->query('campaigns__id', 1); // Default to 1 if not provided

        if (!$this->campaignExists($campaigns__id)) {
            \Log::error('Campaign not found', ['campaigns__id' => $campaigns__id]);
            return response()->json(['error' => 'Campaign not found'], 404);
        }

        $response = Http::get('https://www.tcsion.com/iONBizServices/iONWebService?u=o3p%2FoROBrcGCHbD9jePhCRVXbGP7C13mQfdEjeiA7iJzhP0UqDRTdNazobyhKGIZ&apiKey=tV1gwVjXJkx4mfNyyXHlwA%3D%3D&servicekey=zbMGm2LerdEvF8kg2MzJIg%3D%3D&OverdueDays=1');

        if ($response->successful()) {
            // Check if the response is JSON
            if ($this->isJson($response->body())) {
                $data = $response->json();

                foreach ($data as $entry) {
                    \Log::info('Fetched entry:', $entry);
                    $this->sendTemplateMessage($entry, $campaigns__id); // Pass campaigns__id here
                }

                return response()->json(['message' => 'Data processed successfully']);
            } else {
                \Log::error('Invalid response format', ['response' => $response->body()]);
                return response()->json(['error' => 'Invalid response format, expected JSON'], 400);
            }
        } else {
            \Log::error('Failed to fetch data', ['details' => $response->body()]);
            return response()->json(['error' => 'Failed to fetch data', 'details' => $response->body()], $response->status());
        }
    } catch (\Exception $e) {
        \Log::error('Exception occurred while fetching data', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to fetch and send data', 'details' => $e->getMessage()], 500);
    }
}

   
    // public function fetchAndSend(Request $request)
    // {
    //     try {
    //         $campaigns__id = $request->query('campaigns__id', 1); // Default to 1 if not provided

    //         if (!$this->campaignExists($campaigns__id)) {
    //             \Log::error('Campaign not found', ['campaigns__id' => $campaigns__id]);
    //             return response()->json(['error' => 'Campaign not found'], 404);
    //         }

    //         $response = Http::get('https://www.tcsion.com/iONBizServices/iONWebService?u=o3p%2FoROBrcGCHbD9jePhCRVXbGP7C13mQfdEjeiA7iJzhP0UqDRTdNazobyhKGIZ&apiKey=tV1gwVjXJkx4mfNyyXHlwA%3D%3D&servicekey=zbMGm2LerdEvF8kg2MzJIg%3D%3D&OverdueDays=1');

    //         if ($response->successful()) {
    //             $data = $response->json();

    //             foreach ($data as $entry) {
    //                 \Log::info('Fetched entry:', $entry);
    //                 $this->sendTemplateMessage($entry, $campaigns__id); // Pass campaigns__id here
    //             }

    //             return response()->json(['message' => 'Data processed successfully']);
    //         } else {
    //             \Log::error('Failed to fetch data', ['details' => $response->body()]);
    //             return response()->json(['error' => 'Failed to fetch data', 'details' => $response->body()], $response->status());
    //         }
    //     } catch (\Exception $e) {
    //         \Log::error('Exception occurred while fetching data', ['error' => $e->getMessage()]);
    //         return response()->json(['error' => 'Failed to fetch and send data', 'details' => $e->getMessage()], 500);
    //     }
    // }

    private function campaignExists($campaigns__id)
    {
        try {
            // Assuming the column name is 'campaigns__id' in the 'campaigns' table
            return DB::table('campaigns')->where('_id', $campaigns__id)->exists();
        } catch (\Exception $e) {
            \Log::error('Exception occurred while checking campaign existence', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    private function sendTemplateMessage($entry, $campaigns__id)
    {
        try {
            $client = new Client();
    
            $data = [
                'from_phone_number_id' => '',
                // 'phone_number' => $entry['phone'],
                'phone_number' => '919156526284',
                'template_name' => 'paymentreminder_1',
                'template_language' => 'en',
                'field_1' => $entry['Party_Description'],
                'field_2' => $entry['Supplier_Invoice_No/_Customer_PO_No'],
                'field_3' => $entry['Due_Amount_in_Domestic_Currency'],
                'field_4' => $entry['Supplier_Invoice_Date/_Customer_PO_Date'],
                // 'field_5' => $entry['pdf'],
                'contact' => [
                    'first_name' => explode(' ', $entry['Party_Description'])[0],
                    'last_name' => explode(' ', $entry['Party_Description'])[1] ?? '',
                    'email' => 'example@example.com',
                    'country' => 'India',
                    'language_code' => 'en',
                ],
                'campaigns__id' => $campaigns__id // Use the provided campaigns__id
            ];
    
            \Log::info('Sending message with data:', $data);
    
            $response = $client->post(route('api.vendor.chat_template_queue_message.send.process', ['vendorUid' => '12839dbb-afca-4a17-8257-84dd473e4738']), [
                'json' => $data,
                'headers' => [
                    'Authorization' => 'Bearer 5O0ujTkLN64sevAJSRIl7x9DwExiWwuh8Iu3YkeZAiQCFtRKwcsy7p0KOPKlUEv9',
                    'Accept' => 'application/json',
                ],
            ]);
    
            $responseBody = json_decode($response->getBody()->getContents(), true);
            $statusCode = $response->getStatusCode();
    
            if ($statusCode == 200) {
                \Log::info('Message sent successfully for entry:', $entry);
            } else {
                \Log::error('Failed to send WhatsApp message', [
                    'status' => $statusCode,
                    'response' => $responseBody,
                    'entry' => $entry
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Exception occurred while sending WhatsApp message', [
                'error' => $e->getMessage(),
                'entry' => $entry
            ]);
        }
    }
     














    // public function getNextCampaignId()
    // {
    //     $lastCampaign = DB::table('whatsapp_message_logs')
    //                       ->orderBy('campaigns__id', 'desc')
    //                       ->first();

    //     $nextCampaignId = $lastCampaign ? $lastCampaign->campaigns__id + 1 : 1;

    //     return response()->json(['nextCampaignId' => $nextCampaignId]);
    // }

    
    // public function fetchAndSend(Request $request)
    // {
    //     try {
    //         $campaigns__id = $request->query('campaigns__id', 1); // Default to 1 if not provided

    //         $response = Http::get('https://irriion.com/data.json');

    //         if ($response->successful()) {
    //             $data = $response->json();

    //             foreach ($data as $entry) {
    //                 \Log::info('Fetched entry:', $entry);
    //                 $this->sendTemplateMessage($entry, $campaigns__id); // Pass campaigns__id here
    //             }

    //             return response()->json(['message' => 'Data processed successfully']);
    //         } else {
    //             \Log::error('Failed to fetch data', ['details' => $response->body()]);
    //             return response()->json(['error' => 'Failed to fetch data', 'details' => $response->body()], $response->status());
    //         }
    //     } catch (\Exception $e) {
    //         \Log::error('Exception occurred while fetching data', ['error' => $e->getMessage()]);
    //         return response()->json(['error' => 'Failed to fetch and send data', 'details' => $e->getMessage()], 500);
    //     }
    // }

    // private function sendTemplateMessage($entry, $campaigns__id)
    // {
    //     try {
    //         $client = new Client();

    //         $data = [
    //             'from_phone_number_id' => '',
    //             'phone_number' => $entry['phone'],
    //             'template_name' => 'paymentreminder_1',
    //             'template_language' => 'en',
    //             'field_1' => $entry['name'],
    //             'field_2' => $entry['invoice_number'],
    //             'field_3' => $entry['amount'],
    //             'field_4' => $entry['date'],
    //             'field_5' => $entry['pdf'],
    //             'contact' => [
    //                 'first_name' => explode(' ', $entry['name'])[0],
    //                 'last_name' => explode(' ', $entry['name'])[1] ?? '',
    //                 'email' => 'example@example.com',
    //                 'country' => 'India',
    //                 'language_code' => 'en',
    //             ],
    //             'campaigns__id' => $campaigns__id // Use the provided campaigns__id
    //         ];

    //         \Log::info('Sending message with data:', $data);

    //         $response = $client->post(route('api.vendor.chat_template_queue_message.send.process', ['vendorUid' => '12839dbb-afca-4a17-8257-84dd473e4738']), [
    //             'json' => $data,
    //             'headers' => [
    //                 'Authorization' => 'Bearer 5O0ujTkLN64sevAJSRIl7x9DwExiWwuh8Iu3YkeZAiQCFtRKwcsy7p0KOPKlUEv9',
    //                 'Accept' => 'application/json',
    //             ],
    //         ]);

    //         $responseBody = json_decode($response->getBody()->getContents(), true);
    //         $statusCode = $response->getStatusCode();

    //         if ($statusCode == 200) {
    //             \Log::info('Message sent successfully for entry:', $entry);
    //         } else {
    //             \Log::error('Failed to send WhatsApp message', [
    //                 'status' => $statusCode,
    //                 'response' => $responseBody,
    //                 'entry' => $entry
    //             ]);
    //         }
    //     } catch (\Exception $e) {
    //         \Log::error('Exception occurred while sending WhatsApp message', [
    //             'error' => $e->getMessage(),
    //             'entry' => $entry
    //         ]);
    //     }
    // }
    
    public function prepareAutoCampaignList($status)
    {
        // respond with dataTables preparations
        return $this->autoCampaignEngine->prepareAutoCampaignDataTableSource($status);
    }

    /**
     * Campaign process delete
     *
     * @param  mix  $campaignIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processCampaignDelete($campaignIdOrUid, BaseRequest $request)
    {
        // ask engine to process the request
        $processReaction = $this->campaignEngine->processCampaignDelete($campaignIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
     /**
     * Campaign process archive
     *
     * @param  mix  $campaignIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processCampaignArchive($campaignIdOrUid, BaseRequest $request)
    {
        // ask engine to process the request
        $processReaction = $this->campaignEngine->processCampaignArchive($campaignIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
     /**
     * Campaign process unarchive
     *
     * @param  mix  $campaignIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processCampaignUnarchive($campaignIdOrUid, BaseRequest $request)
    {
        // ask engine to process the request
        $processReaction = $this->campaignEngine->processCampaignUnarchive($campaignIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Campaign get update data
     *
     * @param  mix  $campaignIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function updateCampaignData($campaignIdOrUid)
    {
        $processReaction = $this->campaignEngine->prepareCampaignUpdateData($campaignIdOrUid);

        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }
     /**
     * Campaign get status view
     *
     * @param  mix  $campaignIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
     public function AutocampaignStatusView($campaignUid,$pageType = null)
    {
        $campaignDataResponse = $this->autoCampaignEngine->prepareCampaignData($campaignUid);

        $campaignDataResponse->updateData(
            'pageType', (((!$pageType or ($pageType == 'executed')) or (!$pageType and $campaignDataResponse->data('campaignStatus')) == 'executed')) ? 'executed' : 'queue'
        );
        return $this->loadView('whatsapp.autocampaign-status', $campaignDataResponse->data());
    }
    
    /**
      * list of campaign queue log
      *
      * @return  json object
      *---------------------------------------------------------------- */

      public function campaignQueueLogListView($campaignIdOrUid)
      {
          // respond with dataTables preparations
          return $this->campaignEngine->prepareCampaignQueueLogList($campaignIdOrUid);
      }

        /**
      * list of executed queue log
      *
      * @return  json object
      *---------------------------------------------------------------- */

      public function campaignExecutedLogListView($campaignIdOrUid)
      {
          // respond with dataTables preparations
          return $this->campaignEngine->prepareCampaignExecutedLogList($campaignIdOrUid);
      }


}
