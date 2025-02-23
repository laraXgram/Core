<?php declare(strict_types=1);

namespace LaraGram\Log\Logger\Handler;

use LaraGram\Log\Logger\Formatter\FormatterInterface;
use LaraGram\Log\Logger\Level;
use LaraGram\Log\Logger\Utils;
use LaraGram\Log\Logger\Handler\Slack\SlackRecord;
use LaraGram\Log\Logger\LogRecord;

class SlackWebhookHandler extends AbstractProcessingHandler
{
    /**
     * Slack Webhook token
     */
    private string $webhookUrl;

    /**
     * Instance of the SlackRecord util class preparing data for Slack API.
     */
    private SlackRecord $slackRecord;

    /**
     * @param string      $webhookUrl             Slack Webhook URL
     * @param string|null $channel                Slack channel (encoded ID or name)
     * @param string|null $username               Name of a bot
     * @param bool        $useAttachment          Whether the message should be added to Slack as attachment (plain text otherwise)
     * @param string|null $iconEmoji              The emoji name to use (or null)
     * @param bool        $useShortAttachment     Whether the the context/extra messages added to Slack as attachments are in a short style
     * @param bool        $includeContextAndExtra Whether the attachment should include context and extra data
     * @param string[]    $excludeFields          Dot separated list of fields to exclude from slack message. E.g. ['context.field1', 'extra.field2']
     *
     * @throws MissingExtensionException If the curl extension is missing
     */
    public function __construct(
        string $webhookUrl,
        ?string $channel = null,
        ?string $username = null,
        bool $useAttachment = true,
        ?string $iconEmoji = null,
        bool $useShortAttachment = false,
        bool $includeContextAndExtra = false,
        $level = Level::Critical,
        bool $bubble = true,
        array $excludeFields = []
    ) {
        if (!\extension_loaded('curl')) {
            throw new MissingExtensionException('The curl extension is needed to use the SlackWebhookHandler');
        }

        parent::__construct($level, $bubble);

        $this->webhookUrl = $webhookUrl;

        $this->slackRecord = new SlackRecord(
            $channel,
            $username,
            $useAttachment,
            $iconEmoji,
            $useShortAttachment,
            $includeContextAndExtra,
            $excludeFields
        );
    }

    public function getSlackRecord(): SlackRecord
    {
        return $this->slackRecord;
    }

    public function getWebhookUrl(): string
    {
        return $this->webhookUrl;
    }

    /**
     * @inheritDoc
     */
    protected function write(LogRecord $record): void
    {
        $postData = $this->slackRecord->getSlackData($record);
        $postString = Utils::jsonEncode($postData);

        $ch = curl_init();
        $options = [
            CURLOPT_URL => $this->webhookUrl,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-type: application/json'],
            CURLOPT_POSTFIELDS => $postString,
        ];

        curl_setopt_array($ch, $options);

        Curl\Util::execute($ch);
    }

    public function setFormatter(FormatterInterface $formatter): HandlerInterface
    {
        parent::setFormatter($formatter);
        $this->slackRecord->setFormatter($formatter);

        return $this;
    }

    public function getFormatter(): FormatterInterface
    {
        $formatter = parent::getFormatter();
        $this->slackRecord->setFormatter($formatter);

        return $formatter;
    }
}
