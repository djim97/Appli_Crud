const API = '../php';

// ==================== TABS ====================
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('tab-' + tab.dataset.tab).classList.add('active');

        if (tab.dataset.tab === 'dashboard') {
            fetchDashboard();
        } else if (tab.dataset.tab === 'projet') {
            loadTypeOptions();
        } else if (tab.dataset.tab === 'travailler') {
            loadAgentOptions();
            loadProjetOptions();
        }
    });
});

// ==================== MESSAGE ====================
function showMessage(text, type) {
    const msg = document.getElementById('message');
    msg.textContent = text;
    msg.className = 'message ' + type;
    msg.classList.remove('hidden');
    setTimeout(() => msg.classList.add('hidden'), 3000);
}

// ==================== DATE FORMAT ====================
// yyyy-mm-dd → dd/mm/yyyy (for display)
function formatDate(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length === 3) return parts[2] + '/' + parts[1] + '/' + parts[0];
    return dateStr;
}

// dd/mm/yyyy → yyyy-mm-dd (for database)
function toDbDate(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('/');
    if (parts.length === 3) return parts[2] + '-' + parts[1] + '-' + parts[0];
    return dateStr;
}

// ==================== DEFAULT DATES ====================
function getTodayFormatted() {
    const d = new Date();
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();
    return dd + '/' + mm + '/' + yyyy;
}

document.getElementById('agent-date_embauche').value = getTodayFormatted();
document.getElementById('travailler-dateaff').value = getTodayFormatted();

// ==================== GENERIC HELPERS ====================
async function fetchJSON(url, options) {
    let text;
    try {
        const res = await fetch(url, options);
        text = await res.text();
        return JSON.parse(text);
    } catch (e) {
        // InfinityFree bot protection or error — retry once
        console.warn('Non-JSON response, retrying:', url);
        try {
            const retry = await fetch(url, options);
            const retryText = await retry.text();
            return JSON.parse(retryText);
        } catch (e2) {
            console.error('Retry also failed for:', url);
            return [];
        }
    }
}

function postJSON(url, data) {
    return fetchJSON(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
}

// ==================== MODAL ====================
function openModal(title, bodyHTML) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-body').innerHTML = bodyHTML;
    document.getElementById('detail-modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('detail-modal').classList.add('hidden');
}

document.getElementById('modal-close').addEventListener('click', closeModal);
document.getElementById('detail-modal').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) closeModal();
});

async function viewAgent(agent) {
    const affs = await fetchJSON(`${API}/travailler/read.php`);
    const agentAffs = affs.filter(a => String(a.ida) === String(agent.idA));

    let html = '<div class="detail-grid">';
    const fields = [
        ['Nom', agent.nom], ['Prenom', agent.prenom], ['Fonction', agent.fonction],
        ['Email', agent.email], ['Telephone', agent.telephone],
        ['Date Embauche', formatDate(agent.date_embauche)], ['Salaire', formatBudget(agent.salaire)]
    ];
    fields.forEach(([label, val]) => {
        html += `<div class="detail-label">${label}</div><div class="detail-value">${val ?? ''}</div>`;
    });
    html += '</div>';

    html += '<div class="detail-section-title">Projets assign\u00e9s</div>';
    if (agentAffs.length === 0) {
        html += '<p class="detail-empty">Aucun projet assign\u00e9</p>';
    } else {
        html += '<table class="detail-list-table"><thead><tr><th>Projet</th><th>R\u00f4le</th><th>Date</th></tr></thead><tbody>';
        agentAffs.forEach(a => {
            html += `<tr><td>${a.nomp ?? ''}</td><td>${a.role ?? ''}</td><td>${formatDate(a.dateaff)}</td></tr>`;
        });
        html += '</tbody></table>';
    }
    openModal(agent.prenom + ' ' + agent.nom, html);
}

