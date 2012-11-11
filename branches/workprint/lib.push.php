<?php
    
    /*
        PuSH // pubsubhubbub
    */

    class PuSH
    {
        public static function notify( $hubs, $urls )
        {
            if( !is_array( $hubs ) )
                $hubs = array($hubs);

            if( !is_array( $urls ) )
                $urls = array($urls);

            $push_object = new Object();
            $push_object->hubs = $hubs;
            $push_object->urls = $urls;

            require_once('class.scheduler.php');
            $Scheduler = Scheduler::getInstance();

            //
            // queue the push object
            $schedule_id = $Scheduler->queue( 'push_notify', $push_object );

            //
            // dispatch to ping
            $Scheduler->dispatch($schedule_id, "http://{$GLOBALS['DOMAIN']}/api/push/notify.ping");
        }
    }

?>
