// Fonction globale pour générer le PDF
function generatePDF() {
    console.log("Fonction generatePDF appelée");
    
    // Vérifier que jsPDF est bien chargée
    if (!window.jspdf) {
        console.error("La bibliothèque jsPDF n'est pas chargée");
        alert("Erreur: La bibliothèque jsPDF n'est pas disponible. Veuillez recharger la page.");
        return;
    }
    
    const filter = document.getElementById('exportFilter').value;
    const period = document.getElementById('exportPeriod').value;
    const groupBySupplier = document.getElementById('groupBySupplier').value === 'true';
    const sortByDate = document.getElementById('sortByDate').value === 'true';

    console.log("Configuration de l'export:", { filter, period, groupBySupplier, sortByDate });

    try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('landscape');
        
        // Titre
        doc.setFontSize(18);
        doc.text('Liste des Commandes de Pièces', 14, 22);
        
        // Date de génération
        doc.setFontSize(10);
        doc.text('Généré le ' + new Date().toLocaleDateString('fr-FR'), 14, 30);
        
        // Filtrer les lignes selon les critères
        let rows = Array.from(document.querySelectorAll('tbody tr')).filter(row => {
            if (row.style.display === 'none') return false;
            
            const reparationCell = row.querySelector('td:nth-child(6)');
            const hasRepair = reparationCell && !reparationCell.textContent.includes('-');
            
            if (filter === 'with-repair' && !hasRepair) return false;
            if (filter === 'without-repair' && hasRepair) return false;
            
            const dateCell = row.querySelector('td:nth-child(8)');
            const date = new Date(dateCell.textContent.split(' ')[0].split('/').reverse().join('-'));
            const today = new Date();
            const diffDays = Math.ceil((today - date) / (1000 * 60 * 60 * 24));
            
            if (period !== 'all' && diffDays > parseInt(period)) return false;
            
            return true;
        });
        
        console.log("Nombre de lignes filtrées:", rows.length);
        
        // Préparer les données pour le tableau
        const tableData = rows.map(row => {
            const cells = row.querySelectorAll('td');
            return {
                code_barre: cells[0].textContent.trim(),
                fournisseur: cells[1].querySelector('small').textContent.trim(),
                piece: cells[2].textContent.trim(),
                client: cells[1].querySelector('.client-name').textContent.trim(),
                reparation: cells[5].textContent.trim(),
                quantite: cells[3].textContent.trim(),
                prix: cells[4].textContent.trim(),
                statut: cells[6].textContent.trim(),
                date: cells[7].textContent.trim(),
                dateObj: new Date(cells[7].textContent.split(' ')[0].split('/').reverse().join('-'))
            };
        });
        
        // Trier les données
        if (sortByDate) {
            tableData.sort((a, b) => b.dateObj - a.dateObj);
        }
        
        // Grouper par fournisseur si demandé
        if (groupBySupplier) {
            const groupedData = {};
            tableData.forEach(item => {
                if (!groupedData[item.fournisseur]) {
                    groupedData[item.fournisseur] = [];
                }
                groupedData[item.fournisseur].push(item);
            });
            
            // Générer le tableau pour chaque fournisseur
            let currentY = 40;
            Object.entries(groupedData).forEach(([fournisseur, items]) => {
                // Titre du fournisseur
                doc.setFontSize(12);
                doc.setFont(undefined, 'bold');
                doc.text(fournisseur, 14, currentY);
                currentY += 10;
                
                // Tableau pour ce fournisseur
                doc.autoTable({
                    head: [['Code barre', 'Pièce', 'Client', 'Réparation', 'Quantité', 'Prix', 'Statut', 'Date']],
                    body: items.map(item => [
                        item.code_barre,
                        item.piece,
                        item.client,
                        item.reparation,
                        item.quantite,
                        item.prix,
                        item.statut,
                        item.date
                    ]),
                    startY: currentY,
                    theme: 'grid',
                    styles: { fontSize: 8, cellPadding: 2 },
                    headStyles: { fillColor: [13, 110, 253], textColor: [255, 255, 255] },
                    alternateRowStyles: { fillColor: [240, 240, 240] },
                    margin: { top: 10 }
                });
                
                currentY = doc.lastAutoTable.finalY + 15;
                
                // Nouvelle page si nécessaire
                if (currentY > 180) {
                    doc.addPage();
                    currentY = 20;
                }
            });
        } else {
            // Tableau simple sans groupement
            doc.autoTable({
                head: [['Code barre', 'Fournisseur', 'Pièce', 'Client', 'Réparation', 'Quantité', 'Prix', 'Statut', 'Date']],
                body: tableData.map(item => [
                    item.code_barre,
                    item.fournisseur,
                    item.piece,
                    item.client,
                    item.reparation,
                    item.quantite,
                    item.prix,
                    item.statut,
                    item.date
                ]),
                startY: 40,
                theme: 'grid',
                styles: { fontSize: 8, cellPadding: 2 },
                headStyles: { fillColor: [13, 110, 253], textColor: [255, 255, 255] },
                alternateRowStyles: { fillColor: [240, 240, 240] },
                margin: { top: 40 }
            });
        }
        
        // Pied de page
        const pageCount = doc.internal.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            doc.setFontSize(8);
            doc.text('Page ' + i + ' sur ' + pageCount, doc.internal.pageSize.width - 20, doc.internal.pageSize.height - 10);
            doc.text('© ' + new Date().getFullYear() + ' - Gestion des Commandes', 14, doc.internal.pageSize.height - 10);
        }
        
        // Enregistrer le PDF
        console.log("Enregistrement du PDF...");
        doc.save('commandes_pieces_' + new Date().toLocaleDateString('fr-FR').replace(/\//g, '-') + '.pdf');
        
        // Fermer le modal
        console.log("Fermeture du modal...");
        const modal = bootstrap.Modal.getInstance(document.getElementById('exportConfigModal'));
        modal.hide();
        
        console.log("PDF généré avec succès");
    } catch (error) {
        console.error("Erreur lors de la génération du PDF:", error);
        alert("Une erreur s'est produite lors de la génération du PDF: " + error.message);
    }
}

// Fonction pour obtenir le libellé du statut
function get_status_label(statut) {
    switch(statut) {
        case 'en_attente': return 'En attente';
        case 'commande': return 'Commandé';
        case 'recue': return 'Reçu';
        case 'annulee': return 'Annulé';
        case 'urgent': return 'URGENT';
        default: return statut;
    }
}

// Initialiser les handlers lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log("Initialisation des fonctions PDF...");
}); 