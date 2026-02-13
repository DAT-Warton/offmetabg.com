// Force dark theme only
document.addEventListener('DOMContentLoaded', function() {
    var theme = 'dark';
    document.documentElement.setAttribute('data-theme', theme);
    document.body.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    
    var darkThemeStyle = document.getElementById('dark-theme-style');
    if (darkThemeStyle) {
        darkThemeStyle.disabled = false;
    }
});


