/**
 * Fonction pour exporter les commandes en PDF
 */
function exportPDF() {
    console.log("Exportation des commandes en PDF...");
    
    // Récupération des données du tableau
    const rows = [];
    const tableRows = document.querySelectorAll('#commandesTableBody tr');
    
    // Vérifier s'il y a des données à exporter
    if (tableRows.length === 0 || (tableRows.length === 1 && tableRows[0].querySelector('td[colspan]'))) {
        showNotification('Aucune donnée à exporter', 'warning');
        return;
    }
    
    // Collecter les données visibles uniquement (celles qui ne sont pas filtrées)
    tableRows.forEach(row => {
        // Ne pas inclure les lignes masquées par les filtres
        if (row.style.display !== 'none') {
            try {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 8) {
                    rows.push({
                        id: cells[0].textContent.trim(),
                        client: cells[1].textContent.replace(/\s+/g, ' ').trim(),
                        piece: cells[2].textContent.trim(),
                        fournisseur: cells[3].textContent.trim(),
                        quantite: cells[4].textContent.trim(),
                        prix: cells[5].textContent.trim(),
                        date: cells[6].textContent.trim(),
                        statut: cells[7].textContent.trim()
                    });
                }
            } catch (e) {
                console.error("Erreur lors de l'extraction des données:", e);
            }
        }
    });
    
    if (rows.length === 0) {
        showNotification('Aucune donnée visible à exporter', 'warning');
        return;
    }
    
    try {
        // Initialiser jsPDF
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Titre
        doc.setFontSize(18);
        doc.text('Liste des Commandes de Pièces', 14, 22);
        
        // Date d'exportation
        doc.setFontSize(11);
        doc.setTextColor(100);
        const exportDate = new Date().toLocaleDateString('fr-FR', { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        doc.text(`Exporté le: ${exportDate}`, 14, 30);
        
        // Sous-titre avec les filtres appliqués
        let yPos = 38;
        const filtres = [];
        
        // Accéder aux variables du scope global via window
        if (window.currentStatusFilter && window.currentStatusFilter !== 'all') 
            filtres.push(`Statut: ${window.get_status_label(window.currentStatusFilter)}`);
        
        if (window.currentFournisseurId) {
            const fournisseurLabel = document.querySelector('#fournisseurBouton').textContent.trim();
            filtres.push(`Fournisseur: ${fournisseurLabel}`);
        }
        
        if (window.currentPeriode && window.currentPeriode !== 'all') {
            const periodeLabel = document.querySelector('#periodeButton').textContent.trim();
            filtres.push(`Période: ${periodeLabel}`);
        }
        
        if (window.currentSearchTerm) 
            filtres.push(`Recherche: "${window.currentSearchTerm}"`);
        
        // Ajouter les filtres si présents
        if (filtres.length > 0) {
            doc.text(`Filtres: ${filtres.join(' | ')}`, 14, yPos);
            yPos += 8;
        }
        
        // Créer le tableau
        doc.autoTable({
            head: [['ID', 'Client', 'Pièce', 'Fournisseur', 'Qté', 'Prix', 'Date', 'Statut']],
            body: rows.map(row => [
                row.id,
                row.client,
                row.piece,
                row.fournisseur,
                row.quantite,
                row.prix,
                row.date,
                row.statut
            ]),
            startY: yPos,
            styles: { fontSize: 8 },
            headStyles: { fillColor: [41, 128, 185], textColor: 255 },
            alternateRowStyles: { fillColor: [242, 242, 242] },
            margin: { top: 40 }
        });
        
        // Pied de page
        doc.setFontSize(8);
        doc.setTextColor(100);
        const pageCount = doc.internal.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            doc.text(`Page ${i} sur ${pageCount}`, doc.internal.pageSize.width - 20, doc.internal.pageSize.height - 10);
            doc.text('© Système de Gestion - Document généré automatiquement', 14, doc.internal.pageSize.height - 10);
        }
        
        // Enregistrer le PDF
        doc.save(`commandes_pieces_${new Date().toLocaleDateString('fr-FR').replace(/\//g, '-')}.pdf`);
        
        // Notification de succès
        showNotification(`Exportation réussie: ${rows.length} commandes exportées`, 'success');
        
    } catch (error) {
        console.error("Erreur lors de l'exportation PDF:", error);
        showNotification(`Erreur lors de l'exportation: ${error.message}`, 'danger');
    }
}

// Exposer la fonction globalement
window.exportPDF = exportPDF; 