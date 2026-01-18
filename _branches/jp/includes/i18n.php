<?php
// /includes/i18n.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1) lang 결정: GET > SESSION > default(ko)
if (isset($_GET['lang'])) {
    $lang = strtolower(trim($_GET['lang']));
    if (in_array($lang, ['ko', 'ja', 'en'], true)) {
        $_SESSION['lang'] = $lang;
    }
}
// 전역 변수 $lang 초기화 (null 방지)
$lang = $_SESSION['lang'] ?? 'ko';

// global $lang 선언으로 모든 함수에서 접근 가능하도록 보장
if (!isset($GLOBALS['lang'])) {
    $GLOBALS['lang'] = $lang;
}

// 2) 번역 파일 로드
$__i18n_ko = [];
$__i18n_ja = [];
$__i18n_en = [];

$base = dirname(__DIR__); // project root (where lang/ exists)

$ko_file = $base . '/lang/ko.php';
$ja_file = $base . '/lang/ja.php';
$en_file = $base . '/lang/en.php';

if (file_exists($ko_file)) $__i18n_ko = require $ko_file;
if (file_exists($ja_file)) $__i18n_ja = require $ja_file;
if (file_exists($en_file)) $__i18n_en = require $en_file;

// 3) 번역 함수 (현재 lang 우선, 없으면 ko fallback, 그래도 없으면 key/fallback)
function t(string $key, ?string $fallback = null): string
{
    global $lang, $__i18n_ko, $__i18n_ja, $__i18n_en;

    if ($lang === 'ja' && isset($__i18n_ja[$key])) return (string)$__i18n_ja[$key];
    if ($lang === 'en' && isset($__i18n_en[$key])) return (string)$__i18n_en[$key];

    if (isset($__i18n_ko[$key])) return (string)$__i18n_ko[$key]; // KO fallback
    return $fallback ?? $key;
}

function current_lang(): string
{
    global $lang;
    return $lang ?? 'ko'; // null 방지: 기본값 'ko' 반환
}
