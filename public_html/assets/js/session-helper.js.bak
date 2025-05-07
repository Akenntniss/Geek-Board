/**
 * Module pour gérer la persistance des données de session
 * Assure que l'ID du magasin est disponible même pendant les requêtes AJAX
 */
const SessionHelper = {
    /**
     * Initialise le module et vérifie l'ID du magasin
     */
    init() {
        console.log('Initialisation du SessionHelper');
        
        // Vérifier si l'ID du magasin est disponible sur la page
        this.checkAndStoreShopId();
        
        // Ajouter un écouteur pour mettre à jour l'ID du magasin lors d'un changement
        document.addEventListener('shop_change', this.handleShopChange.bind(this));
        
        // Initialiser l'intercepteur AJAX
        this.setupAjaxInterceptor();
    },
    
    /**
     * Vérifie si l'ID du magasin est disponible et le stocke localement
     */
    checkAndStoreShopId() {
        // Chercher l'ID du magasin dans les méta-tags ou dans les attributs data
        let shopId = null;
        
        // Méthode 1: Chercher dans un méta-tag
        const metaTag = document.querySelector('meta[name="shop-id"]');
        if (metaTag) {
            shopId = metaTag.getAttribute('content');
            console.log('ID du magasin trouvé dans meta tag:', shopId);
        }
        
        // Méthode 2: Chercher dans un attribut data sur le body ou un élément racine
        if (!shopId && document.body.hasAttribute('data-shop-id')) {
            shopId = document.body.getAttribute('data-shop-id');
            console.log('ID du magasin trouvé dans data-shop-id:', shopId);
        }
        
        // Méthode 3: Chercher dans un élément spécifique
        if (!shopId) {
            const shopElement = document.getElementById('current-shop-id');
            if (shopElement) {
                shopId = shopElement.textContent || shopElement.value;
                console.log('ID du magasin trouvé dans #current-shop-id:', shopId);
            }
        }
        
        // Si l'ID du magasin est trouvé, le stocker
        if (shopId) {
            this.storeShopId(shopId);
        } else {
            console.warn('Aucun ID de magasin trouvé sur la page');
        }
    },
    
    /**
     * Stocke l'ID du magasin dans le localStorage et sessionStorage
     * @param {string} shopId - ID du magasin
     */
    storeShopId(shopId) {
        if (!shopId) return;
        
        console.log('Stockage de l\'ID du magasin:', shopId);
        
        // Stocker dans localStorage pour une persistance à long terme
        localStorage.setItem('shop_id', shopId);
        
        // Stocker dans sessionStorage pour la session actuelle
        sessionStorage.setItem('shop_id', shopId);
        
        // Ajouter l'attribut data au body pour un accès facile
        document.body.setAttribute('data-shop-id', shopId);
    },
    
    /**
     * Gère le changement de magasin
     * @param {CustomEvent} event - Événement contenant le nouvel ID de magasin
     */
    handleShopChange(event) {
        if (event.detail && event.detail.shopId) {
            console.log('Changement de magasin détecté:', event.detail.shopId);
            this.storeShopId(event.detail.shopId);
        }
    },
    
    /**
     * Récupère l'ID du magasin stocké
     * @returns {string|null} ID du magasin ou null s'il n'est pas défini
     */
    getShopId() {
        return sessionStorage.getItem('shop_id') || localStorage.getItem('shop_id') || null;
    },
    
    /**
     * Configure l'intercepteur pour les requêtes AJAX
     * Ajoute automatiquement l'ID du magasin à toutes les requêtes
     */
    setupAjaxInterceptor() {
        // Sauvegarder la référence d'origine à XMLHttpRequest.open
        const originalOpen = XMLHttpRequest.prototype.open;
        const self = this;
        
        // Remplacer la méthode open par notre propre implémentation
        XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
            // Obtenir l'ID du magasin
            const shopId = self.getShopId();
            
            // Si l'ID du magasin est disponible et que l'URL est relative ou pointe vers le même domaine
            if (shopId && (url.startsWith('/') || url.startsWith('./') || url.startsWith('../') || 
                url.indexOf('//') === -1 || url.indexOf(window.location.hostname) !== -1)) {
                
                // Ajouter l'ID du magasin à l'URL si ce n'est pas déjà fait
                const separator = url.indexOf('?') !== -1 ? '&' : '?';
                if (url.indexOf('shop_id=') === -1) {
                    url = url + separator + 'shop_id=' + shopId;
                    console.log('ID du magasin ajouté à la requête AJAX:', url);
                }
            }
            
            // Appeler la méthode d'origine avec l'URL modifiée
            return originalOpen.call(this, method, url, async, user, password);
        };
        
        // Intercepter les requêtes fetch également
        const originalFetch = window.fetch;
        window.fetch = function(resource, options = {}) {
            // Obtenir l'ID du magasin
            const shopId = self.getShopId();
            
            // Si c'est une requête vers notre domaine et que l'ID du magasin est disponible
            if (shopId && typeof resource === 'string' && 
                (resource.startsWith('/') || resource.startsWith('./') || resource.startsWith('../') || 
                resource.indexOf('//') === -1 || resource.indexOf(window.location.hostname) !== -1)) {
                
                // Ajouter l'ID du magasin à l'URL si ce n'est pas déjà fait
                const separator = resource.indexOf('?') !== -1 ? '&' : '?';
                if (resource.indexOf('shop_id=') === -1) {
                    resource = resource + separator + 'shop_id=' + shopId;
                    console.log('ID du magasin ajouté à la requête fetch:', resource);
                }
                
                // Pour les requêtes POST avec un body JSON, ajouter l'ID du magasin au body également
                if (options && options.method === 'POST' && options.body) {
                    try {
                        // Si le body est une chaîne JSON, la parser et ajouter l'ID du magasin
                        if (typeof options.body === 'string' && options.headers && 
                            (options.headers['Content-Type'] === 'application/json' || 
                             (options.headers.get && options.headers.get('Content-Type') === 'application/json'))) {
                            
                            const bodyData = JSON.parse(options.body);
                            if (!bodyData.shop_id) {
                                bodyData.shop_id = shopId;
                                options.body = JSON.stringify(bodyData);
                                console.log('ID du magasin ajouté au body JSON de la requête');
                            }
                        }
                        // Si le body est un FormData, ajouter l'ID du magasin
                        else if (options.body instanceof FormData) {
                            if (!options.body.has('shop_id')) {
                                options.body.append('shop_id', shopId);
                                console.log('ID du magasin ajouté au FormData de la requête');
                            }
                        }
                    } catch (e) {
                        console.error('Erreur lors de la modification du body:', e);
                    }
                }
            }
            
            // Appeler la méthode d'origine avec l'URL modifiée et les options
            return originalFetch.call(window, resource, options);
        };
        
        console.log('Intercepteur AJAX configuré avec succès');
    }
};

// Initialiser le module quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    SessionHelper.init();
});

// Exporter le module pour une utilisation externe
window.SessionHelper = SessionHelper; 