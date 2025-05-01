// Rachat interactions
document.addEventListener('DOMContentLoaded', () => {
  // Gestion du modal
  const rachatModal = new bootstrap.Modal('#addRachatModal');
  
  // Soumission du formulaire en AJAX
  document.getElementById('rachatForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    try {
      const response = await fetch('/ajax/rachat_handler.php', {
        method: 'POST',
        body: formData
      });
      
      if (!response.ok) throw new Error('Erreur réseau');
      
      const result = await response.json();
      
      if (result.success) {
        rachatModal.hide();
        refreshRachatsTable();
        showToast('success', 'Rachat enregistré avec succès');
      } else {
        showToast('error', result.error || 'Erreur inconnue');
      }
    } catch (error) {
      showToast('error', error.message);
    }
  });

  // Actualisation du tableau
  async function refreshRachatsTable() {
    const response = await fetch('/ajax/recherche_rachat.php');
    const data = await response.json();
    
    const tbody = document.querySelector('#rachatsTable tbody');
    tbody.innerHTML = data.map(item => `
      <tr>
        <td>${new Date(item.date).toLocaleDateString()}</td>
        <td>${item.modele}</td>
        <td>${item.marque}</td>
        <td>€${item.prix}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" data-id="${item.id}">
            <i class="fas fa-eye"></i>
          </button>
        </td>
      </tr>
    `).join('');
  }

  // Gestion de la pagination
  document.querySelectorAll('.page-link').forEach(link => {
    link.addEventListener('click', async (e) => {
      e.preventDefault();
      const page = e.target.dataset.page;
      await loadPage(page);
    });
  });
});

function showToast(type, message) {
  const toast = document.createElement('div');
  toast.className = `toast align-items-center text-white bg-${type} border-0`;
  toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">
        ${message}
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  `;
  
  document.querySelector('.toast-container').appendChild(toast);
  new bootstrap.Toast(toast).show();
}