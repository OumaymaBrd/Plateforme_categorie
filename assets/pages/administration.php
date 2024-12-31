<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrateur</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #0a0a0a;
            color: #ffffff;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
        }

        .search-bar {
            display: flex;
            align-items: center;
            background-color: #141414;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 0.5rem 1rem;
        }

        .search-bar input {
            background: none;
            border: none;
            color: #fff;
            margin-left: 0.5rem;
            outline: none;
        }

        .card {
            background-color: #141414;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .stats-chart {
            height: 200px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
        }

        .stats-bar {
            width: 12%;
            background-color: #E50914;
            transition: height 0.3s ease;
        }

        .tabs {
            display: flex;
            margin-bottom: 1rem;
        }

        .tab {
            background-color: #141414;
            border: 1px solid #333;
            color: #fff;
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .tab:hover, .tab:focus {
            background-color: #1f1f1f;
        }

        .tab.active {
            background-color: #E50914;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .btn {
            background-color: #E50914;
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #b8070f;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #141414;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #333;
            width: 80%;
            max-width: 500px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: #fff;
            text-decoration: none;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.5rem;
            background-color: #0a0a0a;
            border: 1px solid #333;
            color: #fff;
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .stats-chart {
                height: 150px;
            }

            .tabs {
                flex-direction: column;
            }

            .tab {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Dashboard Administrateur</h1>
            <div class="search-bar">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" placeholder="Rechercher un article...">
            </div>
        </header>

        <div class="card">
            <h2 class="card-title">Statistiques des Visites</h2>
            <div class="stats-chart">
                <div class="stats-bar" style="height: 80%;"></div>
                <div class="stats-bar" style="height: 60%;"></div>
                <div class="stats-bar" style="height: 40%;"></div>
                <div class="stats-bar" style="height: 70%;"></div>
                <div class="stats-bar" style="height: 50%;"></div>
                <div class="stats-bar" style="height: 90%;"></div>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="openTab(event, 'admins')">Administrateurs</button>
            <button class="tab" onclick="openTab(event, 'users')">Utilisateurs</button>
            <button class="tab" onclick="openTab(event, 'categories')">Catégories</button>
        </div>

        <div id="admins" class="tab-content active">
            <div class="card">
                <h2 class="card-title">Gestion des Administrateurs</h2>
                <button class="btn" onclick="openModal('adminModal')">Ajouter un Admin</button>
            </div>
        </div>

        <div id="users" class="tab-content">
            <div class="card">
                <h2 class="card-title">Gestion des Utilisateurs</h2>
                <button class="btn" onclick="openModal('userModal')">Bloquer un Utilisateur</button>
            </div>
        </div>

        <div id="categories" class="tab-content">
            <div class="card">
                <h2 class="card-title">Gestion des Catégories</h2>
                <button class="btn" onclick="openModal('categoryModal')">Ajouter une Catégorie</button>
            </div>
        </div>
    </div>

    <!-- Modal pour ajouter un admin -->
    <div id="adminModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('adminModal')">&times;</span>
            <h2>Ajouter un Administrateur</h2>
            <form>
                <div class="form-group">
                    <label for="adminName">Nom complet</label>
                    <input type="text" id="adminName" required>
                </div>
                <div class="form-group">
                    <label for="adminEmail">Email</label>
                    <input type="email" id="adminEmail" required>
                </div>
                <div class="form-group">
                    <label for="adminPassword">Mot de passe</label>
                    <input type="password" id="adminPassword" required>
                </div>
                <button type="submit" class="btn">Ajouter</button>
            </form>
        </div>
    </div>

    <!-- Modal pour bloquer un utilisateur -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('userModal')">&times;</span>
            <h2>Bloquer un Utilisateur</h2>
            <form>
                <div class="form-group">
                    <label for="userEmail">Email de l'utilisateur</label>
                    <input type="email" id="userEmail" required>
                </div>
                <div class="form-group">
                    <label for="blockReason">Raison du blocage</label>
                    <input type="text" id="blockReason" required>
                </div>
                <button type="submit" class="btn">Bloquer l'utilisateur</button>
            </form>
        </div>
    </div>

    <!-- Modal pour ajouter une catégorie -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('categoryModal')">&times;</span>
            <h2>Ajouter une Catégorie</h2>
            <form>
                <div class="form-group">
                    <label for="categoryName">Nom de la catégorie</label>
                    <input type="text" id="categoryName" required>
                </div>
                <div class="form-group">
                    <label for="categoryDesc">Description</label>
                    <input type="text" id="categoryDesc" required>
                </div>
                <button type="submit" class="btn">Ajouter</button>
            </form>
        </div>
    </div>

    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tab");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target.className === "modal") {
                event.target.style.display = "none";
            }
        }
    </script>
</body>
</html>