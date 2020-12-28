<?php
require_once __DIR__ . '/BaseCommand.php';
require_once __DIR__ . '/Telegram.php';

class Bot
{
    private $config;
    /* @var Telegram */
    private $tg;
    private $aliases = [];
    private $commandsFolder = 'commands';
    private $bot;
    /* @var Logger */
    private $logger;

    public function __construct($config, Logger $logger)
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->tg = new Telegram($config['token']);
        if (file_exists(__DIR__ . "/aliases.json")) {
            $this->aliases = json_decode(file_get_contents(__DIR__ . "/aliases.json"), true);
        }
    }

    public function loadCommand($commandName)
    {
        $className = $commandName . "_command";
        $filePath = $this->commandsFolder . $commandName . ".php";

        if (file_exists($filePath)) {
            require_once $filePath;
            if (class_exists($className)) {
                return new $className(['allowed_ids' => $this->config['allowed_ids']], $commandName, $this->tg);
            }
        }
        return false;
    }

    private function parseText($text)
    {
        $result = array(
            'text' => $text,
            'is_command' => false
        );
        if ($text[0] == '/') {
            $parts = explode(' ', $text);
            $cmdName = str_replace("@" . $this->bot["username"], "", substr($parts[0], 1)); // временно... для работы команды /команда@имя_бота
            $result['is_command'] = true;
            $result['command_name'] = $cmdName;
        } elseif (!empty($this->aliases)) {
            $text = mb_strtolower(trim($text));
            if (array_key_exists($text, $this->aliases)) {
                $result['is_command'] = true;
                $result['command_name'] = $this->aliases[$text];
            }
        }
        return $result;
    }

    private function isGameBot() {
        return isset($this->config['game_bot']) && $this->config['game_bot'] && $this->config['game_bot']['enabled'];
    }

    public function processCallbackQuery($packet) {
        $this->logger->write('processing callback query');
        $callbackQuery = $packet['callback_query'];
        if (isset($callbackQuery['game_short_name']) && $this->isGameBot()) {
            $this->tg->answerCallbackQuery($callbackQuery['id'], [
                'url' => $this->config['game_bot']['game_base_url'] . '?inline_message_id=' . $callbackQuery['inline_message_id']
            ]);
        } else {
            $this->logger->write('ignoring/not implemented');
        }
    }

    public function processPacket($packet)
    {
        if (isset($packet['message']['text'])) {
            $text = $packet['message']['text'];
            $chatId = $packet['message']['chat']['id'];
            $userId = $packet['message']['from']['id'];
            $data = $this->parseText($text);
            if ($data['is_command']) {
                $this->logger->write('loading command: ' . $data['command_name']);
                $cmd = $this->loadCommand($data['command_name']);
                if ($cmd !== false && $cmd instanceof BaseCommand && ($cmd->hasAccess($chatId) || $cmd->hasAccess($userId))) {
                    $result = $cmd->process($chatId, $text, $userId, $packet);
                    if (is_array($result)) {
                        $this->tg->{$result["type"]}($chatId, $result["data"]);
                    } elseif (!empty($result)) {
                        $this->tg->sendMessage($chatId, $result);
                    }
                    $this->tg->deleteMessage($chatId, $packet['message']["message_id"]);
                } else {
                    $this->logger->write('incorrect command');
                }
            } else {
                $this->logger->write('ignoring text message');
            }
        } elseif (isset($packet['callback_query'])) {
            $this->processCallbackQuery($packet);
        } else {
            $this->logger->write('ignoring packet');
        }
    }

    public function startPolling()
    {
        $offset = 0;
        while (true) {
            $updates = $this->tg->getUpdates($offset);
            if ($updates && count($updates) > 0) {
                $offset = $updates[count($updates) - 1]['update_id'] + 1;
            }
            foreach ($updates as $update) {
                $this->logger->write($update);
                $this->processPacket($update);
            }
            usleep($this->config['interval'] * 1000); //micro seconds, not milli seconds
        }
    }

    public function start() {
        if ($this->config['webhook']) {
            $data = file_get_contents('php://input');
            $this->logger->write($data);
            $data = json_decode($data, true);
            $this->processPacket($data);
        } else {
            $this->bot = $this->tg->getMe();
            if (empty($this->bot["username"])) {
                throw new Exception("Incorrect bot token");
            }
            $this->logger->write('starting longpolling');
            $this->startPolling();
        }
    }
}

?>