/**
 * GeekBoard - Module de synchronisation hors ligne
 * 
 * Ce script gère la mise en cache et la synchronisation des données
 * lorsque l'application fonctionne en mode hors ligne.
 */

// IndexedDB pour stocker les données en mode hors ligne
class OfflineStorage {
    constructor() {
        this.dbName = 'geekboard-offline';
        this.dbVersion = 1;
        this.db = null;
        this.initDB();
    }

    // Initialiser la base de données
    initDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);
            
            request.onerror = (event) => {
                console.error('Erreur lors de l\'ouverture de la base de données', event);
                reject(event);
            };
            
            request.onsuccess = (event) => {
                this.db = event.target.result;
                console.log('Base de données ouverte avec succès');
                resolve(this.db);
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Tables principales
                if (!db.objectStoreNames.contains('repairs')) {
                    const repairsStore = db.createObjectStore('repairs', { keyPath: 'id', autoIncrement: true });
                    repairsStore.createIndex('client_id', 'client_id', { unique: false });
                    repairsStore.createIndex('status', 'status', { unique: false });
                    repairsStore.createIndex('sync_status', 'sync_status', { unique: false });
                }
                
                if (!db.objectStoreNames.contains('clients')) {
                    const clientsStore = db.createObjectStore('clients', { keyPath: 'id', autoIncrement: true });
                    clientsStore.createIndex('sync_status', 'sync_status', { unique: false });
                }
                
                if (!db.objectStoreNames.contains('tasks')) {
                    const tasksStore = db.createObjectStore('tasks', { keyPath: 'id', autoIncrement: true });
                    tasksStore.createIndex('repair_id', 'repair_id', { unique: false });
                    tasksStore.createIndex('sync_status', 'sync_status', { unique: false });
                }
                
                // File d'attente pour les actions à synchroniser
                if (!db.objectStoreNames.contains('sync_queue')) {
                    const syncQueue = db.createObjectStore('sync_queue', { keyPath: 'id', autoIncrement: true });
                    syncQueue.createIndex('table', 'table', { unique: false });
                    syncQueue.createIndex('action', 'action', { unique: false });
                    syncQueue.createIndex('timestamp', 'timestamp', { unique: false });
                }
            };
        });
    }

    // Ajouter des données
    add(store, data) {
        return new Promise((resolve, reject) => {
            if (!this.db) {
                return this.initDB().then(() => this.add(store, data));
            }
            
            // Marquer comme non synchronisé
            data.sync_status = 'pending';
            data.offline_id = `offline_${Date.now()}_${Math.floor(Math.random() * 1000)}`;
            
            const transaction = this.db.transaction([store], 'readwrite');
            const objectStore = transaction.objectStore(store);
            const request = objectStore.add(data);
            
            request.onsuccess = (event) => {
                const id = event.target.result;
                
                // Ajouter à la file d'attente de synchronisation
                this.addToSyncQueue(store, 'add', { ...data, id });
                
                resolve(id);
            };
            
            request.onerror = (event) => {
                console.error(`Erreur lors de l'ajout de données dans ${store}`, event);
                reject(event);
            };
        });
    }

    // Mettre à jour des données
    update(store, id, data) {
        return new Promise((resolve, reject) => {
            if (!this.db) {
                return this.initDB().then(() => this.update(store, id, data));
            }
            
            const transaction = this.db.transaction([store], 'readwrite');
            const objectStore = transaction.objectStore(store);
            const getRequest = objectStore.get(id);
            
            getRequest.onsuccess = (event) => {
                const record = event.target.result;
                
                if (!record) {
                    reject(new Error(`Aucun enregistrement trouvé avec l'ID ${id}`));
                    return;
                }
                
                // Fusionner les données existantes avec les nouvelles données
                const updatedData = { ...record, ...data, sync_status: 'pending' };
                
                const updateRequest = objectStore.put(updatedData);
                
                updateRequest.onsuccess = () => {
                    // Ajouter à la file d'attente de synchronisation
                    this.addToSyncQueue(store, 'update', updatedData);
                    
                    resolve(id);
                };
                
                updateRequest.onerror = (event) => {
                    console.error(`Erreur lors de la mise à jour de données dans ${store}`, event);
                    reject(event);
                };
            };
            
            getRequest.onerror = (event) => {
                console.error(`Erreur lors de la récupération de données dans ${store}`, event);
                reject(event);
            };
        });
    }

    // Supprimer des données
    delete(store, id) {
        return new Promise((resolve, reject) => {
            if (!this.db) {
                return this.initDB().then(() => this.delete(store, id));
            }
            
            const transaction = this.db.transaction([store], 'readwrite');
            const objectStore = transaction.objectStore(store);
            const getRequest = objectStore.get(id);
            
            getRequest.onsuccess = (event) => {
                const record = event.target.result;
                
                if (!record) {
                    reject(new Error(`Aucun enregistrement trouvé avec l'ID ${id}`));
                    return;
                }
                
                const deleteRequest = objectStore.delete(id);
                
                deleteRequest.onsuccess = () => {
                    // Ajouter à la file d'attente de synchronisation
                    this.addToSyncQueue(store, 'delete', { id });
                    
                    resolve(id);
                };
                
                deleteRequest.onerror = (event) => {
                    console.error(`Erreur lors de la suppression de données dans ${store}`, event);
                    reject(event);
                };
            };
            
            getRequest.onerror = (event) => {
                console.error(`Erreur lors de la récupération de données dans ${store}`, event);
                reject(event);
            };
        });
    }

    // Récupérer toutes les données
    getAll(store) {
        return new Promise((resolve, reject) => {
            if (!this.db) {
                return this.initDB().then(() => this.getAll(store));
            }
            
            const transaction = this.db.transaction([store], 'readonly');
            const objectStore = transaction.objectStore(store);
            const request = objectStore.getAll();
            
            request.onsuccess = (event) => {
                resolve(event.target.result);
            };
            
            request.onerror = (event) => {
                console.error(`Erreur lors de la récupération de données dans ${store}`, event);
                reject(event);
            };
        });
    }

    // Ajouter à la file d'attente de synchronisation
    addToSyncQueue(table, action, data) {
        return new Promise((resolve, reject) => {
            if (!this.db) {
                return this.initDB().then(() => this.addToSyncQueue(table, action, data));
            }
            
            const transaction = this.db.transaction(['sync_queue'], 'readwrite');
            const objectStore = transaction.objectStore('sync_queue');
            
            const syncItem = {
                table,
                action,
                data,
                timestamp: Date.now(),
                attempts: 0
            };
            
            const request = objectStore.add(syncItem);
            
            request.onsuccess = (event) => {
                resolve(event.target.result);
            };
            
            request.onerror = (event) => {
                console.error('Erreur lors de l\'ajout à la file d\'attente de synchronisation', event);
                reject(event);
            };
        });
    }

    // Récupérer la file d'attente de synchronisation
    getSyncQueue() {
        return this.getAll('sync_queue');
    }

    // Supprimer un élément de la file d'attente de synchronisation
    removeSyncQueueItem(id) {
        return new Promise((resolve, reject) => {
            if (!this.db) {
                return this.initDB().then(() => this.removeSyncQueueItem(id));
            }
            
            const transaction = this.db.transaction(['sync_queue'], 'readwrite');
            const objectStore = transaction.objectStore('sync_queue');
            const request = objectStore.delete(id);
            
            request.onsuccess = () => {
                resolve(id);
            };
            
            request.onerror = (event) => {
                console.error('Erreur lors de la suppression de l\'élément de la file d\'attente', event);
                reject(event);
            };
        });
    }
}

