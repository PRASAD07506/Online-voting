document.addEventListener('DOMContentLoaded', function () {
    var root = document.documentElement;
    var storedTheme = localStorage.getItem('voting-theme');
    var initialTheme = storedTheme || 'light';

    root.setAttribute('data-theme', initialTheme);

    if (!document.body || !document.body.classList.contains('app-shell')) {
        return;
    }

    if (document.querySelector('.theme-toggle')) {
        return;
    }

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'theme-toggle';
    button.setAttribute('aria-label', 'Toggle dark and light theme');

    var label = document.createElement('span');
    label.className = 'theme-toggle-label';
    label.textContent = initialTheme === 'dark' ? 'Dark' : 'Light';

    var track = document.createElement('span');
    track.className = 'theme-toggle-track';

    var sun = document.createElement('span');
    sun.className = 'theme-toggle-sun';

    var stars = document.createElement('span');
    stars.className = 'theme-toggle-stars';

    var thumb = document.createElement('span');
    thumb.className = 'theme-toggle-thumb';

    track.appendChild(sun);
    track.appendChild(stars);
    track.appendChild(thumb);
    button.appendChild(label);
    button.appendChild(track);
    document.body.appendChild(button);

    var syncThemeLabel = function () {
        var nextTheme = root.getAttribute('data-theme') === 'dark' ? 'Dark' : 'Light';
        label.textContent = nextTheme;
    };

    button.addEventListener('click', function () {
        var currentTheme = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        var nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', nextTheme);
        localStorage.setItem('voting-theme', nextTheme);
        syncThemeLabel();
    });

    syncThemeLabel();
});
