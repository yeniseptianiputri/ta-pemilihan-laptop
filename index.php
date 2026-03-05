<?php

declare(strict_types=1);

$target = 'public/';
$query = (string)($_SERVER['QUERY_STRING'] ?? '');

if ($query !== '') {
    $target .= '?' . $query;
}

header('Location: ' . $target);
exit;