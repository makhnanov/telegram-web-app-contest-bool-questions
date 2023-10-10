<?php

use Illuminate\Support\Str;
use OxMohsen\TgBot\Validate;
use Predis\Client;
use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

Dotenv::createUnsafeMutable(__DIR__)->load();

define('TOKEN', getenv('TOKEN'));

const BAD_EXIT = 1;

$redis = new Predis\Client([
    'scheme' => 'tcp',
    'host' => 'localhost',
    'port' => 6379,
]);

$uri = $_SERVER['REQUEST_URI'];
if (!Str::startsWith($uri, '/api?')) {
    die(BAD_EXIT);
}

$action = $_GET['action'] ?? null;
unset($_GET['action']);

switch ($action) {
    case 'add-question': addQuestion(); break;
    case 'get-one-question': getOneQuestion(); break;
    case 'send-answer': receiveAnswer(); break;
    case 'get-my-answers': getMyAnswers(); break;
    case 'my-question-statistic': getMyQuestionStatistic(); break;
}

error();

function addQuestion() {
    $data = $_GET['data'] ?? null;
    $questionText = $_GET['question_text'] ?? null;
    if (!$data || !$questionText) {
        error();
    }
    $initData = base64_decode($data);
    !Validate::isSafe(TOKEN, $initData) and error();

    $questionText = substr(base64_decode($questionText), 0, 80);
    $questionText = ucfirst(rtrim(trim($questionText), '?') . '?');

    $userId = userId($initData);

    OneQuestion::create($userId, $questionText);

    success(['text' => $questionText]);
}

function getOneQuestion() {
    $data = $_GET['data'] ?? null;
    if (!$data) {
        error();
    }
    $initData = base64_decode($data);
    !Validate::isSafe(TOKEN, $initData) and error();

    $keyAlreadyAnswered = 'already_answered_' . userId($initData);

    $notAnswered = redis()->sdiff(['all_questions', $keyAlreadyAnswered]);

    $randFromAll = redis()->srandmember('all_questions');

    if (in_array($randFromAll, $notAnswered, true)) {
        if ($text = redis()->get('question_' . $randFromAll)) {
            success(['text' => $text, 'question_id' => $randFromAll]);
        }
    }
    $key = array_pop($notAnswered);
    if ($key) {
        if ($text = redis()->get('question_' . $key)) {
            success(['text' => $text, 'question_id' => $key]);
        }
    }
}

function receiveAnswer() {
    $data = $_GET['data'] ?? null;
    $questionId = $_GET['question_id'] ?? null;
    $answer = $_GET['answer'] ?? null;
    if (!$data || !$questionId || !in_array($answer, ['0', '1'], true)) {
        error();
    }
    $initData = base64_decode($data);
    !Validate::isSafe(TOKEN, $initData) and error();
    $keyAlreadyAnswered = 'already_answered_' . userId($initData);
    redis()->sadd($keyAlreadyAnswered, [$questionId]);
    $answer
        ? redis()->sadd($keyAlreadyAnswered . '_yes', [$questionId])
        : redis()->sadd($keyAlreadyAnswered . '_no', [$questionId]);
    $answer
        ? redis()->sadd('total_' . $questionId . '_yes', [$questionId])
        : redis()->sadd('total_' . $questionId . '_no', [$questionId]);
    success();
}

function getMyAnswers() {
    $data = $_GET['data'] ?? null;
    if (!$data) {
        error();
    }
    $initData = base64_decode($data);
    !Validate::isSafe(TOKEN, $initData) and error();
    $userId = userId($initData);
    $keyAlreadyAnswered = 'already_answered_' . $userId;
    $alreadyAnswered = redis()->smembers($keyAlreadyAnswered);
    $questions = [];
    foreach ($alreadyAnswered as $id) {
        $questions[] = new OneQuestion($id, $userId);
    }
    success([
        'questions' => array_map(function (OneQuestion $q) {
            return [
                'text' => $q->text,
                'yes' => $q->isYes,
            ];
        }, $questions)
    ]);
}


function getMyQuestionStatistic() {
    $data = $_GET['data'] ?? null;
    if (!$data) {
        error();
    }
    $initData = base64_decode($data);
    !Validate::isSafe(TOKEN, $initData) and error();
    $userId = userId($initData);
    $allMyQuestionIds = redis()->smembers('my_questions_' . $userId);
    $allMyQuestions = [];
    foreach ($allMyQuestionIds as $id) {
        $allMyQuestions[] = [
            'text' => redis()->get('question_' . $id),
            'yes' => redis()->scard('total_' . $id . '_yes'),
            'no' => redis()->scard('total_' . $id . '_no'),
        ];
    }
    success(['questions' => $allMyQuestions]);
}

class OneQuestion {
    public $owner;

    public $text;

    public $isYes;

    public function __construct($id, $userId) {
        $this->text = (string)redis()->get('question_' . $id);
        $this->isYes = (bool)redis()->sismember("already_answered_{$userId}_yes", $id);
    }

    public static function create(int $owner, string $text) {
        $newQuestionId = nextQuestionId();
        redis()->sadd('all_questions', [$newQuestionId]);
        redis()->set('question_' . $newQuestionId, $text);
        redis()->set('question_' . $newQuestionId . '_owner', $owner);
        redis()->sadd('my_questions_' . $owner, [$newQuestionId]);
    }
}

function userId($initData) {
    $keyWithValues = explode('&', $initData);
    foreach ($keyWithValues as $keyWithValue) {
        $keyWithValue = explode('=', $keyWithValue);
        $first = array_shift($keyWithValue);
        if ($first === 'user') {
            $otherParts = implode('=', $keyWithValue);
            $otherParts = json_decode(urldecode($otherParts), true);
            if (isset($otherParts['id'])) {
                return $otherParts['id'];
            }
        }
    }
    throw new Exception('user.id not found');
}

function nextQuestionId(): int {
    $key = 'question_increment';
    $questionIncrement = redis()->get($key);
    if (!$questionIncrement) {
        redis()->set($key, 0);
    }
    return redis()->incr($key);
}

function redis(): Client {
    global $redis;
    return $redis;
}

function error($reason = null) {
    echo json_encode([
        'data' => [
            'success' => false,
            'reason' => $reason
        ]
    ]);
    die(BAD_EXIT);
}

function success($additional = []) {
    echo json_encode(['data' => array_merge(['success' => true], $additional)]);
    die();
}
