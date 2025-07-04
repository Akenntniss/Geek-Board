/**
 * Gestionnaire de Base de Données - JavaScript
 * Interface avancée pour la gestion des bases de données des magasins
 */

class DatabaseManager {
    constructor() {
        this.sqlEditor = null;
        this.currentShopId = null;
        this.currentTable = null;
        this.init();
    }

    init() {
        this.initSQLEditor();
        this.bindEvents();
        this.setupAutoRefresh();
        this.initTooltips();
    }

    /**
     * Initialise l'éditeur SQL avec CodeMirror
     */
    initSQLEditor() {
        const sqlTextarea = document.getElementById('sql_query');
        if (sqlTextarea) {
            this.sqlEditor = CodeMirror.fromTextArea(sqlTextarea, {
                mode: 'text/x-sql',
                theme: 'default',
                lineNumbers: true,
                autoCloseBrackets: true,
                matchBrackets: true,
                indentUnit: 2,
                smartIndent: true,
                lineWrapping: true,
                foldGutter: true,
                gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
                extraKeys: {
                    "Ctrl-Space": "autocomplete",
                    "Ctrl-Enter": () => this.executeQuery(),
                    "F5": () => this.executeQuery(),
                    "Ctrl-S": (cm) => {
                        this.saveQuery();
                        return false;
                    }
                }
            });
            
            this.sqlEditor.setSize(null, 250);
            this.loadSavedQuery();
        }
    }

