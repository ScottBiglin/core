<?php

namespace App\Modules\NetworkData\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;

class NetworkDataDownloaded extends Event {
    use SerializesModels;

    /**
     * Construct this event.
     *
     * There's little to construct at the minute as it's simply a notification!
     */
    public function __construct(){

    }
}