<?php
/**
* Contact.php - Model file
*
* This file is part of the Contact component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\GmailToWeb\Models;

use App\Yantrana\Base\BaseModel;
use App\Yantrana\Components\Auth\Models\AuthModel;
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel;
use App\Yantrana\Support\Country\Models\Country;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GmailToWebModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'gmail_to_webs';

    /**
     * Datatable Result counts also its max result per request.
     *
     * @var string
     *----------------------------------------------------------------------- */
    protected $maxDataTableResultCount = 500;

    protected $primaryKey = 'id';  

    /**
     * @var array - The attributes that should be casted to native types.
     */
    protected $casts = [

    ];

}