    /**
     * Lie les événements aux éléments de l'interface
     */
    bindEvents() {
        // Recherche rapide dans les tables
        const searchInput = document.getElementById('table-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.filterTables(e.target.value));
        }

        // Confirmation pour les requêtes dangereuses
        const dangerousCheckbox = document.getElementById('confirm_dangerous');
        const executeBtn = document.querySelector('button[name="execute_query"]');
        
        if (executeBtn) {
            executeBtn.addEventListener('click', (e) => {
                if (!this.validateQuery(e)) {
                    e.preventDefault();
                }
            });
        }

        // Raccourcis clavier globaux
        document.addEventListener('keydown', (e) => this.handleGlobalShortcuts(e));

        // Auto-save de l'éditeur SQL
        if (this.sqlEditor) {
            this.sqlEditor.on('change', () => this.autoSaveQuery());
        }

        // Export rapide
        document.querySelectorAll('.export-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleExport(e));
        });
    }

    /**
     * Filtre les tables selon le terme de recherche
     */
    filterTables(searchTerm) {
        const tableItems = document.querySelectorAll('.table-item');
        const term = searchTerm.toLowerCase();
        
        tableItems.forEach(item => {
            const tableName = item.textContent.toLowerCase();
            if (tableName.includes(term)) {
                item.style.display = 'flex';
                this.highlightMatch(item, term);
            } else {
                item.style.display = 'none';
            }
        });
    }

    /**
     * Surligne les correspondances dans le texte
     */
    highlightMatch(element, term) {
        if (!term) return;
        
        const text = element.textContent;
        const regex = new RegExp(`(${term})`, 'gi');
        const highlighted = text.replace(regex, '<mark>$1</mark>');
        element.innerHTML = highlighted;
    }

    /**
     * Valide la requête SQL avant exécution
     */
    validateQuery(event) {
        if (!this.sqlEditor) return true;
        
        const query = this.sqlEditor.getValue().trim();
        const dangerousKeywords = ['DROP', 'DELETE', 'TRUNCATE', 'ALTER', 'CREATE', 'INSERT', 'UPDATE'];
        const isDangerous = dangerousKeywords.some(keyword => 
            query.toUpperCase().includes(keyword)
        );
        
        const confirmCheckbox = document.getElementById('confirm_dangerous');
        
        if (isDangerous && !confirmCheckbox.checked) {
            this.showAlert('Cette requête contient des mots-clés potentiellement dangereux. Cochez la case de confirmation.', 'warning');
            confirmCheckbox.focus();
            return false;
        }
        
        if (!query) {
            this.showAlert('Veuillez saisir une requête SQL.', 'warning');
            this.sqlEditor.focus();
            return false;
        }
        
        return confirm('Êtes-vous sûr de vouloir exécuter cette requête ?');
    }

    /**
     * Exécute la requête SQL
     */
    executeQuery() {
        const executeBtn = document.querySelector('button[name="execute_query"]');
        if (executeBtn) {
            // Afficher un loader
            this.showQueryLoader(true);
            executeBtn.click();
        }
    }

    /**
     * Sauvegarde la requête SQL dans le localStorage
     */
    saveQuery() {
        if (this.sqlEditor) {
            const query = this.sqlEditor.getValue();
            localStorage.setItem('db_manager_saved_query', query);
            this.showAlert('Requête sauvegardée', 'success', 2000);
        }
    }

    /**
     * Auto-sauvegarde de la requête
     */
    autoSaveQuery() {
        if (this.sqlEditor) {
            const query = this.sqlEditor.getValue();
            localStorage.setItem('db_manager_current_query', query);
        }
    }

    /**
     * Charge la requête sauvegardée
     */
    loadSavedQuery() {
        const savedQuery = localStorage.getItem('db_manager_current_query');
        if (savedQuery && this.sqlEditor) {
            this.sqlEditor.setValue(savedQuery);
        }
    }

    /**
     * Gère les raccourcis clavier globaux
     */
    handleGlobalShortcuts(event) {
        // Ctrl+K pour la recherche rapide
        if (event.ctrlKey && event.key === 'k') {
            event.preventDefault();
            const searchInput = document.getElementById('table-search');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Échap pour fermer les modals
        if (event.key === 'Escape') {
            const activeModal = document.querySelector('.modal.show');
            if (activeModal) {
                const modal = bootstrap.Modal.getInstance(activeModal);
                if (modal) modal.hide();
            }
        }
    }

    /**
     * Gère l'export des données
     */
    handleExport(event) {
        const format = event.target.dataset.format;
        const table = this.currentTable;
        const shopId = this.currentShopId;
        
        if (!table || !shopId) {
            this.showAlert('Sélectionnez d\'abord une table', 'warning');
            return;
        }
        
        this.showAlert(`Export en cours (${format.toUpperCase()})...`, 'info');
        
        // L'export se fait via le lien direct
        window.location.href = `?shop_id=${shopId}&table=${encodeURIComponent(table)}&export=${format}`;
    }

    /**
     * Configure l'actualisation automatique
     */
    setupAutoRefresh() {
        // Actualisation automatique des données toutes les 30 secondes (optionnel)
        const autoRefreshCheckbox = document.getElementById('auto-refresh');
        if (autoRefreshCheckbox) {
            let refreshInterval;
            
            autoRefreshCheckbox.addEventListener('change', (e) => {
                if (e.target.checked) {
                    refreshInterval = setInterval(() => {
                        this.refreshCurrentView();
                    }, 30000);
                } else {
                    clearInterval(refreshInterval);
                }
            });
        }
    }

    /**
     * Actualise la vue actuelle
     */
    refreshCurrentView() {
        if (this.currentTable) {
            window.location.reload();
        }
    }

    /**
     * Initialise les tooltips Bootstrap
     */
    initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    /**
     * Affiche un loader pour les requêtes
     */
    showQueryLoader(show) {
        let loader = document.getElementById('query-loader');
        if (!loader && show) {
            loader = document.createElement('div');
            loader.id = 'query-loader';
            loader.className = 'query-loader d-flex align-items-center justify-content-center';
            loader.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Exécution en cours...</span>
                </div>
                <span class="ms-2">Exécution en cours...</span>
            `;
            document.body.appendChild(loader);
        }
        
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
        }
    }

    /**
     * Affiche une alerte personnalisée
     */
    showAlert(message, type = 'info', duration = 5000) {
        const alertContainer = document.getElementById('alert-container') || this.createAlertContainer();
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="fas fa-${this.getAlertIcon(type)} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        alertContainer.appendChild(alertDiv);
        
        // Auto-remove après la durée spécifiée
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, duration);
    }

    /**
     * Crée le conteneur d'alertes s'il n'existe pas
     */
    createAlertContainer() {
        const container = document.createElement('div');
        container.id = 'alert-container';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        container.style.maxWidth = '400px';
        document.body.appendChild(container);
        return container;
    }

    /**
     * Retourne l'icône appropriée pour le type d'alerte
     */
    getAlertIcon(type) {
        const icons = {
            'success': 'check-circle',
            'danger': 'exclamation-triangle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    /**
     * Formatte la taille des données
     */
    formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Copie le contenu dans le presse-papiers
     */
    copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            this.showAlert('Copié dans le presse-papiers', 'success', 2000);
        }).catch(() => {
            this.showAlert('Erreur lors de la copie', 'danger', 2000);
        });
    }
}

// Initialisation quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    window.dbManager = new DatabaseManager();
    
    // Ajout de fonctions utilitaires globales
    window.copyTableQuery = function(tableName) {
        const query = `SELECT * FROM \`${tableName}\` LIMIT 100;`;
        window.dbManager.copyToClipboard(query);
    };
    
    window.copyStructureQuery = function(tableName) {
        const query = `DESCRIBE \`${tableName}\`;`;
        window.dbManager.copyToClipboard(query);
    };
});

// Export pour utilisation en module si nécessaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DatabaseManager;
}
