<?php

/**
 * Description of system_message
 *
 * @author porquero
 */
class system_message {

    public function __construct() {
        $CI = & get_instance();
    }

    /**
     * Return a message for use in system messages
     *
     * @param string $class
     * @param string $message
     * @param string $format
     * @return mixed
     */
    public function send($message, $class = null, $format = 'json') {
        $message = (array) $message;

        if ($class != null) {
            $message['type'] = $class;
        }

        switch ($format) {
            case 'json':
                return json_encode($message);
                break;
            default:
                return $message;
                break;
        }
    }

}