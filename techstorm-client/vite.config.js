import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', //fichier Tailwind global
                 'resources/js/app.js',  //JS global

                 // Mes 5 fichiers spécifiques pour la page d'accueil
                'resources/css/Acceuil_css/style1.css',
                'resources/css/Acceuil_css/section-nouveautes.css',
                'resources/css/Acceuil_css/section-action.css',
                'resources/css/Acceuil_css/section-produits.css',

                //Fichier css spécifiques de la page à-propos
                'resources/css/About_css/about.css',

                //Fichiers css spécifiques de la page contact
                'resources/css/Contact_css/style.css',
                'resources/css/Contact_css/main.css',

                'resources/js/client.js',// JS des pages mon compte, paramètres et commandes
                'resources/js/auth.js'// JS des pages connexion et inscription
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
