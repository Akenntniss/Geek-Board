/**
 * Script de débogage pour diagnostiquer les problèmes d'affichage de la messagerie
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Débogage de la Messagerie ===');
    
    // Vérification des dépendances
    console.log('Dépendances:');
    console.log('- jQuery:', typeof $ !== 'undefined' ? 'Chargé ✅' : 'Non chargé ❌');
    console.log('- Bootstrap:', typeof bootstrap !== 'undefined' ? 'Chargé ✅' : 'Non chargé ❌');
    console.log('- Select2:', typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined' ? 'Chargé ✅' : 'Non chargé ❌');
    
    // Tester la fonction de chargement des conversations
    testConversationsAPI();
    
    // Ajouter un bouton de débogage à l'interface
    addDebugButton();
});

/**
 * Teste l'API de récupération des conversations
 */
function testConversationsAPI() {
    console.log('Test de l\'API get_conversations.php...');
    
    fetch('api/get_conversations.php')
        .then(response => {
            console.log('Statut de réponse:', response.status, response.statusText);
            return response.text();
        })
        .then(text => {
            console.log('Réponse brute:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('Données JSON parsées:', data);
                
                if (data.success) {
                    console.log('Nombre de conversations:', data.count);
                    
                    if (data.conversations.length > 0) {
                        // Vérifier la première conversation
                        const firstConv = data.conversations[0];
                        console.log('Exemple de conversation:', firstConv);
                        
                        // Vérifier les propriétés importantes
                        checkConversationProperties(firstConv);
                    } else {
                        console.warn('Aucune conversation trouvée. Vérifiez que l\'utilisateur a des conversations.');
                    }
                } else {
                    console.error('L\'API a retourné une erreur:', data.message);
                }
            } catch (e) {
                console.error('Erreur de parsing JSON:', e);
            }
        })
        .catch(error => {
            console.error('Erreur de communication avec l\'API:', error);
        });
}

/**
 * Vérifie que toutes les propriétés importantes d'une conversation sont présentes
 */
function checkConversationProperties(conversation) {
    const requiredProperties = [
        'id', 'titre', 'type', 'participants', 'last_message'
    ];
    
    console.log('Vérification des propriétés de la conversation:');
    
    let missingProperties = [];
    
    requiredProperties.forEach(prop => {
        if (conversation[prop] === undefined) {
            missingProperties.push(prop);
            console.error(`❌ Propriété manquante: ${prop}`);
        } else {
            console.log(`✅ Propriété présente: ${prop}`);
            
            // Vérifications supplémentaires
            if (prop === 'participants') {
                if (!Array.isArray(conversation[prop])) {
                    console.error('❌ participants n\'est pas un tableau');
                } else {
                    console.log(`   - Nombre de participants: ${conversation[prop].length}`);
                }
            }
            
            if (prop === 'last_message' && conversation[prop]) {
                console.log(`   - Message: ${conversation[prop].contenu ? conversation[prop].contenu.substring(0, 30) + '...' : '[aucun contenu]'}`);
            }
        }
    });
    
    if (missingProperties.length > 0) {
        console.error('⚠️ La conversation est incomplète. Les propriétés suivantes sont manquantes:', missingProperties.join(', '));
    } else {
        console.log('✅ La conversation contient toutes les propriétés requises');
    }
}

/**
 * Ajoute un bouton de débogage à l'interface
 */
function addDebugButton() {
    const button = document.createElement('button');
    button.textContent = 'Déboguer';
    button.className = 'debug-btn';
    button.style.position = 'fixed';
    button.style.bottom = '20px';
    button.style.right = '20px';
    button.style.zIndex = '1000';
    button.style.padding = '8px 16px';
    button.style.backgroundColor = '#dc3545';
    button.style.color = 'white';
    button.style.border = 'none';
    button.style.borderRadius = '4px';
    button.style.cursor = 'pointer';
    
    button.addEventListener('click', function() {
        // Tester à nouveau l'API et afficher un rapport dans une modale
        generateDebugReport();
    });
    
    document.body.appendChild(button);
}

/**
 * Génère un rapport de débogage complet
 */
