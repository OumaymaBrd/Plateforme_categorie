<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Culture - Andev Web</title>
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

        .navbar {
            padding: 2rem 4rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(10, 10, 10, 0.95);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #E50914;
        }

        .container {
            max-width: 1400px;
            margin: 120px auto 40px;
            padding: 0 2rem;
        }

        .articles-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-top: 2rem;
        }

        .article-card {
            background-color: #141414;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .article-card:hover {
            transform: translateY(-5px);
        }

        .article-image {
            width: 100%;
            height: 240px;
            object-fit: cover;
        }

        .article-content {
            padding: 1.5rem;
        }

        .article-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #ffffff;
        }

        .article-excerpt {
            color: #9ca3af;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .read-more {
            display: inline-block;
            color: #E50914;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            position: relative;
        }

        .read-more::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: #E50914;
            transition: width 0.3s ease;
        }

        .read-more:hover::after {
            width: 100%;
        }

        @media (max-width: 1024px) {
            .articles-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 1.5rem;
            }
            
            .articles-grid {
                grid-template-columns: 1fr;
            }

            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/" class="logo">Andev Web</a>
        <div class="nav-links">
            <a href="/works">works</a>
            <a href="/culture">culture</a>
            <a href="/news">news</a>
            <a href="/careers">careers</a>
            <a href="/contact">contact</a>
        </div>
    </nav>

    <div class="container">
        <div class="articles-grid">
            <!-- Article 1 -->
            <article class="article-card">
                <img src="images/photo.jpg" alt="Article 1" class="article-image">
                <div class="article-content">
                    <h2 class="article-title">L'évolution du Design Web en 2024</h2>
                    <p class="article-excerpt">Découvrez les dernières tendances en matière de design web et comment elles façonnent l'expérience utilisateur moderne...</p>
                    <a href="#" class="read-more">Continuer la lecture</a>
                </div>
            </article>

            <!-- Article 2 -->
            <article class="article-card">
                <img src="images/photo.jpg" alt="Article 2" class="article-image">
                <div class="article-content">
                    <h2 class="article-title">Intelligence Artificielle et Créativité</h2>
                    <p class="article-excerpt">Comment l'IA révolutionne le processus créatif dans le développement web et le design numérique...</p>
                    <a href="#" class="read-more">Continuer la lecture</a>
                </div>
            </article>

            <!-- Article 3 -->
            <article class="article-card">
                <img src="images/photo.jpg" alt="Article 3" class="article-image">
                <div class="article-content">
                    <h2 class="article-title">Performance et Accessibilité</h2>
                    <p class="article-excerpt">Les meilleures pratiques pour créer des sites web rapides et accessibles à tous les utilisateurs...</p>
                    <a href="#" class="read-more">Continuer la lecture</a>
                </div>
            </article>

            <!-- Article 4 -->
            <article class="article-card">
                <img src="images/photo.jpg" alt="Article 4" class="article-image">
                <div class="article-content">
                    <h2 class="article-title">Le Futur du Commerce en Ligne</h2>
                    <p class="article-excerpt">Les innovations technologiques qui transforment l'expérience d'achat en ligne...</p>
                    <a href="#" class="read-more">Continuer la lecture</a>
                </div>
            </article>

            <!-- Article 5 -->
            <article class="article-card">
                <img src="images/photo.jpg" alt="Article 5" class="article-image">
                <div class="article-content">
                    <h2 class="article-title">Sécurité Web en 2024</h2>
                    <p class="article-excerpt">Les défis et solutions pour protéger les applications web modernes...</p>
                    <a href="#" class="read-more">Continuer la lecture</a>
                </div>
            </article>

            <!-- Article 6 -->
            <article class="article-card">
                <img src="images/photo.jpg" alt="Article 6" class="article-image">
                <div class="article-content">
                    <h2 class="article-title">UX Design et Psychologie</h2>
                    <p class="article-excerpt">Comment la psychologie cognitive influence la conception d'interfaces utilisateur...</p>
                    <a href="#" class="read-more">Continuer la lecture</a>
                </div>
            </article>
        </div>
    </div>
</body>
</html>