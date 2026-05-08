<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .btn { display: inline-block; padding: 12px 24px; border-radius: 6px; 
               text-decoration: none; font-weight: bold; margin: 8px; }
        .btn-confirm { background: #22c55e; color: white; }
        .btn-reject  { background: #ef4444; color: white; }
        .info { background: #f3f4f6; padding: 16px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h2>Запрос на согласование маршрута</h2>

    <div class="info">
        <p><strong>Маршрут:</strong> {{ $requestPoint->request->route->title }}</p>
        <p><strong>Дата:</strong> {{ $requestPoint->request->proposed_date->format('d.m.Y') }}</p>
        <p><strong>Время начала:</strong> {{ $requestPoint->request->start_time }}</p>
        <p><strong>Дедлайн:</strong> {{ $requestPoint->request->deadline->format('d.m.Y H:i') }}</p>
    </div>

    <p>Пожалуйста, примите решение по согласованию:</p>

    <div>
        <a href="{{ url('/vote/' . $requestPoint->token . '?status=confirmed') }}" 
           class="btn btn-confirm">✓ Подтверждаю</a>

        <a href="{{ url('/vote/' . $requestPoint->token . '?status=rejected') }}" 
           class="btn btn-reject">✗ Отклоняю</a>
    </div>

    <p style="color: #6b7280; font-size: 14px; margin-top: 20px;">
        Ссылка действительна до {{ $requestPoint->request->deadline->format('d.m.Y H:i') }}
    </p>
</body>
</html>