async function viewProjet(projet) {
    const affs = await fetchJSON(`${API}/travailler/read.php`);
    const projAffs = affs.filter(a => String(a.idp) === String(projet.idp));

    let html = '<div class="detail-grid">';
    const fields = [
        ['Nom', projet.nomp], ['Description', projet.description],
        ['Date D\u00e9but', formatDate(projet.dated)], ['Date Fin', formatDate(projet.datf)],
        ['Budget', formatBudget(projet.budget)], ['Statut', projet.statut],
        ['Type', projet.libelletype ?? '']
    ];
    fields.forEach(([label, val]) => {
        html += `<div class="detail-label">${label}</div><div class="detail-value">${val ?? ''}</div>`;
    });
    html += '</div>';

    html += '<div class="detail-section-title">Agents assign\u00e9s</div>';
    if (projAffs.length === 0) {
        html += '<p class="detail-empty">Aucun agent assign\u00e9</p>';
    } else {
        html += '<table class="detail-list-table"><thead><tr><th>Agent</th><th>R\u00f4le</th><th>Date</th></tr></thead><tbody>';
        projAffs.forEach(a => {
            html += `<tr><td>${(a.prenom ?? '') + ' ' + (a.nom ?? '')}</td><td>${a.role ?? ''}</td><td>${formatDate(a.dateaff)}</td></tr>`;
        });
        html += '</tbody></table>';
    }
    openModal(projet.nomp, html);
}

// ==================== DASHBOARD ====================
async function fetchDashboard() {
    try {
        const stats = await fetchJSON(`${API}/dashboard/stats.php`);
        if (stats && typeof stats === 'object' && !Array.isArray(stats)) {
            renderDashboard(stats);
        }
    } catch (e) {
        showMessage('Erreur de chargement du dashboard: ' + e.message, 'error');
    }
}

function formatCFA(value) {
    if (value == null || value === '') return '';
    const num = Number(value);
    if (isNaN(num)) return value;
    return num.toLocaleString('fr-FR') + ' CFA';
}

function formatBudget(value) {
    if (value >= 1000000) {
        return (value / 1000000).toFixed(1) + 'M CFA';
    } else if (value >= 1000) {
        return (value / 1000).toFixed(1) + 'K CFA';
    }
    return Number(value).toLocaleString('fr-FR') + ' CFA';
}

function renderDashboard(stats) {
    // Update stat cards
    document.getElementById('stat-total-projets').textContent = stats.totalProjets;
    document.getElementById('stat-total-agents').textContent = stats.totalAgents;
    document.getElementById('stat-total-budget').textContent = formatBudget(stats.totalBudget);
    // Render status chart
    renderStatusChart(stats.projetsByStatus);

    // Render affectations list
    renderAffectationsList(stats.affectationsList);
}

function getStatusBarClass(statut) {
    const normalized = statut.toLowerCase().replace(/\s+/g, '-').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    if (normalized.includes('en-cours')) return 'bar-en-cours';
    if (normalized.includes('termin')) return 'bar-termine';
    if (normalized.includes('attente')) return 'bar-en-attente';
    if (normalized.includes('annul')) return 'bar-annule';
    return 'bar-default';
}

function renderStatusChart(data) {
    const container = document.getElementById('status-bars');
    container.innerHTML = '';

    if (!data || data.length === 0) {
        container.innerHTML = '<p class="chart-no-data">Aucun projet</p>';
        return;
    }

    const maxCount = Math.max(...data.map(d => parseInt(d.count)));

    data.forEach(item => {
        const percentage = maxCount > 0 ? (parseInt(item.count) / maxCount) * 100 : 0;
        const barClass = getStatusBarClass(item.statut);

        const barItem = document.createElement('div');
        barItem.className = 'chart-bar-item';
        barItem.innerHTML = `
            <span class="chart-bar-label">${item.statut}</span>
            <div class="chart-bar-container">
                <div class="chart-bar ${barClass}" style="width: ${Math.max(percentage, 10)}%">
                    <span class="chart-bar-value">${item.count}</span>
                </div>
            </div>
        `;
        container.appendChild(barItem);
    });
}

