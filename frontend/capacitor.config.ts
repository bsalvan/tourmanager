import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.bsalvan.tourmanager',
  appName: 'TourManager',
  webDir: 'dist'
  server: {
    // Si tu veux pointer directement vers ton serveur en dev (optionnel)
    url: 'https://bsalvan.freeboxos.fr:8900',
    cleartext: true
  }
};

export default config;
