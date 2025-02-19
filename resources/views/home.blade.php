@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Главная страница</h2>

    <!-- Показываем кнопки "Войти" и "Регистрация" только если пользователь НЕ авторизован -->
    <div id="guestLinks">
        <a href="{{ route('login.form') }}">Войти</a>
        <a href="{{ route('register.form') }}">Регистрация</a>
    </div>

    <!-- Показываем API-кнопки только если пользователь авторизован -->
    <div id="authLinks" style="display: none;">
        <button onclick="logout()">Выйти</button>
        <button onclick="logoutAll()">Выйти со всех устройств</button>
        <button onclick="showChangePasswordForm()">Сменить пароль</button>

        <!-- Форма смены пароля -->
        <div id="changePasswordForm" style="display: none;">
            <input type="password" id="current_password" placeholder="Текущий пароль">
            <input type="password" id="new_password" placeholder="Новый пароль">
            <input type="password" id="confirm_password" placeholder="Подтвердите пароль">
            <button onclick="changePassword()">Обновить пароль</button>
        </div>
    </div>
</div>

<script>
    // Если у пользователя есть токен, значит, он авторизован
    if (localStorage.getItem("access_token")) {
        document.getElementById("authLinks").style.display = "block";  // Показываем кнопки для авторизованного пользователя
        document.getElementById("guestLinks").style.display = "none";  // Скрываем ссылки "Войти" и "Регистрация"
    }

    // Функция выхода из аккаунта
    function logout() {
        fetch("/api/auth/out", {
            method: "POST",
            headers: { "Authorization": "Bearer " + localStorage.getItem("access_token") }
        }).then(() => {
            localStorage.removeItem("access_token"); // Удаляем токен из localStorage
            window.location.href = "/home"; // Перенаправляем на главную страницу
        });
    }

    // Функция выхода со всех устройств
    function logoutAll() {
        fetch("/api/auth/out_all", {
            method: "POST",
            headers: { "Authorization": "Bearer " + localStorage.getItem("access_token") }
        }).then(() => {
            localStorage.removeItem("access_token"); // Удаляем токен
            window.location.href = "/home"; // Перенаправляем на главную страницу
        });
    }

    // Показываем форму смены пароля
    function showChangePasswordForm() {
        document.getElementById("changePasswordForm").style.display = "block";
    }

    // Функция смены пароля
    function changePassword() {
    let token = localStorage.getItem("access_token");
    console.log("Токен перед отправкой:", token);

    fetch("/api/auth/change_password", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Authorization": "Bearer " + token
        },
        body: JSON.stringify({
            current_password: document.getElementById("current_password").value,
            new_password: document.getElementById("new_password").value,
            confirm_password: document.getElementById("confirm_password").value
        })
    })
    .then(response => response.json().catch(() => response.text()))
    .then(result => {
        console.log("Ответ API:", result);
    })
    .catch(error => console.error("Ошибка запроса:", error));
}

</script>
@endsection
