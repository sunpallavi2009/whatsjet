<?php
/**
* ContactRepository.php - Repository file
*
* This file is part of the Contact component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\EmailToWeb\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\Contact\Interfaces\ContactRepositoryInterface;
use App\Yantrana\Components\EmailToWeb\Models\EmailToWebModel;

class EmailToWebRepository extends BaseRepository implements ContactRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var object
     */
    protected $primaryModel = EmailToWebModel::class;

    /**
     * Fetch contact datatable source
     *
     * @return mixed
     *---------------------------------------------------------------- */
    public function fetchEmailToWebDataTableSource($groupContactIds = null, $contactGroupUid = null)
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

    public function deleteEmailToWeb($emailtoweb)
    {
        // Check if $contact deleted
        if ($emailtoweb->deleteIt()) {
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

    public function deleteSelectedEmailToWeb(array $emailIdOrUid, int|null $vendorId = null)
    {
        return $this->primaryModel::where([
            'vendors__id' => $vendorId ?: getVendorId()
        ])->whereIn('id', $emailIdOrUid)->delete();
    }

    
}
