@extends('layouts.app')

<script>
    // Если в localStorage уже есть access_token, перенаправляем на главную страницу
    if (localStorage.getItem("access_token")) {
        window.location.href = "{{ route('home') }}";
    }
</script>

@section('content')
<div class="container">
    <h2>Вход</h2>
    <form id="loginForm">
        <input type="text" id="username" placeholder="Имя пользователя" required>
        <input type="password" id="password" placeholder="Пароль" required>
        <button type="submit">Войти</button>
    </form>
</div>

<script>
document.getElementById("loginForm").addEventListener("submit", async function(event) {
    event.preventDefault(); // Предотвращаем (перезагрузку страницы)

    let response = await fetch("/api/auth/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            username: document.getElementById("username").value,
            password: document.getElementById("password").value
        })
    });

    let result = await response.json();

    if (result.access_token) {
        // Сохраняем токен в localStorage и перенаправляем пользователя
        localStorage.setItem("access_token", result.access_token);
        window.location.href = "/home";
    } else {
        // Выводим сообщение об ошибке при некорректных данных
        alert("Ошибка входа");
    }
});
</script>

@endsection
