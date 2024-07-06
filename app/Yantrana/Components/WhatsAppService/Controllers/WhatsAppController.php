<?php

namespace App\Yantrana\Components\WhatsAppService\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequestTwo; // Make sure this import is correct
use App\Yantrana\Components\Vendor\VendorSettingsEngine;
use App\Yantrana\Components\WhatsAppService\Controllers\WhatsAppServiceController;
use App\Yantrana\Components\WhatsAppService\WhatsAppTemplateEngine;
use Illuminate\Support\Facades\Auth;

class WhatsAppController extends BaseController
{
    protected $whatsAppServiceController;

    public function __construct(WhatsAppServiceController $whatsAppServiceController)
    {
        $this->whatsAppServiceController = $whatsAppServiceController;
        // Middleware to ensure the user is authenticated
        $this->middleware('auth');
    }
    
   
}
