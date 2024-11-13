<?php

namespace App\Core;

use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class LogMessages
{
    private $log;
    /**
     * LogManager constructor.
     *
     * Initializes the logging functionality for the application. Sets up the timezone from
     * the `.env` configuration, creates a `Logger` instance, configures the log message format,
     * and sets the log output to a specified file. The log will record messages with a timestamp,
     * log channel, and severity level. Logging levels are set to capture messages of `DEBUG` level and higher.
     *
     *
     */
    public function __construct()
    {

        $envFile = parse_ini_file('.env');
        $timeZone = $envFile['LOCAL_TIMEZONE'];
        date_default_timezone_set($timeZone);

        $this->log = new Logger('Custom_MVC');

        $streamHandler = new StreamHandler(dirname(__DIR__) . '/log_messages.log', Level::Debug);

        $output = "[%datetime%] %channel%.%level_name%: %message%\n";

        $dateFormat = "D M d H:i:s Y";

        $formatter = new LineFormatter($output, $dateFormat);

        $streamHandler->setFormatter($formatter);

        $this->log->pushHandler($streamHandler);
    }

    /**
     * Logs messages of different types.
     *
     * Based on the `$type` parameter, logs messages at different levels (`info` or `error`).
     * - `info`: Logs informational messages, typically for general operational messages.
     * - `error`: Logs error messages, typically for issues that need attention.
     *
     * @param string $type The type of message, either 'info' or 'error'.
     * @param string $message The message content to be logged.
     *
     */
    public function setMessage($type, $message)
    {
        switch ($type) {
            case 'info':
                $this->log->info($message);
                break;
            case 'error':
                $this->log->error($message);
                break;
        }
    }
};
