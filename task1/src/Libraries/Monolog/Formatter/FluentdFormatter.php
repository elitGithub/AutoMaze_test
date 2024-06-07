<?php declare(strict_types=1);

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Libraries\Monolog\Formatter;

use Libraries\Monolog\Formatter\FormatterInterface;
use Libraries\Monolog\Utils;
use Monolog\Formatter\Record;

/**
 * Class FluentdFormatter
 *
 * Serializes a log message to Fluentd unix socket protocol
 *
 * Fluentd config:
 *
 * <source>
 *  type unix
 *  path /var/run/td-agent/td-agent.sock
 * </source>
 *
 * Monolog setup:
 *
 * $logger = new Libraries\Monolog\Logger('fluent.tag');
 * $fluentHandler = new Libraries\Monolog\Handler\SocketHandler('unix:///var/run/td-agent/td-agent.sock');
 * $fluentHandler->setFormatter(new Libraries\Monolog\Formatter\FluentdFormatter());
 * $logger->pushHandler($fluentHandler);
 *
 * @author Andrius Putna <fordnox@gmail.com>
 */
class FluentdFormatter implements FormatterInterface
{
    /**
     * @var bool $levelTag should message level be a part of the fluentd tag
     */
    protected bool $levelTag = false;

    /**
     * @param  bool  $levelTag
     */
    public function __construct(bool $levelTag = false)
    {
        if (!function_exists('json_encode')) {
            throw new \RuntimeException('PHP\'s json extension is required to use Monolog\'s FluentdUnixFormatter');
        }

        $this->levelTag = $levelTag;
    }

    /**
     * @return bool
     */
    public function isUsingLevelsInTag(): bool
    {
        return $this->levelTag;
    }

    /**
     * Formats a log record.
     *
     * @param  array $record A record to format
     * @return mixed The formatted record
     *
     * @phpstan-param Record $record
     */
    public function format(array $record): string
    {
        $tag = $record['channel'];
        if ($this->levelTag) {
            $tag .= '.' . strtolower($record['level_name']);
        }

        $message = [
            'message' => $record['message'],
            'context' => $record['context'],
            'extra' => $record['extra'],
        ];

        if (!$this->levelTag) {
            $message['level'] = $record['level'];
            $message['level_name'] = $record['level_name'];
        }

        return Utils::jsonEncode([$tag, $record['datetime']->getTimestamp(), $message]);
    }

    /**
     * Formats a set of log records.
     *
     * @param  array $records A set of records to format
     * @return mixed The formatted set of records
     *
     * @phpstan-param Record[] $records
     */
    public function formatBatch(array $records): string
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }

        return $message;
    }
}
