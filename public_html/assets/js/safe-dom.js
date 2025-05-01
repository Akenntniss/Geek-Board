/**
 * Utilitaires pour la manipulation sécurisée du DOM
 * Permet d'éviter les erreurs "Cannot read properties of null (reading 'addEventListener')"
 */

// Fonction qui permet d'ajouter un écouteur d'événement de manière sécurisée
function safeAddEventListener(element, event, callback) {
    if (element) {
        element.addEventListener(event, callback);
        return true;
    }
    return false;
}

// Fonction pour obtenir un élément du DOM de manière sécurisée
function safeGetElement(selector) {
    return document.querySelector(selector);
}

// Fonction pour obtenir un élément par ID de manière sécurisée
function safeGetElementById(id) {
    return document.getElementById(id);
}

// Fonction pour ajouter un écouteur d'événement à un élément obtenu par ID
function safeAddEventListenerById(id, event, callback) {
    const element = document.getElementById(id);
    return safeAddEventListener(element, event, callback);
}

// Fonction pour exécuter une fonction sur un élément s'il existe
function withElement(selector, callback) {
    const element = document.querySelector(selector);
    if (element) {
        callback(element);
        return element;
    }
    return null;
}

// Fonction pour exécuter une fonction sur un élément obtenu par ID s'il existe
function withElementById(id, callback) {
    const element = document.getElementById(id);
    if (element) {
        callback(element);
        return element;
    }
    return null;
}

// Exporter les fonctions
window.SafeDOM = {
    addEventListener: safeAddEventListener,
    getElement: safeGetElement,
    getElementById: safeGetElementById,
    addEventListenerById: safeAddEventListenerById,
    withElement: withElement,
    withElementById: withElementById
}; 