// Synchroniseur pour gérer la synchronisation des données avec le serveur
class Synchronizer {
    constructor() {
        this.storage = new OfflineStorage();
        this.isSyncing = false;
        this.syncEndpoint = '/ajax_handlers/sync_data.php';
    }

    // Vérifier si l'application est en ligne
    isOnline() {
        return navigator.onLine;
    }

    // Synchroniser les données
    async sync() {
        if (this.isSyncing || !this.isOnline()) {
            return;
        }
        
        this.isSyncing = true;
        
        try {
            // Récupérer la file d'attente de synchronisation
            const queue = await this.storage.getSyncQueue();
            
            if (queue.length === 0) {
                this.isSyncing = false;
                return;
            }
            
            console.log(`Synchronisation de ${queue.length} éléments`);
            
            // Traiter chaque élément de la file d'attente
            for (const item of queue) {
                try {
                    await this.syncItem(item);
                    await this.storage.removeSyncQueueItem(item.id);
                } catch (error) {
                    console.error('Erreur lors de la synchronisation de l\'élément', item, error);
                    
                    // Incrémenter le nombre de tentatives
                    const transaction = this.storage.db.transaction(['sync_queue'], 'readwrite');
                    const objectStore = transaction.objectStore('sync_queue');
                    const getRequest = objectStore.get(item.id);
                    
                    getRequest.onsuccess = (event) => {
                        const record = event.target.result;
                        if (record) {
                            record.attempts = (record.attempts || 0) + 1;
                            objectStore.put(record);
                        }
                    };
                }
            }
            
            // Afficher un message de synchronisation réussie
            if (typeof toastr !== 'undefined') {
                toastr.success('Synchronisation terminée', 'Données synchronisées');
            }
        } catch (error) {
            console.error('Erreur lors de la synchronisation', error);
        } finally {
            this.isSyncing = false;
        }
    }

