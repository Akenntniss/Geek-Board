/**
 * Gestionnaire de thème (clair/sombre)
 * 
 * Ce script gère la bascule entre le mode jour et le mode nuit
 * avec support des préférences système et stockage local
 */

class ThemeSwitcher {
  constructor() {
    this.darkMode = false;
    this.toggleButton = null;
    this.sunIcon = '<i class="fas fa-sun"></i>';
    this.moonIcon = '<i class="fas fa-moon"></i>';
    this.storageKey = 'theme_preference';
    this.observers = [];
  }

  /**
   * Initialise le gestionnaire de thème
   * @param {string|Element} toggleButtonSelector - Sélecteur CSS ou élément DOM du bouton de bascule
   * @param {Object} options - Options de configuration
   */
  init(toggleButtonSelector, options = {}) {
    // Options par défaut
    const defaultOptions = {
      storageKey: 'theme_preference',
      useSystemPreference: true,
      initialTheme: null, // 'light', 'dark', null (auto)
      darkModeClass: 'dark-mode',
      attribute: 'data-theme', // attribut HTML à définir
      persist: true, // sauvegarder dans localStorage
      onChange: null // callback lorsque le thème change
    };

    this.options = { ...defaultOptions, ...options };
    this.storageKey = this.options.storageKey;
    
    // Récupérer le bouton de bascule
    if (typeof toggleButtonSelector === 'string') {
      this.toggleButton = document.querySelector(toggleButtonSelector);
    } else if (toggleButtonSelector instanceof Element) {
      this.toggleButton = toggleButtonSelector;
    }

    // Déterminer le thème initial
    this.setInitialTheme();
    
    // Ajouter les écouteurs d'événements
    this.addEventListeners();
    
    // Modifier l'apparence du bouton
    this.updateToggleButton();
    
    // Si on surveille les changements de préférence système
    if (this.options.useSystemPreference) {
      this.watchSystemPreference();
    }

    return this;
  }

  /**
   * Détermine et applique le thème initial
   */
  setInitialTheme() {
    // Priorité 1: Thème forcé par les options
    if (this.options.initialTheme) {
      this.darkMode = this.options.initialTheme === 'dark';
      this.applyTheme();
      return;
    }
    
    // Priorité 2: Préférence sauvegardée localement
    if (this.options.persist) {
      const savedPreference = localStorage.getItem(this.storageKey);
      if (savedPreference !== null) {
        this.darkMode = savedPreference === 'dark';
        this.applyTheme();
        return;
      }
    }
    
    // Priorité 3: Préférence système
    if (this.options.useSystemPreference) {
      this.darkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
      this.applyTheme();
      return;
    }
    
    // Par défaut: mode clair
    this.darkMode = false;
    this.applyTheme();
  }

  /**
   * Applique le thème actuel au document
   */
  applyTheme() {
    const theme = this.darkMode ? 'dark' : 'light';
    
    // Appliquer la classe au body
    if (this.options.darkModeClass) {
      document.body.classList.toggle(this.options.darkModeClass, this.darkMode);
    }
    
    // Appliquer l'attribut au document
    if (this.options.attribute) {
      document.documentElement.setAttribute(this.options.attribute, theme);
    }
    
    // Sauvegarder la préférence
    if (this.options.persist) {
      localStorage.setItem(this.storageKey, theme);
    }
    
    // Mettre à jour l'apparence du bouton
    this.updateToggleButton();
    
    // Exécuter le callback si défini
    if (typeof this.options.onChange === 'function') {
      this.options.onChange(theme);
    }
    
    // Notifier les observateurs
    this.notifyObservers(theme);
  }

  /**
   * Met à jour l'apparence du bouton de bascule
   */
  updateToggleButton() {
    if (!this.toggleButton) return;
    
    // Mettre à jour l'icône
    this.toggleButton.innerHTML = this.darkMode ? this.sunIcon : this.moonIcon;
    
    // Ajouter un attribut aria pour l'accessibilité
    this.toggleButton.setAttribute('aria-label', 
      this.darkMode ? 'Passer au mode clair' : 'Passer au mode sombre'
    );
    
    // Ajouter un titre au survol
    this.toggleButton.title = this.darkMode ? 'Mode clair' : 'Mode sombre';
  }

  /**
   * Ajoute les écouteurs d'événements
   */
  addEventListeners() {
    if (!this.toggleButton) return;
    
    this.toggleButton.addEventListener('click', () => {
      // Ajouter une animation au clic
      this.toggleButton.classList.add('theme-toggle-animate');
      
      // Enlever la classe d'animation après la fin
      setTimeout(() => {
        this.toggleButton.classList.remove('theme-toggle-animate');
      }, 500);
      
      // Basculer le mode
      this.toggle();
    });
  }

  /**
   * Surveille les changements de préférence système
   */
  watchSystemPreference() {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    
    // Gestionnaire de changement
    const handleChange = (e) => {
      // Uniquement si on n'a pas de préférence explicite sauvegardée
      if (!localStorage.getItem(this.storageKey)) {
        this.darkMode = e.matches;
        this.applyTheme();
      }
    };
    
    // Ajouter l'écouteur avec la méthode appropriée
    if (mediaQuery.addEventListener) {
      mediaQuery.addEventListener('change', handleChange);
    } else if (mediaQuery.addListener) {
      // Pour la compatibilité avec les anciens navigateurs
      mediaQuery.addListener(handleChange);
    }
  }

  /**
   * Bascule entre les modes clair et sombre
   */
  toggle() {
    this.darkMode = !this.darkMode;
    this.applyTheme();
    return this.darkMode;
  }

  /**
   * Définit explicitement le mode
   * @param {boolean} isDark - Vrai pour activer le mode sombre
   */
  setDarkMode(isDark) {
    if (this.darkMode !== isDark) {
      this.darkMode = isDark;
      this.applyTheme();
    }
    return this.darkMode;
  }

  /**
   * Ajoute un observateur qui sera notifié lors des changements de thème
   * @param {Function} callback - Fonction à appeler lors du changement
   */
  addObserver(callback) {
    if (typeof callback === 'function') {
      this.observers.push(callback);
    }
    return this;
  }

  /**
   * Notifie tous les observateurs
   * @param {string} theme - Le thème actuel ('light' ou 'dark')
   */
  notifyObservers(theme) {
    this.observers.forEach(callback => callback(theme));
  }
}

// Créer une instance et l'exporter
const themeSwitcher = new ThemeSwitcher();

// Initialisation automatique si l'élément existe
document.addEventListener('DOMContentLoaded', () => {
  const toggleButton = document.getElementById('themeToggle') || document.getElementById('toggleDarkMode');
  if (toggleButton) {
    themeSwitcher.init(toggleButton, {
      useSystemPreference: true,
      persist: true
    });
  }
});

// Exposer l'instance globalement
window.themeSwitcher = themeSwitcher; 