function updateThemeIcon(theme) {
    var themeIcon = document.getElementById('theme-icon');
    if (!themeIcon) {
        return;
    }

    var iconSun = document.body.dataset.iconSun || '';
    var iconMoon = document.body.dataset.iconMoon || '';

    if (theme === 'dark') {
        themeIcon.innerHTML = iconSun;
    } else {
        themeIcon.innerHTML = iconMoon;
    }
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    document.body.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);

    var darkThemeStyle = document.getElementById('dark-theme-style');
    if (darkThemeStyle) {
        darkThemeStyle.disabled = (theme !== 'dark');
    }

    updateThemeIcon(theme);
}

function toggleTheme() {
    var currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    var newTheme = currentTheme === 'light' ? 'dark' : 'light';
    applyTheme(newTheme);
}

document.addEventListener('DOMContentLoaded', function() {
    var savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);
});
