<?php
/**
 * CampaignEngine.php - Main component file
 *
 * This file is part of the Campaign component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\AutoCampaign;

use App\Yantrana\Base\BaseEngine;
use App\Yantrana\Components\Campaign\Interfaces\CampaignEngineInterface;
use App\Yantrana\Components\AutoCampaign\Repositories\AutoCampaignRepository;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;

class AutoCampaignEngine extends BaseEngine implements CampaignEngineInterface
{
    /**
     * @var AutoCampaignRepository - Campaign Repository
     */
    protected $AutoCampaignRepository;

    /**
     * Constructor
     *
     * @param  AutoCampaignRepository  $AutoCampaignRepository  - Campaign Repository
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(AutoCampaignRepository $AutoCampaignRepository)
    {
        $this->AutoCampaignRepository = $AutoCampaignRepository;
    }

    /**
     * Campaign datatable source
     *
     * @return array
     *---------------------------------------------------------------- */
    public function prepareAutoCampaignDataTableSource($status)
    {
        $campaignCollection = $this->AutoCampaignRepository->fetchAutoCampaignDataTableSource($status);
        $timeNow = now();
        // required columns for DataTables
        $requireColumns = [
            '_id',
            '_uid',
            'title',
            'template_name',
            'template_language',
            'created_at' => function ($rowData) {
                return formatDateTime($rowData['created_at']);
            },
            'scheduled_at' => function ($rowData) {
                return (!$rowData['scheduled_at'] or ($rowData['scheduled_at'] != $rowData['created_at'])) ? '<span>📅 </span>' . formatDateTime($rowData['scheduled_at']) : '<span title="' . __tr('Instant') . '">⚡ </span>' . formatDateTime($rowData['scheduled_at']);
            },
            'status',
            'scheduled_status' => function ($rowData) use (&$timeNow) {
                $statusText = __tr('Upcoming');
                if(Carbon::parse($rowData['scheduled_at']) < $timeNow) {
                    $statusText = __tr('Awaiting Execution');
                    if(($rowData['queue_pending_messages_count'] or $rowData['queue_processing_messages_count']) and $rowData['message_log_count']) {
                        $statusText = __tr('Processing');
                    } elseif(!$rowData['queue_pending_messages_count'] and !$rowData['queue_processing_messages_count']) {
                        $statusText = __tr('Executed');
                    } elseif(!$rowData['queue_pending_messages_count'] and !$rowData['message_log_count']) {
                        $statusText = __tr('NA');
                    }
                }
                return $statusText;
            },
            'delete_allowed' => function ($rowData) use (&$timeNow) {
                return (Carbon::parse($rowData['scheduled_at']) > $timeNow);
            },
        ];

        // prepare data for the DataTables
        return $this->dataTableResponse($campaignCollection, $requireColumns);
    }

    /**
     * Campaign delete process
     *
     * @param  mix  $campaignIdOrUid
     * @return array
     *---------------------------------------------------------------- */
    public function processCampaignDelete($campaignIdOrUid)
    {
        // fetch the record
        $campaign = $this->AutoCampaignRepository->fetchIt($campaignIdOrUid);
        // check if the record found
        if (__isEmpty($campaign)) {
            // if not found
            return $this->engineResponse(18, null, __tr('Campaign not found'));
        }
        // older campaigns can not be deleted
        if ($campaign->messageLog()->count()) {
            return $this->engineResponse(18, null, __tr('Executed Campaign can not be deleted'));
        }
        // ask to delete the record
        if ($this->AutoCampaignRepository->deleteIt($campaign)) {
            // if successful
            return $this->engineSuccessResponse([], __tr('Campaign deleted successfully'));
        }

        // if failed to delete
        return $this->engineFailedResponse([], __tr('Failed to delete Campaign'));
    }
     /**
     * Campaign archive process
     *
     * @param  mix  $campaignIdOrUid
     * @return array
     *---------------------------------------------------------------- */
    public function processCampaignArchive($campaignIdOrUid)
    {
        // fetch the record
        $campaign = $this->AutoCampaignRepository->fetchIt($campaignIdOrUid);
        // check if the record found
        if (__isEmpty($campaign)) {
            // if not found
            return $this->engineResponse(18, null, __tr('AutoCampaign not found'));
        }
        // Prepare Update Package data
        $updateData = [
            'status' => 5,
        ];
        //Check if package archive
        if ($this->AutoCampaignRepository->updateIt($campaign,$updateData)) {
            return $this->engineSuccessResponse([], __tr('AutoCampaign Archived successfully'));
        }

        // if failed to archive
        return $this->engineFailedResponse([], __tr('Failed to Archive AutoCampaign'));
    }
     /**
     * Campaign unarchive process
     *
     * @param  mix  $campaignIdOrUid
     * @return array
     *---------------------------------------------------------------- */
    public function processCampaignUnarchive($campaignIdOrUid)
    {
        // fetch the record
        $campaign = $this->AutoCampaignRepository->fetchIt($campaignIdOrUid);
        // check if the record found
        if (__isEmpty($campaign)) {
            // if not found
            return $this->engineResponse(18, null, __tr('Campaign not found'));
        }
        // Prepare Update Package data
        $updateData = [
            'status' => 1,
        ];
        //Check if package archive
        if ($this->AutoCampaignRepository->updateIt($campaign,$updateData)) {
            return $this->engineSuccessResponse([], __tr('Campaign Unarchived successfully'));
        }
       

        // if failed to archive
        return $this->engineFailedResponse([], __tr('Failed to Unarchive Campaign'));
    }

    /**
     * Campaign prepare update data
     *
     * @param  mix  $campaignIdOrUid
     * @return object
     *---------------------------------------------------------------- */
    public function prepareCampaignUpdateData($campaignIdOrUid)
    {
        // data fetch request
        $campaign = $this->AutoCampaignRepository->fetchIt($campaignIdOrUid);
        // check if record found
        if (__isEmpty($campaign)) {
            // if record not found
            return $this->engineResponse(18, null, __tr('Campaign not found.'));
        }

        // if record found
        return $this->engineSuccessResponse($campaign->toArray());
    }

    /**
     * Campaign prepare update data
     *
     * @param  mix  $campaignIdOrUid
     * @return object
     *---------------------------------------------------------------- */
    public function prepareCampaignData($campaignIdOrUid)
    {
        // data fetch request
        // $campaign = $this->campaignRepository->with(['messageLog', 'queueMessages'])->fetchIt($campaignIdOrUid);
        $campaign = $this->AutoCampaignRepository->getCampaignData($campaignIdOrUid);
        // if record found
        abortIf(__isEmpty($campaign));
        $rawTime = Carbon::parse($campaign->scheduled_at, 'UTC');
        $scheduleAt = $rawTime->setTimezone($campaign->timezone);
        $campaign->scheduled_at_by_timezone = $scheduleAt;
        $statusText = __tr('Upcoming');
        $campaignStatus = 'upcoming';
        $queueFailedCount = 0;
        $timeNow = now();
        if(Carbon::parse($campaign->scheduled_at) < $timeNow) {
            $statusText = __tr('Awaiting Execution');
            if(($campaign->queue_pending_messages_count or $campaign->queue_processing_messages_count) and $campaign->message_log_count) {
                $statusText = __tr('Processing');
                $campaignStatus = 'processing';
            } elseif(!$campaign->queue_pending_messages_count and !$campaign->queue_processing_messages_count) {
                $statusText = __tr('Executed');
                $campaignStatus = 'executed';
            } elseif(!$campaign->queue_pending_messages_count and !$campaign->message_log_count) {
                $statusText = __tr('NA');
                $campaignStatus = 'na';
            }
        }
        if (Request::ajax() === true) {
            $messageLog = $campaign->messageLog;
            $queueMessages = $campaign->queueMessages;
            $campaignData = $campaign->__data;
            $totalContacts = (int) Arr::get($campaignData, 'total_contacts');
            $totalRead = $messageLog->where('status', 'read')->count();
            $totalReadInPercent = round($totalRead / $totalContacts * 100, 2) . '%';
            $totalDelivered = $messageLog->where('status', 'delivered')->count() + $totalRead;
            $totalDeliveredInPercent = round($totalDelivered / $totalContacts * 100, 2) . '%';
            $queueFailedCount = $queueMessages->where('status', 2)->count();
            $totalFailed = $queueFailedCount + $messageLog->where('status', 'failed')->count();
            $totalFailedInPercent = round($totalFailed / $totalContacts * 100, 2) . '%';

            updateClientModels([
                'totalDelivered' => $totalDelivered,
                'totalDeliveredInPercent' => __tr($totalDeliveredInPercent),
                'totalRead' => $totalRead,
                'totalReadInPercent' => __tr($totalReadInPercent),
                'totalFailed' => $totalFailed,
                'statusText' => $statusText,
                'campaignStatus' => $campaignStatus,
                'queueFailedCount' => $queueFailedCount,
                'totalFailedInPercent' => __tr($totalFailedInPercent),
                'executedCount' => $campaign->messageLog->count() ?? 0,
                'inQueuedCount' => $campaign->queueMessages->where('status', 1)->count() ?? 0,
            ]);
        }
        // if record found
        return $this->engineSuccessResponse([
            'campaign' => $campaign,
            'statusText' => $statusText,
            'campaignStatus' => $campaignStatus,
            'queueFailedCount' => $queueFailedCount,
        ]);
    }
    /**
     * Campaign prepare queue log data
     *
     * @param  mix  $campaignIdOrUid
     * @return object
     *---------------------------------------------------------------- */
    public function prepareCampaignQueueLogList($campaignIdOrUid)
    {
        // data fetch request
        $campaign = $this->AutoCampaignRepository->fetchIt($campaignIdOrUid);
        // data fetch request
        $campaignCollection = $this->AutoCampaignRepository->fetchCampaignQueueLogTableSource($campaign->_id);

        $requireColumns = [
            '_id',
            '_uid',
            'status',
            'whatsapp_message_error',
            'phone_with_country_code',
            'full_name' => function ($rowData) {
                $firstName = $rowData['first_name'];
                $lastName = $rowData['last_name'];
                return trim($firstName . ' ' . $lastName);
            },
            'updated_at' => function ($rowData) {
                return $rowData['formatted_updated_time'];
            },
        ];
        $this->prepareCampaignData($campaignIdOrUid);
        // prepare data for the DataTables
        return $this->dataTableResponse($campaignCollection, $requireColumns);
    }
    /**
     * Campaign prepare executed log data
     *
     * @param  mix  $campaignIdOrUid
     * @return object
     *---------------------------------------------------------------- */
    public function prepareCampaignExecutedLogList($campaignIdOrUid)
    {
        // data fetch request
        $campaign = $this->AutoCampaignRepository->fetchIt($campaignIdOrUid);
        abortIf(__isEmpty($campaign));
        // data fetch request
        $campaignCollection = $this->AutoCampaignRepository->fetchCampaignExecutedLogTableSource($campaign->_id);
        $requireColumns = [
            '_id',
            '_uid',
            'whatsapp_message_error',
            'contact_wa_id',
            'status',
            'full_name' => function ($rowData) {
                $firstName = $rowData['first_name'];
                $lastName = $rowData['last_name'];
                return trim($firstName . ' ' . $lastName);
            },
            'messaged_at' => function ($rowData) {
                return $rowData['formatted_message_time'];
            },
            'updated_at' => function ($rowData) {
                return $rowData['formatted_updated_time'];
            },
        ];
        $this->prepareCampaignData($campaignIdOrUid);
        // prepare data for the DataTables
        return $this->dataTableResponse($campaignCollection, $requireColumns);
    }
}
