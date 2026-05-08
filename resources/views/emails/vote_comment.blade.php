<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Причина отказа</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 500px; margin: 40px auto; padding: 20px; }
    textarea { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; }
    .btn { background: #ef4444; color: white; padding: 12px 24px; border: none; 
           border-radius: 6px; cursor: pointer; font-size: 16px; }
</style>
</head>
<body>
    <h2>Укажите причину отказа</h2>
    <textarea id="comment" rows="4" placeholder="Комментарий (необязательно)"></textarea>
    <br><br>
    <button class="btn" onclick="sendVote()">Отправить отказ</button>

    <script>
    function sendVote() {
        fetch('/vote/{{ $token }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ 
                status: 'rejected', 
                comment: document.getElementById('comment').value 
            })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                document.body.innerHTML = '<h2>✓ Ваш отказ принят</h2>';
            }
        });
    }
    </script>
</body>
</html>