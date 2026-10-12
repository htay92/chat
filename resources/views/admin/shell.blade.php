<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chatbot administration</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div data-chatbot-admin data-page="{{ $page ?? 'dashboard' }}"
         @isset($resourceId) data-resource-id="{{ $resourceId }}" @endisset
         @isset($sourceQuestionId) data-source-question-id="{{ $sourceQuestionId }}" @endisset></div>
    <noscript><main><h1>Chatbot administration</h1><p>Enable JavaScript to manage FAQs and review questions.</p></main></noscript>
</body>
</html>
