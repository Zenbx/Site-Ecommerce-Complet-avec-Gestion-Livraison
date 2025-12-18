// resources/js/auth-guard.js
// Utilitaire pour vérifier l'authentification et rediriger si nécessaire

/**
 * Vérifie si l'utilisateur est authentifié.
 * @returns {boolean} true si authentifié, false sinon
 */
export function isAuthenticated() {
    const token = localStorage.getItem('user_token');
    return token !== null && token !== '';
}

/**
 * Vérifie l'authentification et redirige vers la connexion si non authentifié.
 * @param {string} returnUrl - URL de retour après connexion (optionnel)
 * @returns {boolean} true si authentifié et peut continuer, false si redirection
 */
export function requireAuth(returnUrl = null) {
    if (!isAuthenticated()) {
        // Optionnel : sauvegarder l'URL de retour pour rediriger après connexion
        if (returnUrl) {
            localStorage.setItem('redirect_after_login', returnUrl);
        }
        window.location.href = '/connexion';
        return false;
    }
    return true;
}

/**
 * Fonction globale pour les boutons avec onclick inline
 * @param {string} targetUrl - URL de destination si authentifié
 */
window.goIfAuthenticated = function(targetUrl) {
    if (requireAuth(targetUrl)) {
        window.location.href = targetUrl;
    }
};

/**
 * Fonction globale pour les actions panier
 * @param {Function} callback - Fonction à exécuter si authentifié
 */
window.doIfAuthenticated = function(callback) {
    if (requireAuth()) {
        callback();
    }
};
