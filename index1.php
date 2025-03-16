<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>School Cards Animation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #1a1a1a;
            transition: background-color 0.5s ease;
        }

        .container {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #1a1a1a;
            display: flex;
            justify-content: center;
            align-items: flex-end;
            transition: background-color 0.5s ease;
        }

        .card-wrapper {
            display: flex;
            gap: 20px;
            transform: translateY(100%);
            transition: transform 0.5s ease;
        }

        .card {
            width: 150px;
            height: 200px;
            background-color: #333;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card:hover {
            transform: scale(1.05);
        }

        .btn {
            padding: 10px 20px;
            background-color: #e63946;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            position: absolute;
            top: 20px;
        }

        .btn:hover {
            background-color: #d62839;
        }

        .container.active {
            background-color: #111;
        }

        .container.active .card-wrapper {
            transform: translateY(0);
        }
    </style>
</head>

<body>
    <button class="btn" onclick="toggleCards()">Show Schools</button>

    <div class="container" id="container">
        <div class="card-wrapper">
            <div class="card">
                <img src="school1.jpg" alt="School 1" />
            </div>
            <div class="card">
                <img src="school2.jpg" alt="School 2" />
            </div>
            <div class="card">
                <img src="school3.jpg" alt="School 3" />
            </div>
        </div>
    </div>

    <script>
        function toggleCards() {
            const container = document.getElementById('container');
            container.classList.toggle('active');
        }
    </script>
</body>

</html>