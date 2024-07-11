<?php
/**
* ContactEngine.php - Main component file
*
* This file is part of the Contact component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\GmailToWeb;

use XLSXWriter;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Yantrana\Base\BaseEngine;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use App\Yantrana\Components\User\Repositories\UserRepository;
use App\Yantrana\Support\Country\Repositories\CountryRepository;
use App\Yantrana\Components\Contact\Repositories\LabelRepository;
use App\Yantrana\Components\GmailToWeb\Repositories\GmailToWebRepository;
use App\Yantrana\Components\Contact\Interfaces\ContactEngineInterface;
use App\Yantrana\Components\Contact\Repositories\ContactGroupRepository;
use App\Yantrana\Components\Contact\Repositories\ContactLabelRepository;
use App\Yantrana\Components\Contact\Repositories\GroupContactRepository;
use App\Yantrana\Components\Contact\Repositories\ContactCustomFieldRepository;

class GmailToWebEngine extends BaseEngine implements ContactEngineInterface
{
    /**
     * @var GmailToWebRepository - Contact Repository
     */
    protected $gmailToWebRepository;

    /**
     * @var ContactGroupRepository - ContactGroup Repository
     */
    protected $contactGroupRepository;

    /**
     * @var GroupContactRepository - ContactGroup Repository
     */
    protected $groupContactRepository;

    /**
     * @var ContactCustomFieldRepository - ContactGroup Repository
     */
    protected $contactCustomFieldRepository;
    /**
     * @var UserRepository - User Repository
     */
    protected $userRepository;
    /**
     * @var LabelRepository - Label Repository
     */
    protected $labelRepository;
    /**
     * @var ContactLabelRepository - Contact Label Repository
     */
    protected $contactLabelRepository;

    /**
     * Constructor
     *
     * @param  GmailToWebRepository  $gmailToWebRepository  - Contact Repository
     * @param  ContactGroupRepository  $contactGroupRepository  - ContactGroup Repository
     * @param  GroupContactRepository  $groupContactRepository  - Group Contacts Repository
     * @param  ContactCustomFieldRepository  $contactCustomFieldRepository  - Contacts Custom  Fields Repository
     * @param  UserRepository  $userRepository  - User Fields Repository
     * @param  LabelRepository  $labelRepository  - Labels Repository
     * @param  ContactLabelRepository  $contactLabelRepository  - Contact Labels Repository
     *
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(
        GmailToWebRepository $gmailToWebRepository,
        ContactGroupRepository $contactGroupRepository,
        GroupContactRepository $groupContactRepository,
        ContactCustomFieldRepository $contactCustomFieldRepository,
        UserRepository $userRepository,
        LabelRepository $labelRepository,
        ContactLabelRepository $contactLabelRepository,
    ) {
        $this->gmailToWebRepository = $gmailToWebRepository;
        $this->contactGroupRepository = $contactGroupRepository;
        $this->groupContactRepository = $groupContactRepository;
        $this->contactCustomFieldRepository = $contactCustomFieldRepository;
        $this->userRepository = $userRepository;
        $this->labelRepository = $labelRepository;
        $this->contactLabelRepository = $contactLabelRepository;
    }

    /**
     * Contact datatable source
     *
     * @return array
     *---------------------------------------------------------------- */

    public function prepareGmailToWebDataTableSource($contactGroupUid = null)
    {
        $groupContactIds = [];
    
        // Fetch data based on group UID if provided
        if ($contactGroupUid) {
            $vendorId = getVendorId();
            $groupContacts = $this->gmailToWebRepository->fetchItAll([
                'contact_groups__id' => $contactGroupUid,
                'vendors__id' => $vendorId,
            ]);
    
            $groupContactIds = $groupContacts->pluck('contacts__id')->toArray();
        }
    
        // Fetch email data for DataTables
        $gmailData = $this->gmailToWebRepository->fetchGmailToWebDataTableSource($groupContactIds, $contactGroupUid);
    
        // Required columns for DataTables display
        $requiredColumns = [
            'id',
            'from_email',
            'to_email',
            'subject',
            'received_at' => function ($rowData) {
                return formatDateTime($rowData['received_at']);
            },
            'testing' => function ($rowData) {
                $subject = strtolower($rowData['subject']);
                $body = strtolower($rowData['body']);
    
                // Check for keywords in both subject and body
                if (strpos($subject, 'inquiry') !== false || strpos($body, 'inquiry') !== false) {
                    return 'Inquiry';
                } elseif (strpos($subject, 'issue') !== false || strpos($body, 'issue') !== false) {
                    return 'Support';
                } elseif (strpos($subject, 'problem') !== false || strpos($body, 'problem') !== false) {
                    return 'Support';
                }  elseif (strpos($subject, 'support') !== false || strpos($body, 'support') !== false) {
                    return 'Support';
                } else {
                    return '';
                }
            },
            // Add more fields as needed
        ];
    
        // Prepare data for DataTables response
        return $this->dataTableResponse($gmailData, $requiredColumns);
    }
     

    public function prepareContactRequiredData($groupUid = null)
    {
        $vendorId = getVendorId();

        if($groupUid) {
            $group = $this->contactGroupRepository->fetchIt([
                '_uid' => $groupUid,
                'vendors__id' => $vendorId,
            ]);
            abortIf(__isEmpty($group));
        }

        $vendorContactCustomFields = $this->contactCustomFieldRepository->fetchItAll([
            'vendors__id' => $vendorId,
        ]);
        // contact groups
        $vendorContactGroups = $this->contactGroupRepository->fetchItAll([
            'vendors__id' => $vendorId,
        ]);

        return $this->engineSuccessResponse([
            'groupUid' => $groupUid,
            'vendorContactGroups' => $vendorContactGroups,
            'vendorContactCustomFields' => $vendorContactCustomFields,
        ]);
    }

    public function prepareGmailToWebUpdateData($gmailIdOrUid)
    {
        $gmailtoweb = $this->gmailToWebRepository->fetchIt($gmailIdOrUid);
        if (__isEmpty($gmailtoweb)) {
            return $this->engineResponse(18, null, __tr('Gmail To Web not found.'));
        }

        $gmailtowebArray = $gmailtoweb->toArray();
        return $this->engineSuccessResponse(array_merge($gmailtowebArray));
    }

    public function processGmailToWebDelete($gmailIdOrUid)
    {
        // fetch the record
        $gmailtoweb = $this->gmailToWebRepository->fetchIt($gmailIdOrUid);
        // check if the record found
        if (__isEmpty($gmailtoweb)) {
            // if not found
            return $this->engineResponse(18, null, __tr('Gmail To Web not found'));
        }

        // if(getVendorSettings('test_recipient_contact') == $contact->_uid) {
        //     return $this->engineFailedResponse([], __tr('Record set as Test Contact for Campaign, Set another contact for test before deleting it.'));
        // }

        // ask to delete the record
        if ($this->gmailToWebRepository->deleteIt($gmailtoweb)) {
            // if successful
            return $this->engineSuccessResponse([], __tr('Gmail To Web deleted successfully'));
        }

        // if failed to delete
        return $this->engineFailedResponse([], __tr('Failed to delete Gmail To Web'));
    }

    public function processSelectedGmailToWebDelete($request)
    {
        $selectedGmailToWeb = $request->get('selected_gmailtoweb');
        $message = '';
        // check for test number
        // if(in_array(getVendorSettings('test_recipient_contact'), $selectedContactUids)) {
        //     $message .= __tr(' However one of these contact is set as Test Contact, which can not be deleted.');
        //     if (($key = array_search(getVendorSettings('test_recipient_contact'), $selectedContactUids)) !== false) {
        //         unset($selectedContactUids[$key]);
        //     }
        //     if(empty($selectedContactUids)) {
        //         return $this->engineFailedResponse([], __tr('As selected is test contact it can not be deleted.'));
        //     }
        // }
        if(empty($selectedGmailToWeb)) {
            return $this->engineFailedResponse([], __tr('Nothing to delete'));
        }
        // ask to delete the record
        if ($this->gmailToWebRepository->deleteSelectedGmailToWeb($selectedGmailToWeb)) {
            // if successful
            return $this->engineSuccessResponse([
                'reloadDatatableId' => '#lwGmailToWebList'
            ], __tr('Gmail To Web deleted successfully.') . $message);
        }
        // if failed to delete
        return $this->engineFailedResponse([], __tr('Failed to delete Gmail To Web'));
    }

}