function renderAffectationsList(data) {
    const container = document.getElementById('type-bars');
    container.innerHTML = '';

    if (!data || data.length === 0) {
        container.innerHTML = '<p class="chart-no-data">Aucune affectation</p>';
        return;
    }

    const table = document.createElement('table');
    table.className = 'affectations-table';
    table.innerHTML = `
        <thead>
            <tr>
                <th>Agent</th>
                <th>Projet</th>
                <th>R\u00f4le</th>
            </tr>
        </thead>
    `;
    const tbody = document.createElement('tbody');
    data.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.nom} ${item.prenom}</td>
            <td>${item.nomp}</td>
            <td>${item.role || '-'}</td>
        `;
        tbody.appendChild(row);
    });
    table.appendChild(tbody);
    container.appendChild(table);
}

// ==================== AGENT ====================
const agentFields = ['nom', 'prenom', 'fonction', 'email', 'telephone', 'date_embauche', 'salaire'];

async function fetchAgents() {
    try {
        const agents = await fetchJSON(`${API}/agent/read.php`);
        renderAgents(Array.isArray(agents) ? agents : []);
    } catch (e) {
        showMessage('Erreur de chargement des agents: ' + e.message, 'error');
    }
}

function renderAgents(agents) {
    const tbody = document.getElementById('agent-tbody');
    const noData = document.getElementById('agent-no-data');
    tbody.innerHTML = '';

    if (agents.length === 0) {
        noData.classList.remove('hidden');
        return;
    }
    noData.classList.add('hidden');

    agents.forEach(a => {
        const tr = document.createElement('tr');

        ['nom', 'prenom', 'fonction'].forEach(f => {
            const td = document.createElement('td');
            td.textContent = a[f] ?? '';
            tr.appendChild(td);
        });

        const tdActions = document.createElement('td');
        const btnEdit = document.createElement('button');
        btnEdit.textContent = 'Modifier';
        btnEdit.className = 'btn-edit';
        btnEdit.addEventListener('click', () => editAgent(a));

        const btnDelete = document.createElement('button');
        btnDelete.textContent = 'Supprimer';
        btnDelete.className = 'btn-delete';
        btnDelete.addEventListener('click', () => deleteAgent(a.idA));

        const btnView = document.createElement('button');
        btnView.textContent = 'Voir';
        btnView.className = 'btn-view';
        btnView.addEventListener('click', () => viewAgent(a));

        tdActions.style.whiteSpace = 'nowrap';
        tdActions.appendChild(btnView);
        tdActions.appendChild(btnEdit);
        tdActions.appendChild(btnDelete);
        tr.appendChild(tdActions);
        tbody.appendChild(tr);
    });
}

function editAgent(agent) {
    document.getElementById('agent-id').value = agent.idA;
    agentFields.forEach(f => {
        document.getElementById('agent-' + f).value = f === 'date_embauche' ? formatDate(agent[f]) : (agent[f] ?? '');
    });
    document.getElementById('agent-form-title').textContent = 'Modifier l\'Agent';
    document.getElementById('agent-submit-btn').textContent = 'Mettre à jour';
    document.getElementById('agent-cancel-btn').classList.remove('hidden');
    document.querySelector('#tab-agent .form-section').scrollIntoView({ behavior: 'smooth' });
}

function resetAgentForm() {
    document.getElementById('agent-form').reset();
    document.getElementById('agent-id').value = '';
    document.getElementById('agent-date_embauche').value = getTodayFormatted();
    document.getElementById('agent-form-title').textContent = 'Ajouter un Agent';
    document.getElementById('agent-submit-btn').textContent = 'Ajouter';
    document.getElementById('agent-cancel-btn').classList.add('hidden');
}

async function deleteAgent(id) {
    if (!confirm('Voulez-vous vraiment supprimer cet agent ?')) return;
    try {
        const result = await postJSON(`${API}/agent/delete.php`, { idA: id });
        if (result.success) {
            showMessage('Agent supprimé avec succès', 'success');
            fetchAgents();
            loadAgentOptions();
        } else {
            showMessage(result.error || 'Erreur de suppression', 'error');
        }
    } catch (e) {
        showMessage('Erreur réseau: ' + e.message, 'error');
    }
}

document.getElementById('agent-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('agent-id').value;
    const data = {};
    agentFields.forEach(f => {
        data[f] = document.getElementById('agent-' + f).value.trim();
    });

    data.date_embauche = toDbDate(data.date_embauche);
    const url = id ? `${API}/agent/update.php` : `${API}/agent/create.php`;
    if (id) data.idA = parseInt(id);

    try {
        const result = await postJSON(url, data);
        if (result.success) {
            showMessage(id ? 'Agent mis à jour' : 'Agent ajouté', 'success');
            resetAgentForm();
            fetchAgents();
            loadAgentOptions();
        } else {
            showMessage(result.error || 'Erreur', 'error');
        }
    } catch (e) {
        showMessage('Erreur réseau: ' + e.message, 'error');
    }
});

document.getElementById('agent-cancel-btn').addEventListener('click', resetAgentForm);

// ==================== TYPE PROJET ====================
const typFields = ['libelletype', 'descriptiont'];

async function fetchTypesProjets() {
    try {
        const types = await fetchJSON(`${API}/typeprojet/read.php`);
        const arr = Array.isArray(types) ? types : [];
        renderTypesProjets(arr);
        return arr;
    } catch (e) {
        showMessage('Erreur de chargement des types: ' + e.message, 'error');
        return [];
    }
}

function renderTypesProjets(types) {
    const tbody = document.getElementById('typeprojet-tbody');
    const noData = document.getElementById('typeprojet-no-data');
    tbody.innerHTML = '';

    if (types.length === 0) {
        noData.classList.remove('hidden');
        return;
    }
    noData.classList.add('hidden');

    types.forEach(t => {
        const tr = document.createElement('tr');

        ['libelletype', 'descriptiont'].forEach(f => {
            const td = document.createElement('td');
            td.textContent = t[f] ?? '';
            tr.appendChild(td);
        });

        const tdActions = document.createElement('td');
        const btnEdit = document.createElement('button');
        btnEdit.textContent = 'Modifier';
        btnEdit.className = 'btn-edit';
        btnEdit.addEventListener('click', () => editTypeProjet(t));

        const btnDelete = document.createElement('button');
        btnDelete.textContent = 'Supprimer';
        btnDelete.className = 'btn-delete';
        btnDelete.addEventListener('click', () => deleteTypeProjet(t.idtype));

        tdActions.style.whiteSpace = 'nowrap';
        tdActions.appendChild(btnEdit);
        tdActions.appendChild(btnDelete);
        tr.appendChild(tdActions);
        tbody.appendChild(tr);
    });
}

function editTypeProjet(type) {
    document.getElementById('typeprojet-id').value = type.idtype;
    typFields.forEach(f => {
        document.getElementById('typeprojet-' + f).value = type[f] ?? '';
    });
    document.getElementById('typeprojet-form-title').textContent = 'Modifier le Type de Projet';
    document.getElementById('typeprojet-submit-btn').textContent = 'Mettre à jour';
    document.getElementById('typeprojet-cancel-btn').classList.remove('hidden');
    document.querySelector('#tab-typeprojet .form-section').scrollIntoView({ behavior: 'smooth' });
}

function resetTypeProjetForm() {
    document.getElementById('typeprojet-form').reset();
    document.getElementById('typeprojet-id').value = '';
    document.getElementById('typeprojet-form-title').textContent = 'Ajouter un Type de Projet';
    document.getElementById('typeprojet-submit-btn').textContent = 'Ajouter';
    document.getElementById('typeprojet-cancel-btn').classList.add('hidden');
}

async function deleteTypeProjet(id) {
    if (!confirm('Voulez-vous vraiment supprimer ce type de projet ?')) return;
    try {
        const result = await postJSON(`${API}/typeprojet/delete.php`, { idtype: id });
        if (result.success) {
            showMessage('Type de projet supprimé', 'success');
            fetchTypesProjets();
            loadTypeOptions();
        } else {
            showMessage(result.error || 'Erreur de suppression', 'error');
        }
    } catch (e) {
        showMessage('Erreur réseau: ' + e.message, 'error');
    }
}

document.getElementById('typeprojet-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('typeprojet-id').value;
    const data = {};
    typFields.forEach(f => {
        data[f] = document.getElementById('typeprojet-' + f).value.trim();
    });

    const url = id ? `${API}/typeprojet/update.php` : `${API}/typeprojet/create.php`;
    if (id) data.idtype = parseInt(id);

    try {
        const result = await postJSON(url, data);
        if (result.success) {
            showMessage(id ? 'Type mis à jour' : 'Type ajouté', 'success');
            resetTypeProjetForm();
            fetchTypesProjets();
            loadTypeOptions();
        } else {
            showMessage(result.error || 'Erreur', 'error');
        }
    } catch (e) {
        showMessage('Erreur réseau: ' + e.message, 'error');
    }
});

document.getElementById('typeprojet-cancel-btn').addEventListener('click', resetTypeProjetForm);

// ==================== PROJET ====================
const projetFields = ['nomp', 'description', 'dated', 'datf', 'budget', 'statut', 'idtype'];

async function loadTypeOptions() {
    try {
        const types = await fetchJSON(`${API}/typeprojet/read.php`);
        const select = document.getElementById('projet-idtype');
        // Keep the first placeholder option
        select.innerHTML = '<option value="">-- Choisir --</option>';
        types.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.idtype;
            opt.textContent = t.libelletype;
            select.appendChild(opt);
        });
    } catch (e) {
        console.error('Failed to load type options:', e);
    }
}

async function fetchProjets() {
    try {
        const projets = await fetchJSON(`${API}/projet/read.php`);
        renderProjets(Array.isArray(projets) ? projets : []);
    } catch (e) {
        showMessage('Erreur de chargement des projets: ' + e.message, 'error');
    }
}

function renderProjets(projets) {
    const tbody = document.getElementById('projet-tbody');
    const noData = document.getElementById('projet-no-data');
    tbody.innerHTML = '';

    if (projets.length === 0) {
        noData.classList.remove('hidden');
        return;
    }
    noData.classList.add('hidden');

    projets.forEach(p => {
        const tr = document.createElement('tr');

        ['nomp', 'description', 'dated', 'datf', 'statut'].forEach(f => {
            const td = document.createElement('td');
            td.textContent = (f === 'dated' || f === 'datf') ? formatDate(p[f]) : (p[f] ?? '');
            tr.appendChild(td);
        });

        const tdActions = document.createElement('td');
        const btnEdit = document.createElement('button');
        btnEdit.textContent = 'Modifier';
        btnEdit.className = 'btn-edit';
        btnEdit.addEventListener('click', () => editProjet(p));

        const btnDelete = document.createElement('button');
        btnDelete.textContent = 'Supprimer';
        btnDelete.className = 'btn-delete';
        btnDelete.addEventListener('click', () => deleteProjet(p.idp));

        const btnView = document.createElement('button');
        btnView.textContent = 'Voir';
        btnView.className = 'btn-view';
        btnView.addEventListener('click', () => viewProjet(p));

        tdActions.appendChild(btnView);
        tdActions.appendChild(btnEdit);
        tdActions.appendChild(btnDelete);
        tr.appendChild(tdActions);
        tbody.appendChild(tr);
    });
}

function editProjet(projet) {
    document.getElementById('projet-id').value = projet.idp;
    projetFields.forEach(f => {
        document.getElementById('projet-' + f).value = (f === 'dated' || f === 'datf') ? formatDate(projet[f]) : (projet[f] ?? '');
    });
    document.getElementById('projet-form-title').textContent = 'Modifier le Projet';
    document.getElementById('projet-submit-btn').textContent = 'Mettre à jour';
    document.getElementById('projet-cancel-btn').classList.remove('hidden');
    document.querySelector('#tab-projet .form-section').scrollIntoView({ behavior: 'smooth' });
}

function resetProjetForm() {
    document.getElementById('projet-form').reset();
    document.getElementById('projet-id').value = '';
    document.getElementById('projet-form-title').textContent = 'Ajouter un Projet';
    document.getElementById('projet-submit-btn').textContent = 'Ajouter';
    document.getElementById('projet-cancel-btn').classList.add('hidden');
}

async function deleteProjet(id) {
    if (!confirm('Voulez-vous vraiment supprimer ce projet ?')) return;
    try {
        const result = await postJSON(`${API}/projet/delete.php`, { idp: id });
        if (result.success) {
            showMessage('Projet supprimé', 'success');
            fetchProjets();
            loadProjetOptions();
        } else {
            showMessage(result.error || 'Erreur de suppression', 'error');
        }
    } catch (e) {
        showMessage('Erreur réseau: ' + e.message, 'error');
    }
}

document.getElementById('projet-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('projet-id').value;
    const data = {};
    projetFields.forEach(f => {
        data[f] = document.getElementById('projet-' + f).value.trim();
    });

    data.dated = toDbDate(data.dated);
    data.datf = toDbDate(data.datf);
    const url = id ? `${API}/projet/update.php` : `${API}/projet/create.php`;
    if (id) data.idp = parseInt(id);

    try {
        const result = await postJSON(url, data);
        if (result.success) {
            showMessage(id ? 'Projet mis à jour' : 'Projet ajouté', 'success');
            resetProjetForm();
            fetchProjets();
            loadProjetOptions();
        } else {
            showMessage(result.error || 'Erreur', 'error');
        }
    } catch (e) {
        showMessage('Erreur réseau: ' + e.message, 'error');
    }
});

document.getElementById('projet-cancel-btn').addEventListener('click', resetProjetForm);

// ==================== TRAVAILLER (AFFECTATIONS) ====================
const travFields = ['role', 'dateaff', 'ida', 'idp'];

async function loadAgentOptions() {
    try {
        const agents = await fetchJSON(`${API}/agent/read.php`);
        const select = document.getElementById('travailler-ida');
        select.innerHTML = '<option value="">-- Choisir un agent --</option>';
        agents.forEach(a => {
            const opt = document.createElement('option');
            opt.value = a.idA;
            opt.textContent = a.prenom + ' ' + a.nom;
            select.appendChild(opt);
        });
    } catch (e) {
        console.error('Failed to load agent options:', e);
    }
}

async function loadProjetOptions() {
    try {
        const projets = await fetchJSON(`${API}/projet/read.php`);
        const select = document.getElementById('travailler-idp');
        select.innerHTML = '<option value="">-- Choisir un projet --</option>';
        projets.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.idp;
            opt.textContent = p.nomp;
            select.appendChild(opt);
        });
    } catch (e) {
        console.error('Failed to load projet options:', e);
    }
}

async function fetchAffectations() {
    try {
        const affs = await fetchJSON(`${API}/travailler/read.php`);
        renderAffectations(Array.isArray(affs) ? affs : []);
    } catch (e) {
        showMessage('Erreur de chargement des affectations: ' + e.message, 'error');
    }
}

function renderAffectations(affs) {
    const tbody = document.getElementById('travailler-tbody');
    const noData = document.getElementById('travailler-no-data');
    tbody.innerHTML = '';

    if (affs.length === 0) {
        noData.classList.remove('hidden');
        return;
    }
    noData.classList.add('hidden');

    affs.forEach(a => {
        const tr = document.createElement('tr');

        const tdAgent = document.createElement('td');
        tdAgent.textContent = (a.prenom ?? '') + ' ' + (a.nom ?? '');
        tr.appendChild(tdAgent);

        const tdProjet = document.createElement('td');
        tdProjet.textContent = a.nomp ?? '';
        tr.appendChild(tdProjet);

        const tdRole = document.createElement('td');
        tdRole.textContent = a.role;
        tr.appendChild(tdRole);

        const tdDate = document.createElement('td');
        tdDate.textContent = formatDate(a.dateaff);
        tr.appendChild(tdDate);

        const tdActions = document.createElement('td');
        const btnEdit = document.createElement('button');
        btnEdit.textContent = 'Modifier';
        btnEdit.className = 'btn-edit';
        btnEdit.addEventListener('click', () => editAffectation(a));

        const btnDelete = document.createElement('button');
        btnDelete.textContent = 'Supprimer';
        btnDelete.className = 'btn-delete';
        btnDelete.addEventListener('click', () => deleteAffectation(a.numt));

        tdActions.appendChild(btnEdit);
        tdActions.appendChild(btnDelete);
        tr.appendChild(tdActions);
        tbody.appendChild(tr);
    });
}

function editAffectation(aff) {
    document.getElementById('travailler-id').value = aff.numt;
    document.getElementById('travailler-role').value = aff.role;
    document.getElementById('travailler-dateaff').value = formatDate(aff.dateaff);
    document.getElementById('travailler-ida').value = aff.ida;
    document.getElementById('travailler-idp').value = aff.idp;
    document.getElementById('travailler-form-title').textContent = 'Modifier l\'Affectation';
    document.getElementById('travailler-submit-btn').textContent = 'Mettre à jour';
    document.getElementById('travailler-cancel-btn').classList.remove('hidden');
    document.querySelector('#tab-travailler .form-section').scrollIntoView({ behavior: 'smooth' });
}

function resetAffectationForm() {
    document.getElementById('travailler-form').reset();
    document.getElementById('travailler-id').value = '';
    document.getElementById('travailler-dateaff').value = getTodayFormatted();
    document.getElementById('travailler-form-title').textContent = 'Ajouter une Affectation';
    document.getElementById('travailler-submit-btn').textContent = 'Ajouter';
    document.getElementById('travailler-cancel-btn').classList.add('hidden');
}

async function deleteAffectation(id) {
    if (!confirm('Voulez-vous vraiment supprimer cette affectation ?')) return;
    try {
        const result = await postJSON(`${API}/travailler/delete.php`, { numt: id });
        if (result.success) {
            showMessage('Affectation supprimée', 'success');
            fetchAffectations();
        } else {
            showMessage(result.error || 'Erreur de suppression', 'error');
        }
    } catch (e) {
        showMessage('Erreur réseau: ' + e.message, 'error');
    }
}

document.getElementById('travailler-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('travailler-id').value;
    const data = {};
    travFields.forEach(f => {
        data[f] = document.getElementById('travailler-' + f).value.trim();
    });

    data.dateaff = toDbDate(data.dateaff);
    const url = id ? `${API}/travailler/update.php` : `${API}/travailler/create.php`;
    if (id) data.numt = parseInt(id);

    try {
        const result = await postJSON(url, data);
        if (result.success) {
            showMessage(id ? 'Affectation mise à jour' : 'Affectation ajoutée', 'success');
            resetAffectationForm();
            fetchAffectations();
        } else {
            showMessage(result.error || 'Erreur', 'error');
        }
    } catch (e) {
        showMessage('Erreur réseau: ' + e.message, 'error');
    }
});

document.getElementById('travailler-cancel-btn').addEventListener('click', resetAffectationForm);

// ==================== INIT ====================
document.addEventListener('DOMContentLoaded', () => {
    fetchDashboard();
    fetchAgents();
    fetchTypesProjets();
    fetchProjets();
    fetchAffectations();
    loadTypeOptions();
    loadAgentOptions();
    loadProjetOptions();
});
