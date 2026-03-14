/**
 * Funcționalitate toggle tema - CRM ANR Bihor
 * Gestionează schimbarea între tema întunecată și luminoasă
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inițializare iconițe Lucide
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Funcționalitate toggle tema
    const themeToggle = document.getElementById('theme-toggle');
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            
            if (isDark) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
            
            // Reinițializează iconițele după schimbarea temei
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    }
});
