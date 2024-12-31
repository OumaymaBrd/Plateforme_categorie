<!DOCTYPE html>
<html lang="en" >
<head>
  <meta charset="UTF-8">
  <title>Andev Web</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
<link rel="stylesheet" href="../style/style_principale.css">


</head>
<body>
<div class="container">
    <header class="header">
        <a class="header__logo">Andev Web</a>
        <nav class="header__menu">
            <ul class="header__menu__list">
                <li class="header__menu__item"><a class="header__menu__link">works</a></li>
                <li class="header__menu__item"><a class="header__menu__link">culture</a></li>
                <li class="header__menu__item"><a class="header__menu__link">news</a></li>
                <li class="header__menu__item"><a class="header__menu__link">careers</a></li>
                <li class="header__menu__item"><a class="header__menu__link">contact</a></li>
            </ul>
        </nav>
    </header>
    <main class="sliders-container">
        <ul class="pagination">
            <li class="pagination__item"><a class="pagination__button"></a></li>
            <li class="pagination__item"><a class="pagination__button"></a></li>
            <li class="pagination__item"><a class="pagination__button"></a></li>
            <li class="pagination__item"><a class="pagination__button"></a></li>
        </ul>
    </main>
    <footer class="footer">
        <nav class="footer__menu">
            <ul class="footer__menu__list">
                <li class="footer__menu__item"><a class="footer__menu__link">facebook</a></li>
                <li class="footer__menu__item"><a class="footer__menu__link">dribbble</a></li>
                <li class="footer__menu__item"><a class="footer__menu__link">instagram</a></li>
            </ul>
        </nav>
    </footer>
</div>
  <script src='https://rawgit.com/lmgonzalves/momentum-slider/master/dist/momentum-slider.min.js'></script><script  src="./script.js"></script>
  <script>
    (function() {

var slidersContainer = document.querySelector('.sliders-container');

var msNumbers = new MomentumSlider({
    el: slidersContainer,
    cssClass: 'ms--numbers',
    range: [1, 4],
    rangeContent: function (i) {
        return '0' + i;
    },
    style: {
        transform: [{scale: [0.4, 1]}],
        opacity: [0, 1]
    },
    interactive: false
});

var titles = [
    'King of the Ring Fight',
    'Sound of Streets',
    'Urban Fashion',
    'Windy Sunset'
];
var msTitles = new MomentumSlider({
    el: slidersContainer,
    cssClass: 'ms--titles',
    range: [0, 3],
    rangeContent: function (i) {
        return '<h3>'+ titles[i] +'</h3>';
    },
    vertical: true,
    reverse: true,
    style: {
        opacity: [0, 1]
    },
    interactive: false
});

var msLinks = new MomentumSlider({
    el: slidersContainer,
    cssClass: 'ms--links',
    range: [0, 3],
    rangeContent: function () {
        return '<a class="ms-slide__link">View Case</a>';
    },
    vertical: true,
    interactive: false
});

var pagination = document.querySelector('.pagination');
var paginationItems = [].slice.call(pagination.children);

var msImages = new MomentumSlider({
    el: slidersContainer,
    cssClass: 'ms--images',
    range: [0, 3],
    rangeContent: function () {
        return '<div class="ms-slide__image-container"><div class="ms-slide__image"></div></div>';
    },
    sync: [msNumbers, msTitles, msLinks],
    style: {
        '.ms-slide__image': {
            transform: [{scale: [1.5, 1]}]
        }
    },
    change: function(newIndex, oldIndex) {
        if (typeof oldIndex !== 'undefined') {
            paginationItems[oldIndex].classList.remove('pagination__item--active');
        }
        paginationItems[newIndex].classList.add('pagination__item--active');
    }
});
pagination.addEventListener('click', function(e) {
    if (e.target.matches('.pagination__button')) {
        var index = paginationItems.indexOf(e.target.parentNode);
        msImages.select(index);
    }
});

})();            
  </script>
</body>
</html>
            