<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantalla de Bienvenida</title>
    <link rel="stylesheet" href="styles.css">
</head>
<style>
    body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    background-color: #f0f0f0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.container {
    text-align: center;
}

h1 {
    color: #333;
}

.animation {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

.circle {
    width: 20px;
    height: 20px;
    background-color: #333;
    border-radius: 50%;
    margin: 0 5px;
    animation: bounce 1s infinite;
}

.circle:nth-child(2) {
    animation-delay: 0.2s;
}

.circle:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-15px);
    }
}
    </style>

<body>
    <div class="container">
        <h1>Bienvenido al Sistema de Turnos</h1>
        <div class="animation">
            <span class="circle"></span>
            <span class="circle"></span>
            <span class="circle"></span>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>