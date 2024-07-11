<?php
/**
* ContactRepository.php - Repository file
*
* This file is part of the Contact component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\GmailToWeb\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\Contact\Interfaces\ContactRepositoryInterface;
use App\Yantrana\Components\GmailToWeb\Models\GmailToWebModel;

class GmailToWebRepository extends BaseRepository implements ContactRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var object
     */
    protected $primaryModel = GmailToWebModel::class;

    /**
     * Fetch contact datatable source
     *
     * @return mixed
     *---------------------------------------------------------------- */
    public function fetchGmailToWebDataTableSource($groupContactIds = null, $contactGroupUid = null)
    {
        // basic configurations for dataTables data
        $dataTableConfig = [
            // searchable columns
            'searchable' => [
                'from_email',
                'to_email',
                'subject',
                'received_at',
                'body'
            ],
        ];

        // get Model result for dataTables
        $query = $this->primaryModel::where([
            'vendors__id' => getVendorId()
        ]);
        if ($contactGroupUid) {
            $query->whereIn('id', $groupContactIds);
        }

        $query->orderBy('received_at', 'desc');

        return $query->dataTables($dataTableConfig)->toArray();
    }

    public function deleteGmailToWeb($gmailtoweb)
    {
        // Check if $contact deleted
        if ($gmailtoweb->deleteIt()) {
            // if deleted
            return true;
        }

        // if failed to delete
        return false;
    }

    // public function fetchIt($emailIdOrUid)
    // {
    //     return EmailToWebModel::where('id', $emailIdOrUid)->first();
    // }

    public function deleteSelectedGmailToWeb(array $gmailIdOrUid, int|null $vendorId = null)
    {
        return $this->primaryModel::where([
            'vendors__id' => $vendorId ?: getVendorId()
        ])->whereIn('id', $gmailIdOrUid)->delete();
    }

    
}
