<!DOCTYPE html>
<html>
<head>
    <title>Приглашение к голосованию</title>
</head>
<body>
    <h2>Уважаемая организация {{ $pointName }}!</h2>
    <p>Вас приглашают согласовать участие в научно-образовательном маршруте <strong>{{ $routeTitle }}</strong>.</p>
    <p><strong>Предлагаемая дата:</strong> {{ $proposedDate }}</p>
    <p><strong>Дедлайн голосования:</strong> {{ $deadline }}</p>
    <p>Пожалуйста, перейдите по ссылке, чтобы подтвердить или отклонить участие:</p>
    <p><a href="{{ $url }}">{{ $url }}</a></p>
    <p>Спасибо!</p>
</body>
</html>