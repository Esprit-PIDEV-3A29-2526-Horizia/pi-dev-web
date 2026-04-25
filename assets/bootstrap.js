// assets/bootstrap.js

// Ce fichier est le point d'entrée principal pour Stimulus
// Il importe le fichier de démarrage Stimulus

import { startStimulusApp } from '@symfony/stimulus-bridge';

// Enregistre les contrôleurs Stimulus depuis controllers.json et le dossier controllers/
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));