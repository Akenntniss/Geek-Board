/**
 * utils.js
 * Bibliothèque de fonctions utilitaires pour GeekBoard
 */

// Namespace pour éviter les conflits
const GeekUtils = {
    
    /**
     * Formate un numéro de téléphone en format international français
     * @param {string} phone - Numéro de téléphone à formater
     * @return {string} - Numéro formaté
     */
    formatPhoneNumber: function(phone) {
        if (!phone) return '';
        
        // Supprimer tous les caractères non numériques
        let cleaned = phone.replace(/\D/g, '');
        
        // S'assurer que le numéro est au format français
        if (cleaned.length === 10 && cleaned.startsWith('0')) {
            cleaned = '33' + cleaned.substring(1);
        }
        
        // Ajouter le + si nécessaire
        if (!cleaned.startsWith('+')) {
            cleaned = '+' + cleaned;
        }
        
        return cleaned;
    },
    
    /**
     * Formate une date au format français (JJ/MM/AAAA)
     * @param {string|Date} date - Date à formater
     * @return {string} - Date formatée
     */
    formatDate: function(date) {
        if (!date) return '';
        
        const d = new Date(date);
        const day = d.getDate().toString().padStart(2, '0');
        const month = (d.getMonth() + 1).toString().padStart(2, '0');
        const year = d.getFullYear();
        
        return `${day}/${month}/${year}`;
    },
    
    /**
     * Formate un prix en euros
     * @param {number|string} price - Prix à formater
     * @return {string} - Prix formaté
     */
    formatPrice: function(price) {
        if (price === null || price === undefined || price === '') return '';
        
        const numPrice = parseFloat(price);
        return numPrice.toLocaleString('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        });
    },
    
    /**
     * Génère un ID unique
     * @return {string} - ID unique
     */
    generateUniqueId: function() {
        return 'id_' + Math.random().toString(36).substr(2, 9);
    },
    
    /**
     * Copie le texte dans le presse-papier
     * @param {string} text - Texte à copier
     * @return {Promise} - Promise résolue si la copie a réussi
     */
    copyToClipboard: function(text) {
        return navigator.clipboard.writeText(text)
            .then(() => {
                return true;
            })
            .catch(err => {
                console.error('Erreur lors de la copie dans le presse-papier:', err);
                return false;
            });
    },
    
    /**
     * Détermine si l'appareil est un mobile
     * @return {boolean} - true si l'appareil est un mobile
     */
    isMobileDevice: function() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    },
    
    /**
     * Joue un son de notification
     * @param {string} soundName - Nom du fichier son (sans extension)
     */
    playSound: function(soundName = 'notification') {
        try {
            const audio = new Audio(`/assets/sounds/${soundName}.mp3`);
            audio.play().catch(e => console.log('Erreur lors de la lecture du son:', e));
        } catch (e) {
            console.log('Erreur lors de la lecture du son:', e);
        }
    },
    
    /**
     * Affiche une notification toast
     * @param {string} message - Message à afficher
     * @param {string} type - Type de notification (success, warning, error, info)
     * @param {number} duration - Durée d'affichage en ms
     */
    showToast: function(message, type = 'info', duration = 3000) {
        // Vérifier si l'élément toast-container existe, sinon le créer
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.position = 'fixed';
            container.style.bottom = '20px';
            container.style.right = '20px';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        
        // Créer le toast
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.minWidth = '250px';
        toast.style.margin = '10px 0';
        toast.style.padding = '15px';
        toast.style.borderRadius = '4px';
        toast.style.boxShadow = '0 0 10px rgba(0,0,0,0.2)';
        toast.style.backgroundColor = this.getToastColor(type);
        toast.style.color = '#fff';
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease-in-out';
        
        // Ajouter le message
        toast.textContent = message;
        
        // Ajouter le toast au container
        container.appendChild(toast);
        
        // Afficher le toast avec une animation
        setTimeout(() => {
            toast.style.opacity = '1';
        }, 10);
        
        // Supprimer le toast après la durée spécifiée
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                container.removeChild(toast);
            }, 300);
        }, duration);
    },
    
    /**
     * Obtenir la couleur de fond pour un type de toast
     * @param {string} type - Type de toast
     * @return {string} - Couleur de fond
     */
    getToastColor: function(type) {
        switch (type) {
            case 'success': return '#4caf50';
            case 'warning': return '#ff9800';
            case 'error': return '#f44336';
            case 'info':
            default: return '#2196f3';
        }
    }
};

// Exporter pour les environnements qui supportent les modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GeekUtils;
} 