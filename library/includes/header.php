<?php
// 必须先定义：$pageTitle、$workTitle
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle ?? '作品页面') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/intro.css">
    <link rel="stylesheet" href="/css/manage.css">
</head>
<body>
    <header>
        <h1><?= htmlspecialchars($workTitle ?? '') ?></h1>
    </header>
    <main>
        <div class="container">