function generateDebugReport() {
    let report = '<h4>Rapport de débogage</h4>';
    
    // Informations sur la page
    report += '<h5>Informations générales</h5>';
    report += `<p>Date: ${new Date().toLocaleString()}</p>`;
    report += `<p>URL: ${window.location.href}</p>`;
    report += `<p>User Agent: ${navigator.userAgent}</p>`;
    
    // Vérifier l'état de la session
    fetch('api/check_session.php')
        .then(response => response.json())
        .then(data => {
            report += '<h5>État de la session</h5>';
            if (data.logged_in) {
                report += `<p style="color: green;">✅ Connecté | ID: ${data.user_id} | Nom: ${data.user_name}</p>`;
            } else {
                report += '<p style="color: red;">❌ Non connecté</p>';
            }
            
            // Tester la connexion à la base de données
            return fetch('api/debug_info.php');
        })
        .then(response => response.text())
        .then(text => {
            report += '<h5>Connexion à la base de données</h5>';
            if (text.includes('Connexion à la base de données réussie')) {
                report += '<p style="color: green;">✅ Connexion réussie</p>';
            } else {
                report += '<p style="color: red;">❌ Problème de connexion</p>';
            }
            report += '<details><summary>Détails</summary><pre>' + text + '</pre></details>';
            
            // Tester l'API de conversations
            return fetch('api/get_conversations.php');
        })
        .then(response => response.text())
        .then(text => {
            report += '<h5>API de conversations</h5>';
            
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    report += `<p style="color: green;">✅ API fonctionnelle | ${data.count} conversations</p>`;
                } else {
                    report += `<p style="color: red;">❌ Erreur API: ${data.message}</p>`;
                }
                
                report += '<details><summary>Réponse JSON</summary><pre>' + JSON.stringify(data, null, 2) + '</pre></details>';
            } catch (e) {
                report += '<p style="color: red;">❌ Erreur de parsing JSON</p>';
                report += '<details><summary>Réponse brute</summary><pre>' + text + '</pre></details>';
            }
            
            // Vérifier l'état de l'UI
            report += '<h5>État de l\'interface</h5>';
            
            const conversationsList = document.getElementById('conversationsList');
            if (conversationsList) {
                report += `<p>Liste des conversations: ${conversationsList.children.length} éléments</p>`;
            } else {
                report += '<p style="color: red;">❌ Élément #conversationsList introuvable</p>';
            }
            
            // Afficher le rapport complet
            showDebugReport(report);
        })
        .catch(error => {
            report += '<h5 style="color: red;">Erreur lors de la génération du rapport</h5>';
            report += `<p>${error.message}</p>`;
            showDebugReport(report);
        });
}

/**
 * Affiche le rapport de débogage dans une modale
 */
function showDebugReport(reportHTML) {
    // Créer une modale pour afficher le rapport
    const modal = document.createElement('div');
    modal.className = 'debug-modal';
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    modal.style.zIndex = '2000';
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    
    const modalContent = document.createElement('div');
    modalContent.className = 'debug-modal-content';
    modalContent.style.backgroundColor = 'white';
    modalContent.style.borderRadius = '8px';
    modalContent.style.maxWidth = '800px';
    modalContent.style.width = '90%';
    modalContent.style.maxHeight = '80vh';
    modalContent.style.overflow = 'auto';
    modalContent.style.padding = '20px';
    modalContent.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.2)';
    
    const header = document.createElement('div');
    header.style.display = 'flex';
    header.style.justifyContent = 'space-between';
    header.style.alignItems = 'center';
    header.style.marginBottom = '20px';
    
    const title = document.createElement('h3');
    title.textContent = 'Rapport de débogage';
    title.style.margin = '0';
    
    const closeButton = document.createElement('button');
    closeButton.textContent = '×';
    closeButton.style.background = 'none';
    closeButton.style.border = 'none';
    closeButton.style.fontSize = '24px';
    closeButton.style.cursor = 'pointer';
    closeButton.onclick = function() {
        document.body.removeChild(modal);
    };
    
    header.appendChild(title);
    header.appendChild(closeButton);
    
    const body = document.createElement('div');
    body.innerHTML = reportHTML;
    
    const footer = document.createElement('div');
    footer.style.marginTop = '20px';
    footer.style.textAlign = 'right';
    
    const copyButton = document.createElement('button');
    copyButton.textContent = 'Copier le rapport';
    copyButton.style.padding = '8px 16px';
    copyButton.style.backgroundColor = '#0d6efd';
    copyButton.style.color = 'white';
    copyButton.style.border = 'none';
    copyButton.style.borderRadius = '4px';
    copyButton.style.cursor = 'pointer';
    copyButton.onclick = function() {
        navigator.clipboard.writeText(body.innerText).then(function() {
            copyButton.textContent = 'Copié !';
            setTimeout(function() {
                copyButton.textContent = 'Copier le rapport';
            }, 2000);
        });
    };
    
    footer.appendChild(copyButton);
    
    modalContent.appendChild(header);
    modalContent.appendChild(body);
    modalContent.appendChild(footer);
    modal.appendChild(modalContent);
    
    document.body.appendChild(modal);
}

// Fonction pour tester manuellement la récupération d'une conversation
function testSpecificConversation(convId) {
    fetch(`api/get_messages.php?conversation_id=${convId}`)
        .then(response => response.json())
        .then(data => {
            console.log(`Conversation #${convId}:`, data);
        })
        .catch(error => {
            console.error(`Erreur pour conversation #${convId}:`, error);
        });
} 