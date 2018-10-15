<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use App\Libraries\SecurityLib;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Http\ResponseTrait;

class ResponseMacroServiceProvider extends ServiceProvider
{
    use ResponseTrait, Macroable {
        Macroable::__call as macroCall;
    }
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Response::macro('encrypt', function ($value) {
            $this->securityLibObj = new SecurityLib();              
            return Response::make($this->securityLibObj->encrypt( json_encode($value)));
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