    // Synchroniser un élément
    async syncItem(item) {
        const { table, action, data } = item;
        
        const response = await fetch(this.syncEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                table,
                action,
                data
            })
        });
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Erreur lors de la synchronisation');
        }
        
        return result;
    }

    // Démarrer la synchronisation automatique
    startAutoSync() {
        // Synchroniser lors de la reconnexion
        window.addEventListener('online', () => {
            console.log('Connexion rétablie, synchronisation en cours...');
            this.sync();
            
            // Afficher un message de reconnexion
            if (typeof toastr !== 'undefined') {
                toastr.success('Connexion Internet rétablie', 'Connecté');
            }
        });
        
        // Détecter la perte de connexion
        window.addEventListener('offline', () => {
            console.log('Connexion perdue, passage en mode hors ligne');
            
            // Afficher un message de perte de connexion
            if (typeof toastr !== 'undefined') {
                toastr.warning('Connexion Internet perdue, passage en mode hors ligne', 'Déconnecté');
            }
        });
        
        // Synchroniser toutes les 5 minutes si en ligne
        setInterval(() => {
            if (this.isOnline()) {
                this.sync();
            }
        }, 5 * 60 * 1000);
        
        // Synchroniser au démarrage si en ligne
        if (this.isOnline()) {
            setTimeout(() => {
                this.sync();
            }, 5000);
        }
    }
}

// Initialiser la synchronisation
const offlineSynchronizer = new Synchronizer();
offlineSynchronizer.startAutoSync();

// Intercepter les formulaires pour le traitement hors ligne
document.addEventListener('DOMContentLoaded', () => {
    const offlineStorage = new OfflineStorage();
    
    // Gérer les formulaires de réparation
    const repairForms = document.querySelectorAll('form[data-offline-form="repair"]');
    
    repairForms.forEach(form => {
        form.addEventListener('submit', async (event) => {
            // Si hors ligne, intercepter le formulaire
            if (!navigator.onLine) {
                event.preventDefault();
                
                // Récupérer les données du formulaire
                const formData = new FormData(form);
                const repairData = {};
                
                formData.forEach((value, key) => {
                    repairData[key] = value;
                });
                
                try {
                    // Stocker la réparation localement
                    const id = await offlineStorage.add('repairs', repairData);
                    
                    // Afficher un message de succès
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Réparation enregistrée en mode hors ligne. Elle sera synchronisée automatiquement lors de la reconnexion.', 'Enregistré localement');
                    }
                    
                    // Rediriger vers la page des réparations
                    window.location.href = '/index.php?page=reparations&offline=1';
                } catch (error) {
                    console.error('Erreur lors de l\'enregistrement hors ligne', error);
                    
                    // Afficher un message d'erreur
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Erreur lors de l\'enregistrement en mode hors ligne.', 'Erreur');
                    }
                }
            }
        });
    });
});

// Exporter les classes pour utilisation dans d'autres fichiers
window.OfflineStorage = OfflineStorage;
window.Synchronizer = Synchronizer; 