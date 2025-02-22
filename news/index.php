<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>For Liane Nichole</title>
    <style>
        body {
            background-color: #ffdde1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
            font-family: Arial, sans-serif;
            position: relative;
        }

        h1 {
            color: #d63384;
            font-size: 2rem;
            text-align: center;
        }

        .heart-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .heart {
            position: absolute;
            bottom: 0;
            width: 20px;
            height: 20px;
            background-color: red;
            transform: rotate(-45deg);
            animation: float 5s infinite ease-in-out;
        }

        .heart::before,
        .heart::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            background-color: red;
            border-radius: 50%;
        }

        .heart::before {
            top: -10px;
            left: 0;
        }

        .heart::after {
            left: 10px;
            top: 0;
        }

        @keyframes float {
            0% {
                transform: translateY(0) scale(1) rotate(-45deg);
                opacity: 0.8;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                transform: translateY(-100vh) scale(1.5) rotate(-45deg);
                opacity: 0;
            }
        }

        .box {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            transition: transform 0.3s ease-in-out;
            margin: 10px;
        }

        .box:hover {
            transform: scale(1.1);
        }

        .hidden-heart,
        .hidden-kiss {
            display: none;
            flex-direction: column;
            align-items: center;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.5);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .glowing-heart {
            width: 50px;
            height: 50px;
            background-color: red;
            position: relative;
            transform: rotate(-45deg);
        }

        .glowing-heart::before,
        .glowing-heart::after {
            content: "";
            position: absolute;
            width: 50px;
            height: 50px;
            background-color: red;
            border-radius: 50%;
        }

        .glowing-heart::before {
            top: -25px;
            left: 0;
        }

        .glowing-heart::after {
            left: 25px;
            top: 0;
        }

        .glowing-kiss {
            font-size: 50px;
            animation: kissGlow 1s infinite alternate;
        }

        @keyframes kissGlow {
            from {
                text-shadow: 0 0 10px pink;
            }

            to {
                text-shadow: 0 0 30px pink;
            }
        }
    </style>
</head>

<body>
    <h1>Hi, Liane Nichole, I love you so much.</h1>
    <div class="heart-container"></div>
    <div class="box" onclick="showHeart()">Click to open</div>
    <div class="box" onclick="showKiss()">Click for a kiss</div>
    <div class="hidden-heart" id="heartMessage">
        <div class="glowing-heart"></div>
        <p>Here is my heart, it's yours forever. ❤️</p>
    </div>
    <div class="hidden-kiss" id="kissMessage">
        <div class="glowing-kiss">💋</div>
        <p>A sweet kiss just for you, my love. 💖</p>
    </div>
    <script>
        function createHeart() {
            const heart = document.createElement('div');
            heart.classList.add('heart');
            heart.style.left = Math.random() * 100 + 'vw';
            heart.style.animationDuration = Math.random() * 2 + 3 + 's';
            document.querySelector('.heart-container').appendChild(heart);
            setTimeout(() => heart.remove(), 5000);
        }
        setInterval(createHeart, 300);

        function showHeart() {
            document.getElementById('heartMessage').style.display = 'flex';
        }

        function showKiss() {
            document.getElementById('kissMessage').style.display = 'flex';
        }
    </script>
</body>

</html>