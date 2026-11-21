document.addEventListener('alpine:init', () => {});

let deferredInstallPrompt = null;

function appIsStandalone() {
  return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

function updateInstallButtons() {
  document.querySelectorAll('[data-install-app]').forEach((button) => {
    button.hidden = appIsStandalone() || deferredInstallPrompt === null;
  });
}

window.addEventListener('beforeinstallprompt', (event) => {
  event.preventDefault();
  deferredInstallPrompt = event;
  updateInstallButtons();
});

window.addEventListener('appinstalled', () => {
  deferredInstallPrompt = null;
  updateInstallButtons();
});

document.addEventListener('click', async (event) => {
  const button = event.target.closest('[data-install-app]');
  if (!button || deferredInstallPrompt === null) {
    return;
  }

  button.disabled = true;
  deferredInstallPrompt.prompt();
  await deferredInstallPrompt.userChoice;
  deferredInstallPrompt = null;
  button.disabled = false;
  updateInstallButtons();
});

document.addEventListener('DOMContentLoaded', updateInstallButtons);
