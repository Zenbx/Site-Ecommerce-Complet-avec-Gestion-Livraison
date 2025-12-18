// resources/js/auth.js

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const apiUrl = import.meta.env.VITE_API_URL;

    // --- LOGIQUE DE CONNEXION ---
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // Empêche le rechargement de la page

            // 1. Récupération des données
            const credentials = {
                email: document.getElementById('login-email').value,
                password: document.getElementById('login-password').value
            };

            console.log('Tentative de connexion avec:', { email: credentials.email });

            try {
                // 2. Envoi à l'API
                const response = await fetch(`${apiUrl}/api/auth/client/login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(credentials)
                });

                const data = await response.json();
                console.log('Réponse API:', response.status, data);

                // 3. Gestion de la réponse
                if (response.ok) {
                    // Succès : on stocke le token et les infos utilisateur
                    // On cherche le token dans plusieurs champs communs
                    const token = data.token || data.access_token || data.bearer_token || data.key || (data.data && data.data.token);
                    
                    if (token) {
                        localStorage.setItem('user_token', token);
                        // On cherche l'utilisateur dans fields communs aussi
                        const user = data.user || data.data || data;
                        if (user && typeof user === 'object') {
                            localStorage.setItem('user_data', JSON.stringify(user));
                        }
                        alert('Connexion réussie !');
                        window.location.href = '/account'; // Redirection vers le compte
                    } else {
                        console.error('Token non trouvé dans la réponse. Voici l\'objet reçu :', data);
                        alert('Erreur : Token non reçu. Champs reçus : ' + Object.keys(data).join(', '));
                    }
                } else {
                    // Erreur : afficher les détails de l'erreur de validation
                    let errorMessage = data.message || 'Identifiants invalides';
                    
                    // Si l'API renvoie des erreurs de validation détaillées
                    if (data.errors) {
                        const errorDetails = Object.entries(data.errors)
                            .map(([field, messages]) => `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
                            .join('\n');
                        errorMessage += '\n\n' + errorDetails;
                    }
                    
                    console.error('Erreur de connexion:', errorMessage);
                    alert('Erreur : ' + errorMessage);
                }
            } catch (error) {
                console.error('Erreur réseau :', error);
                alert('Erreur réseau. Vérifiez que le serveur API est accessible.');
            }
        });
    }

    // --- LOGIQUE D'INSCRIPTION ---
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const password = document.getElementById('register-password').value;
            const passwordConfirmation = document.getElementById('register-password-confirmation').value;

            // Vérifier que les mots de passe correspondent
            if (password !== passwordConfirmation) {
                alert('Les mots de passe ne correspondent pas.');
                return;
            }

            const newUser = {
                name: document.getElementById('register-pseudo').value,
                email: document.getElementById('register-email').value,
                password: password,
                password_confirmation: passwordConfirmation,
                address: document.getElementById('register-address').value,
            };

            try {
                const response = await fetch(`${apiUrl}/api/auth/client/register`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(newUser)
                });

                const data = await response.json();

                if (response.ok) {
                    alert('Inscription réussie ! Vous pouvez vous connecter.');
                    window.location.href = '/account';
                } else {
                    alert('Erreur : ' + JSON.stringify(data.errors));
                }
            } catch (error) {
                console.error('Erreur inscription :', error);
            }
        });
    }
});
