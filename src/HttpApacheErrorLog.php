<?php

namespace Opcodes\LogViewer;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class HttpApacheErrorLog extends HttpLog
{
    static string $regex = '/\[(?<dttm>.*?)\]\s\[(?:(?<module>.*?):)?(?<level>.*?)\]\s\[pid\s(?<pid>\d*)\](?:\s\[client\s(?<client>.*?)\])?\s(?<message>.*)/';

    public ?CarbonInterface $datetime;

    public ?string $module;

    public ?string $level;

    public ?int $pid;

    public ?string $client;

    public ?string $message;

    public function __construct(
        public string $text,
        public ?string $fileIdentifier = null,
        public ?int $filePosition = null,
    ) {
        parent::__construct($text, $fileIdentifier, $filePosition);

        $matches = $this->parseText($text);

        $this->datetime = $this->parseDateTime($matches['datetime'])?->tz(
            config('log-viewer.timezone', config('app.timezone', 'UTC'))
        );
        $this->module = $matches['module'];
        $this->level = $matches['level'];
        $this->pid = isset($matches['pid']) ? intval($matches['pid']) : null;
        $this->client = $matches['client'];
        $this->message = $matches['message'];
    }

    public function parseText(string $text): array
    {
        preg_match(self::$regex, $this->text, $matches);

        return [
            'datetime' => $matches['dttm'] ?? null,
            'module' => $matches['module'] ?? null,
            'level' => $matches['level'] ?? null,
            'pid' => $matches['pid'] ?? null,
            'client' => isset($matches['client']) ? ($matches['client'] ?: null) : null,
            'message' => $matches['message'] ?? null,
        ];
    }

    public function parseDateTime(?string $datetime): ?CarbonInterface
    {
        return $datetime ? Carbon::createFromFormat('D M d H:i:s.u Y', $datetime) : null;
    }

    public static function matches(string $text): bool
    {
        return preg_match(self::$regex, $text) === 1;
    }
}