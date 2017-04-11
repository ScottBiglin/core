<?php

namespace App\Modules\Visittransfer\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use App\Modules\Visittransfer\Events\ApplicationStatusChanged;
use App\Modules\Visittransfer\Jobs\SendApplicantStatusChangeEmail;

class NotifyApplicantOfStatusChange implements ShouldQueue
{
    public function __construct()
    {
        //
    }

    public function handle(ApplicationStatusChanged $event)
    {
        $application = $event->application;
        $application->account->notify(new ApplicationStatusChanged($application));
    }
}
