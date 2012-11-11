<?php
    
    require_once('abstract.apimethod.php');
    require_once('class.scheduler.php');
    require_once('lib.nufiles.php');

    class pingPushNotify extends NuclearAPIMethod
    {
        protected function build()
        {
            $schedule_id = $this->call->schedule_id;

            if( !$schedule_id || !is_numeric($schedule_id) )
                throw new Exception("Invalid schedule id", 4);


            $push_object = Scheduler::getInstance()->unqueue( $schedule_id, 'push_notify' );

            if( !is_null($push_object) )
            {
                $hubs = $push_object->hubs;
                $urls = $push_object->urls;

                $rest_fields = 'hub.mode=publish';

                // encode the urls
                foreach( $urls as $url )
                    $rest_fields .= '&hub.url='. urlencode($url);

                // post to hubs
                foreach( $hubs as $hub )
                    NuFiles::curl( $hub, 'post', $rest_fields );
            }

            return (object) array('status'=>'ok');
        }
    }

    return "pingPushNotify";

?>
