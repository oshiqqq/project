@extends('layouts.app')

<script>
    // Если в localStorage уже есть access_token, перенаправляем пользователя на главную страницу
    if (localStorage.getItem("access_token")) {
        window.location.href = "{{ route('home') }}";
    }
</script>

@section('content')
<div class="container">
    <h2>Регистрация</h2>
    <form id="registerForm">
        <input type="text" id="username" placeholder="Имя пользователя" required>
        <input type="email" id="email" placeholder="Email" required>
        <input type="password" id="password" placeholder="Пароль" required>
        <input type="password" id="c_password" placeholder="Подтвердите пароль" required>
        <input type="date" id="birthday" required>
        <button type="submit">Зарегистрироваться</button>
    </form>
</div>

<script>
document.getElementById("registerForm").addEventListener("submit", async function(event) {
    event.preventDefault(); // Предотвращаем перезагрузку страницы при отправке формы

    let response = await fetch("/api/auth/register", { // Отправляем POST-запрос на сервер
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            username: document.getElementById("username").value,
            email: document.getElementById("email").value,
            password: document.getElementById("password").value,
            password_confirmation: document.getElementById("c_password").value,
            birthday: document.getElementById("birthday").value
        })
    });

    let result = await response.json(); // Получаем ответ сервера

    if (response.ok) {
        // Если регистрация успешна, уведомляем пользователя и перенаправляем на страницу входа
        alert("Регистрация успешна!");
        window.location.href = "/login";
    } else {
        // Если произошла ошибка, выводим сообщение об ошибке
        alert("Ошибка регистрации: " + result.message);
    }
});
</script>

@endsection
