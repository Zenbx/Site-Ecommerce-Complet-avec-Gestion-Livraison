// resources/js/client.js - Script pour les pages client (compte, commandes, paramètres)

document.addEventListener('DOMContentLoaded', async () => {
    const apiUrl = import.meta.env.VITE_API_URL;
    
    // Récupérer le token utilisateur depuis localStorage
    const userToken = localStorage.getItem('user_token');

    // Si pas de token, rediriger vers connexion
    if (!userToken) {
        console.log('Pas de token trouvé, redirection vers connexion');
        alert('Pas de token trouvé, redirection vers connexion');
        //window.location.href = '/connexion';
        return;
    }

    console.log('Token trouvé:', userToken.substring(0, 20) + '...');

    // Fonction pour formater la date
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR');
    }

    // Fonction pour mettre à jour l'affichage des informations utilisateur
    function updateUserDisplay(user) {
        if (!user) {
            console.warn('Aucune donnée utilisateur à afficher');
            return;
        }

        console.log('Mise à jour affichage utilisateur:', user);

        // Mettre à jour le nom d'utilisateur dans la sidebar
        const usernameEl = document.querySelector('.username');
        const sidebarUsernameEl = document.getElementById('sidebar-username');
        
        if (usernameEl) {
            usernameEl.textContent = user.name || 'Utilisateur';
        }
        if (sidebarUsernameEl) {
            sidebarUsernameEl.textContent = user.name || 'Utilisateur';
        }

        // Mettre à jour l'image de profil partout
        const userAvatar = document.getElementById('user-avatar');
        const sidebarAvatar = document.getElementById('sidebar-avatar');
        const settingsAvatarPreview = document.getElementById('settings-avatar-preview');
        
        // Gérer le chemin de l'image
        let avatarSrc = "/images/default-avatar.png";
        if (user.avatar || user.photo || user.image) {
            const photoPath = user.avatar || user.photo || user.image;
            avatarSrc = photoPath.startsWith('http') ? photoPath : `${apiUrl}/${photoPath}`;
        }

        if (userAvatar) userAvatar.src = avatarSrc;
        if (sidebarAvatar) sidebarAvatar.src = avatarSrc;
        if (settingsAvatarPreview) settingsAvatarPreview.src = avatarSrc;

        // Mettre à jour les informations personnelles (Page Compte)
        const nameValue = document.getElementById('user-name');
        const emailValue = document.getElementById('user-email');
        const phoneValue = document.getElementById('user-phone');
        const addressValue = document.getElementById('user-address');
        const dateValue = document.getElementById('user-date');

        if (nameValue) nameValue.textContent = user.name || '-';
        if (emailValue) emailValue.textContent = user.email || '-';
        if (phoneValue) phoneValue.textContent = user.phone || user.telephone || '-';
        if (addressValue) addressValue.textContent = user.address || user.adresse || '-';
        if (dateValue) dateValue.textContent = formatDate(user.created_at) || '-';

        // Mettre à jour les champs de formulaire (Page Paramètres)
        const settingsFullname = document.getElementById('settings-fullname');
        const settingsEmail = document.getElementById('settings-email');
        const settingsPhone = document.getElementById('settings-phone');
        const settingsAddress = document.getElementById('settings-address');

        if (settingsFullname) settingsFullname.value = user.name || '';
        if (settingsEmail) settingsEmail.value = user.email || '';
        if (settingsPhone) settingsPhone.value = user.phone || user.telephone || '';
        if (settingsAddress) settingsAddress.value = user.address || user.adresse || '';
    }

    // Fonction pour récupérer les données utilisateur depuis l'API
    async function fetchUserData() {
        try {
            let authHeader = userToken;
            if (!userToken.startsWith('Bearer ')) {
                authHeader = `Bearer ${userToken}`;
            }
            
            const response = await fetch(`${apiUrl}/api/client/profile`, {
                method: 'GET',
                headers: {
                    'Authorization': authHeader,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();
                const userData = data.user || data.data || data;
                localStorage.setItem('user_data', JSON.stringify(userData));
                return userData;
            } else if (response.status === 401) {
                return 'expired';
            } else {
                return `error_${response.status}`;
            }
        } catch (error) {
            return 'network_error';
        }
    }

    // --- LOGIQUE DE MISE À JOUR ---
    const accountForm = document.getElementById('accountForm');
    if (accountForm) {
        accountForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const fullname = document.getElementById('settings-fullname').value;
            const email = document.getElementById('settings-email').value;
            const phone = document.getElementById('settings-phone').value;
            const address = document.getElementById('settings-address').value;
            const password = document.getElementById('settings-password').value;

            const authHeader = userToken.startsWith('Bearer ') ? userToken : `Bearer ${userToken}`;

            try {
                // 1. Mise à jour des informations générales
                const profileUpdate = await fetch(`${apiUrl}/api/client/profile`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': authHeader,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: fullname,
                        email: email,
                        tel: phone, // Changé de 'phone' à 'tel'
                        address: address
                    })
                });

                if (!profileUpdate.ok) {
                    const err = await profileUpdate.json();
                    throw new Error(err.message || 'Erreur lors de la mise à jour du profil');
                }

                // 2. Mise à jour du mot de passe si rempli
                if (password) {
                    const passwordUpdate = await fetch(`${apiUrl}/api/client/profile/password`, {
                        method: 'PUT',
                        headers: {
                            'Authorization': authHeader,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ password: password })
                    });

                    if (!passwordUpdate.ok) {
                        const err = await passwordUpdate.json();
                        throw new Error(err.message || 'Erreur lors de la mise à jour du mot de passe');
                    }
                }

                alert('Informations mises à jour avec succès !');
                const freshUser = await fetchUserData();
                if (freshUser && typeof freshUser === 'object') {
                    updateUserDisplay(freshUser);
                }

            } catch (error) {
                alert('Erreur : ' + error.message);
                console.error('Erreur de mise à jour:', error);
            }
        });
    }

    // --- LOGIQUE PHOTO DE PROFIL ---
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            // Aperçu immédiat
            const reader = new FileReader();
            reader.onload = (ev) => {
                const preview = document.getElementById('settings-avatar-preview');
                if (preview) preview.src = ev.target.result;
            };
            reader.readAsDataURL(file);

            // Upload
            const formData = new FormData();
            formData.append('photo', file);

            const authHeader = userToken.startsWith('Bearer ') ? userToken : `Bearer ${userToken}`;

            try {
                const response = await fetch(`${apiUrl}/api/client/profile/photo`, {
                    method: 'POST',
                    headers: {
                        'Authorization': authHeader,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                if (response.ok) {
                    const data = await response.json();
                    alert('Photo de profil mise à jour !');
                    
                    // Si l'API renvoie l'utilisateur à jour, on l'utilise
                    const updatedUser = data.user || data.data || (data.id ? data : null);
                    if (updatedUser) {
                        localStorage.setItem('user_data', JSON.stringify(updatedUser));
                        updateUserDisplay(updatedUser);
                    } else {
                        // Sinon on recharge
                        const freshUser = await fetchUserData();
                        if (freshUser) updateUserDisplay(freshUser);
                    }
                } else {
                    const err = await response.json();
                    alert('Erreur upload : ' + (err.message || 'Erreur inconnue'));
                }
            } catch (error) {
                console.error('Erreur upload photo:', error);
                alert('Erreur réseau lors de l\'envoi de la photo.');
            }
        });
    }

    // --- INITIALISATION ---
    const cachedUserData = localStorage.getItem('user_data');
    let hasCachedData = false;
    
    if (cachedUserData) {
        try {
            const user = JSON.parse(cachedUserData);
            updateUserDisplay(user);
            hasCachedData = true;
        } catch (e) {}
    }

    const result = await fetchUserData();
    if (result && typeof result === 'object') {
        updateUserDisplay(result);
    } else if (result === 'expired' && !hasCachedData) {
        window.location.href = '/connexion';
    }

    // Déconnexion
    const logoutBtn = document.querySelector('.logout');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            localStorage.removeItem('user_token');
            localStorage.removeItem('user_data');
            window.location.href = '/connexion';
        });
    }

    // Exposer les fonctions pour utilisation externe si nécessaire
    window.fetchUserData = fetchUserData;
    window.updateUserDisplay = updateUserDisplay;
});
