const app = document.querySelector('#app');
const dialog = document.querySelector('#admin-dialog');
const dialogContent = document.querySelector('#dialog-content');
const routes = { home: 'Home', play: 'Daily challenge', results: 'Results', leaderboard: 'Leaderboard', profile: 'Profile', login: 'Log in', register: 'Sign up', admin: 'Admin' };
let routeVersion = 0;
let ranking = [4, 1, 5, 2, 3];
let demoAttempt = null;
let leaderboardPeriod = 'today';
let challengeFilter = 'All';
let draggedItem = null;
let toastTimer;
const menuMedia = window.matchMedia('(max-width: 850px)');
const navigation = document.querySelector('#site-navigation');
const menuToggle = document.querySelector('.menu-toggle');

function setMobileMenu(open, restoreFocus = true) {
  const wasOpen = menuToggle.getAttribute('aria-expanded') === 'true';
  document.body.classList.toggle('menu-open', open);
  navigation.hidden = menuMedia.matches && !open;
  menuToggle.setAttribute('aria-expanded', open);
  menuToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
  app.inert = open;
  document.querySelector('.site-footer').inert = open;
  document.querySelector('.skip-link').inert = open;
  if (open) navigation.querySelector('a').focus();
  else if (wasOpen && restoreFocus) menuToggle.focus();
}

function escapeHtml(value) {
  return String(value).replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]);
}

function art(name, className = '') {
  return `<svg class="${className}" aria-hidden="true"><use href="assets/art.svg#${name}"></use></svg>`;
}

function currentAdminView() {
  return location.hash.split('/')[2] || 'challenges';
}

async function renderRoute() {
  setMobileMenu(false, false);
  const version = ++routeVersion;
  const parts = location.hash.slice(2).split('/');
  const route = parts[0];
  const validAdmin = ['challenges', 'users', 'attempts', 'attempt-items', 'ai'];
  dialog.close();
  app.removeAttribute('aria-busy');
  document.querySelectorAll('[data-nav]').forEach(link => {
    const active = link.dataset.nav === route || (route === 'results' && link.dataset.nav === 'play');
    if (active) link.setAttribute('aria-current', 'page');
    else link.removeAttribute('aria-current');
  });
  document.title = `${routes[route] || 'Page not found'} · Rank It`;
  if (!location.hash.startsWith('#/') || !routes[route] || parts.length > (route === 'admin' ? 2 : 1) || (route === 'admin' && !validAdmin.includes(currentAdminView()))) {
    app.innerHTML = '<section class="empty-state page-width"><span class="eyebrow gold">WRONG TURN, RANKER</span><h1>That page is off the board.</h1><p>Let’s get you back in the game.</p><a class="button button-purple" href="#/home">Back to home →</a></section>';
    app.focus();
    return;
  }
  app.setAttribute('aria-busy', 'true');
  try {
    const response = await fetch(`views/${route}.html`);
    if (!response.ok) throw new Error('View unavailable');
    const html = await response.text();
    // A slower fetch must not replace a view reached by a more recent navigation.
    if (version !== routeVersion) return;
    app.innerHTML = html;
    // Live Server injects reload scripts into SVG tags, so partials use art markers.
    app.querySelectorAll('[data-art]').forEach(placeholder => {
      placeholder.outerHTML = art(placeholder.dataset.art, placeholder.className);
    });
    if (route === 'play') renderGame();
    if (route === 'results') renderResults();
    if (route === 'leaderboard') renderLeaderboard();
    if (route === 'profile') renderHistory();
    if (route === 'admin') renderAdmin();
  } catch (error) {
    if (version !== routeVersion) return;
    console.error(`Unable to load the ${route} view:`, error);
    app.innerHTML = '<section class="empty-state page-width"><h1>Couldn’t load this view.</h1><p>Please try again in a moment.</p><button class="button button-purple" data-action="retry-view">Try again</button></section>';
  } finally {
    if (version === routeVersion) {
      app.removeAttribute('aria-busy');
      app.focus({ preventScroll: true });
      window.scrollTo(0, 0);
    }
  }
}

function showToast(message) {
  const toast = document.querySelector('#toast');
  clearTimeout(toastTimer);
  toast.textContent = message;
  toast.hidden = false;
  toastTimer = setTimeout(() => { toast.hidden = true; }, 4200);
}

function renderGame() {
  document.querySelector('#challenge-date').textContent = 'OCT 23, 2026';
  document.querySelector('#challenge-title').textContent = demoChallenge.title;
  document.querySelector('#challenge-description').textContent = demoChallenge.description;
  renderRanking();
  if (demoAttempt) {
    document.querySelector('[data-action="shuffle"]').disabled = true;
    document.querySelector('[data-action="submit-ranking"]').textContent = 'View your result →';
    document.querySelector('#game-status').textContent = 'Challenge completed. Your order is locked.';
  }
}

function renderRanking(movedId) {
  document.querySelector('#ranking-list').innerHTML = ranking.map((id, index) => {
    const item = demoChallenge.items.find(item => item.id === id);
    return `<li class="ranking-row ${id === movedId ? 'just-moved' : ''}" data-id="${id}" draggable="${!demoAttempt}"><span class="position">${index + 1}</span>${art(item.art, 'item-art')}<div class="item-name"><strong>${item.text}</strong><span>${item.detail}</span></div><span class="drag-handle" aria-hidden="true">⠿</span><div class="move-controls"><button data-action="move-up" data-id="${id}" aria-label="Move ${item.text} up" ${index === 0 || demoAttempt ? 'disabled' : ''}>↑</button><button data-action="move-down" data-id="${id}" aria-label="Move ${item.text} down" ${index === 4 || demoAttempt ? 'disabled' : ''}>↓</button></div></li>`;
  }).join('');
}

function moveItem(id, direction) {
  if (demoAttempt) return;
  const index = ranking.indexOf(id);
  const newIndex = index + direction;
  if (index < 0 || newIndex < 0 || newIndex >= ranking.length) return;
  ranking.splice(index, 1);
  ranking.splice(newIndex, 0, id);
  renderRanking(id);
  const action = direction < 0 ? 'move-up' : 'move-down';
  const button = document.querySelector(`[data-action="${action}"][data-id="${id}"]`);
  if (!button.disabled) button.focus();
  else document.querySelector(`[data-id="${id}"] .move-controls button:not(:disabled)`).focus();
  const item = demoChallenge.items.find(item => item.id === id);
  document.querySelector('#game-status').textContent = `${item.text} moved to position ${newIndex + 1}.`;
}

function submitRanking() {
  if (!demoAttempt) {
    const correctCount = ranking.filter((id, index) => demoChallenge.items.find(item => item.id === id).correctPosition === index + 1).length;
    demoAttempt = { order: [...ranking], score: correctCount * 20 };
  }
  location.hash = '#/results';
}

function renderResults() {
  const container = document.querySelector('#results-content');
  if (!demoAttempt) {
    container.innerHTML = '<div class="empty-state"><span class="eyebrow gold">YOUR SCORE IS WAITING</span><h1>First, trust your instinct.</h1><p>Complete today’s challenge to reveal your result.</p><a href="#/play" class="button button-gold">Play today →</a></div>';
    return;
  }
  const score = demoAttempt.score;
  container.innerHTML = `<div class="result-heading">${art('trophy', 'result-trophy')}<span class="eyebrow">YOUR SCORE</span><h1><span>${score}</span><small>/ 100</small></h1><h2>${score === 100 ? 'Perfect order!' : score >= 60 ? 'Nice work!' : 'A tricky one!'}</h2><p>${score / 20} of 5 positions correct · 20 points each</p><span class="confetti confetti-one" aria-hidden="true">✦</span><span class="confetti confetti-two" aria-hidden="true">✧</span></div><h3 class="ranking-caption">YOUR CHOSEN ORDER</h3><ol class="result-cards">${demoAttempt.order.map((id, index) => {
    const item = demoChallenge.items.find(item => item.id === id);
    const correct = item.correctPosition === index + 1;
    return `<li class="result-card ${correct ? 'is-correct' : 'is-incorrect'}"><span class="result-position">${index + 1}</span>${art(item.art, 'result-art')}<strong>${item.text}</strong><span class="result-indicator">${correct ? '✓ Correct' : `× Correct: #${item.correctPosition}`}</span></li>`;
  }).join('')}</ol><div class="correct-order panel"><h3>The perfect order</h3><ol>${demoChallenge.items.map(item => `<li><span class="mini-position">${item.correctPosition}</span>${item.text}</li>`).join('')}</ol></div><div class="result-actions"><a href="#/leaderboard" class="button button-purple">View leaderboard →</a></div>`;
}

function renderLeaderboard() {
  let rank = 1;
  const players = leaderboardData[leaderboardPeriod];
  const rows = players.map((player, index) => {
    if (index > 0 && player.points !== players[index - 1].points) rank = index + 1;
    if (player.name === 'Timur') document.querySelector('#your-rank').textContent = `#${rank}`;
    return `<tr class="${player.name === 'Timur' ? 'current-player' : ''}"><td><span class="rank-badge rank-${rank}">${rank}</span></td><td><span class="player-cell"><span class="player-avatar avatar-${index % 4}">${escapeHtml(player.name.charAt(0).toUpperCase())}</span>${player.name}${player.name === 'Timur' ? '<span class="you-tag">YOU</span>' : ''}</span></td><td class="points-cell">${player.points.toLocaleString()} <small>pts</small></td></tr>`;
  }).join('');
  const table = document.querySelector('#leaderboard-table');
  table.innerHTML = `<table><thead><tr><th scope="col">Rank</th><th scope="col">Player</th><th scope="col">Points</th></tr></thead><tbody>${rows}</tbody></table>`;
  table.setAttribute('aria-labelledby', `tab-${leaderboardPeriod}`);
  document.querySelectorAll('[data-period]').forEach(button => {
    button.setAttribute('aria-selected', button.dataset.period === leaderboardPeriod);
    button.tabIndex = button.dataset.period === leaderboardPeriod ? 0 : -1;
  });
}

function renderHistory() {
  document.querySelector('#history-rows').innerHTML = historyData.map((entry, index) => `<tr><td class="muted history-date">${entry.date}</td><td class="history-challenge"><span class="history-icon" aria-hidden="true">${entry.icon}</span>${entry.title}</td><td class="history-points"><strong class="${entry.score === 100 ? 'green-text' : ''}">${entry.score}</strong><span class="muted"> / 100</span></td><td class="history-action"><button class="table-button" data-history="${index}" aria-label="View ${entry.title} result from ${entry.date}"><span class="history-action-label">Details </span><span aria-hidden="true">↗</span></button></td></tr>`).join('');
}

const entityFields = {
  users: [{ name: 'name', label: 'Name', type: 'text' }, { name: 'email', label: 'Email', type: 'email' }, { name: 'role', label: 'Role', options: ['user', 'admin'] }],
  attempts: [{ name: 'user_id', label: 'User ID', type: 'number' }, { name: 'challenge_id', label: 'Challenge ID', type: 'number' }, { name: 'score', label: 'Score', type: 'number', min: 0, max: 100, step: 20 }, { name: 'completed_at', label: 'Completed at', type: 'datetime-local' }],
  'attempt-items': [{ name: 'attempt_id', label: 'Attempt ID', type: 'number' }, { name: 'challenge_item_id', label: 'Challenge item ID', type: 'number' }, { name: 'selected_position', label: 'Selected position', type: 'number', max: 5 }]
};
const entityTitles = { challenges: 'Challenges', users: 'Users', attempts: 'Attempts', 'attempt-items': 'Attempt items', ai: 'AI studio' };
const entitySingular = { challenges: 'challenge', users: 'user', attempts: 'attempt', 'attempt-items': 'attempt item' };

function statusBadge(status) {
  return `<span class="status-badge status-${status.toLowerCase()}">${status === 'Published' ? '●' : status === 'Pending' ? '◷' : '✎'} ${status}</span>`;
}

function renderAdmin() {
  const entity = currentAdminView();
  document.querySelector('#admin-section').value = entity;
  document.querySelectorAll('[data-admin]').forEach(link => {
    if (link.dataset.admin === entity) link.setAttribute('aria-current', 'page');
    else link.removeAttribute('aria-current');
  });
  const container = document.querySelector('#admin-content');
  container.dataset.entity = entity;
  container.innerHTML = `<div class="section-heading admin-heading"><div><span class="eyebrow violet-text">ADMIN WORKSPACE</span><h1>${entityTitles[entity]}<span class="violet-text">.</span></h1><p>${entity === 'challenges' ? 'A great daily challenge starts right here.' : 'Manage rankings, players and their records.'}</p></div>${entity !== 'ai' ? `<button class="button button-purple button-small" data-action="new-record">+ New ${entitySingular[entity]}</button>` : ''}</div>`;
  if (entity === 'ai') {
    container.insertAdjacentHTML('beforeend', '<section class="panel ai-panel"><span class="ai-symbol" aria-hidden="true">✧</span><span class="eyebrow gold">A LITTLE HELP FOR YOUR NEXT BIG IDEA</span><h2>Meet your future challenge co-pilot.</h2><p>Coming soon: turn a topic into five things to rank. Review the facts, edit the order and approve each challenge before publishing.</p><label for="ai-topic">What should we rank?</label><input id="ai-topic" placeholder="e.g. Space, cities, music…" maxlength="120"><button class="button button-purple" data-action="ai-placeholder">See how it works ↗</button><p class="ai-message" role="status"></p><div class="workflow"><span>01 · Draft</span><span>→</span><span>02 · Review & edit</span><span>→</span><span>03 · Approve</span></div></section>');
    return;
  }
  if (entity === 'challenges') {
    container.insertAdjacentHTML('beforeend', `<div class="filter-tabs" aria-label="Challenge status filter">${['All', 'Draft', 'Pending', 'Published'].map(status => `<button data-filter="${status}" aria-pressed="${challengeFilter === status}">${status === 'Pending' ? 'Pending review' : status === 'Draft' ? 'Drafts' : status}</button>`).join('')}</div>`);
  }
  const rows = adminData[entity].filter(row => entity !== 'challenges' || challengeFilter === 'All' || row.status === challengeFilter);
  const fields = entity === 'challenges' ? [{ name: 'challenge_date', label: 'Date' }, { name: 'title', label: 'Title' }, { name: 'status', label: 'Status' }] : entityFields[entity];
  container.insertAdjacentHTML('beforeend', `<div class="panel table-scroll admin-table"><table><thead><tr><th scope="col">ID</th>${fields.map(field => `<th scope="col">${field.label}</th>`).join('')}<th scope="col">Actions</th></tr></thead><tbody>${rows.length ? rows.map(row => `<tr><td class="muted record-id" data-label="ID">#${row.id}</td>${fields.map(field => `<td data-label="${field.label}" class="${['title', 'name'].includes(field.name) ? 'record-title' : ''}">${field.name === 'status' ? statusBadge(row.status) : escapeHtml(row[field.name])}</td>`).join('')}<td class="record-actions" data-label="Actions"><div class="row-actions"><button class="table-button" data-action="edit-record" data-id="${row.id}">${entity === 'challenges' && row.status === 'Pending' ? 'Review' : 'Edit'}</button><button class="table-button delete-button" data-action="delete-record" data-id="${row.id}">Delete</button></div></td></tr>`).join('') : `<tr><td colspan="${fields.length + 2}" class="empty-table">No ${entityTitles[entity].toLowerCase()} here yet.</td></tr>`}</tbody></table></div><p class="table-note">${entity === 'challenges' ? 'Edit a challenge to manage its items, order and publishing status.' : 'Edit or remove records using the actions column.'}</p>${entity === 'challenges' ? '<a class="ai-callout" href="#/admin/ai"><span>✧</span><div><strong>A little inspiration, coming soon.</strong><p>See how AI-assisted challenges will work.</p></div><span>↗</span></a>' : ''}`);
}

function openDialog(html) {
  dialogContent.innerHTML = html;
  dialog.showModal();
}

function renderItemFields(items) {
  const list = document.querySelector('#editor-items');
  list.innerHTML = items.map((text, index) => `<div class="editor-item"><span class="mini-position">${index + 1}</span><label class="sr-only" for="editor-item-${index}">Item at correct position ${index + 1}</label><input id="editor-item-${index}" name="item" value="${escapeHtml(text)}" maxlength="100" required><button type="button" class="icon-button" data-action="editor-up" data-index="${index}" aria-label="Move item ${index + 1} up" ${index === 0 ? 'disabled' : ''}>↑</button><button type="button" class="icon-button delete-button" data-action="remove-item" data-index="${index}" aria-label="Remove item ${index + 1}">×</button></div>`).join('');
  document.querySelector('[data-action="add-item"]').disabled = items.length >= 5;
}

function openEditor(id) {
  const entity = currentAdminView();
  const row = adminData[entity].find(row => row.id === id);
  const fields = entity === 'challenges' ? [{ name: 'title', label: 'Challenge title', type: 'text' }, { name: 'description', label: 'Instructions', type: 'text' }, { name: 'challenge_date', label: 'Challenge date', type: 'date' }, { name: 'status', label: 'Status', options: ['Draft', 'Pending', 'Published'] }] : entityFields[entity];
  openDialog(`<form id="record-form" data-entity="${entity}" data-id="${id || ''}"><div class="dialog-heading"><div><span class="eyebrow violet-text">RECORD EDITOR</span><h2 id="dialog-title">${row ? 'Edit' : 'New'} ${entitySingular[entity]}</h2></div><button type="button" class="icon-button" data-action="close-dialog" aria-label="Close dialog">×</button></div><div class="form-grid">${fields.map(field => `<div class="form-field"><label for="field-${field.name}">${field.label}</label>${field.options ? `<select id="field-${field.name}" name="${field.name}">${field.options.map(option => `<option ${row && row[field.name] === option ? 'selected' : ''}>${option}</option>`).join('')}</select>` : `<input id="field-${field.name}" name="${field.name}" type="${field.type}" value="${escapeHtml(row ? row[field.name] : '')}" ${field.type === 'number' ? `min="${field.min ?? 1}" max="${field.max ?? 999999}" step="${field.step || 1}"` : 'maxlength="200"'} required>`}</div>`).join('')}</div>${entity === 'challenges' ? '<div class="section-subheading"><h3>Challenge items</h3><span class="muted">Correct order: 1 → 5</span></div><p class="form-hint">Five items required. Edit, remove or move items up to set the answer.</p><div id="editor-items"></div><button type="button" class="table-button" data-action="add-item">+ Add item</button>' : ''}<p class="form-message" role="status"></p><div class="dialog-actions"><button type="button" class="button button-outline button-small" data-action="close-dialog">Cancel</button>${entity === 'challenges' ? '<button type="button" class="button button-gold button-small" data-action="approve-challenge">Approve & publish</button>' : ''}<button type="submit" class="button button-purple button-small">Save changes</button></div></form>`);
  if (entity === 'challenges') renderItemFields(row ? row.items : ['', '', '', '', '']);
}

function saveRecord(form) {
  const entity = form.dataset.entity;
  const id = Number(form.dataset.id);
  const data = new FormData(form);
  const record = {};
  const message = form.querySelector('.form-message');
  for (const [key, value] of data.entries()) {
    if (key !== 'item') record[key] = value.trim();
  }
  if (Object.values(record).some(value => !value)) {
    message.textContent = 'Please complete all fields with more than spaces.';
    return;
  }
  if (entity === 'challenges') {
    record.items = data.getAll('item').map(value => value.trim());
    if (record.items.length !== 5 || record.items.some(value => !value) || new Set(record.items.map(value => value.toLowerCase())).size !== 5) {
      message.textContent = 'Add exactly five different, non-empty items.';
      return;
    }
  } else {
    entityFields[entity].filter(field => field.type === 'number').forEach(field => { record[field.name] = Number(record[field.name]); });
  }
  record.id = id || Math.max(0, ...adminData[entity].map(row => row.id)) + 1;
  if (id) adminData[entity] = adminData[entity].map(row => row.id === id ? record : row);
  else adminData[entity].push(record);
  dialog.close();
  renderAdmin();
  showToast('Changes applied.');
}

document.addEventListener('click', event => {
  const button = event.target.closest('button, a');
  if (!button) return;
  if (button.closest('.site-header') && button.matches('a')) {
    setMobileMenu(false, false);
    if (button.getAttribute('href') === location.hash) app.focus();
  }
  if (button.classList.contains('skip-link')) {
    event.preventDefault();
    app.focus();
    return;
  }
  if (button.dataset.period) {
    leaderboardPeriod = button.dataset.period;
    renderLeaderboard();
  }
  if (button.dataset.filter) {
    challengeFilter = button.dataset.filter;
    renderAdmin();
    document.querySelector(`[data-filter="${challengeFilter}"]`).focus();
  }
  if (button.dataset.history !== undefined) {
    const entry = historyData[Number(button.dataset.history)];
    openDialog(`<div class="dialog-heading"><h2 id="dialog-title">${entry.title}</h2><button class="icon-button" data-action="close-dialog" aria-label="Close dialog">×</button></div><p class="muted">${entry.date}</p><p class="history-score">${entry.score}<small> / 100</small></p><p>${entry.score / 20} of 5 positions were correct.</p><a class="button button-purple" href="#/play">Play today’s challenge →</a>`);
  }
  const action = button.dataset.action;
  const id = Number(button.dataset.id);
  if (action === 'toggle-menu') setMobileMenu(menuToggle.getAttribute('aria-expanded') !== 'true');
  if (action === 'retry-view') renderRoute();
  if (action === 'how') document.querySelector('#how-to').scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
  if (action === 'move-up') moveItem(id, -1);
  if (action === 'move-down') moveItem(id, 1);
  if (action === 'shuffle' && !demoAttempt) {
    for (let i = ranking.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [ranking[i], ranking[j]] = [ranking[j], ranking[i]];
    }
    renderRanking();
    document.querySelector('#game-status').textContent = 'Fresh order. What would you change?';
  }
  if (action === 'submit-ranking') submitRanking();
  if (action === 'new-record') openEditor();
  if (action === 'edit-record') openEditor(id);
  if (action === 'close-dialog') dialog.close();
  if (action === 'approve-challenge') {
    const form = document.querySelector('#record-form');
    form.elements.status.value = 'Published';
    form.requestSubmit();
  }
  if (['add-item', 'remove-item', 'editor-up'].includes(action)) {
    const items = [...document.querySelectorAll('#editor-items input')].map(input => input.value);
    const index = Number(button.dataset.index);
    if (action === 'add-item' && items.length < 5) items.push('');
    if (action === 'remove-item') items.splice(index, 1);
    if (action === 'editor-up' && index > 0) [items[index - 1], items[index]] = [items[index], items[index - 1]];
    renderItemFields(items);
  }
  if (action === 'delete-record') {
    openDialog(`<div class="dialog-heading"><h2 id="dialog-title">Delete record #${id}?</h2></div><p>Remove this record from the list?</p><div class="dialog-actions"><button class="button button-outline button-small" data-action="close-dialog">Cancel</button><button class="button button-danger button-small" data-action="confirm-delete" data-id="${id}">Delete record</button></div>`);
  }
  if (action === 'confirm-delete') {
    const entity = currentAdminView();
    adminData[entity] = adminData[entity].filter(row => row.id !== id);
    dialog.close();
    renderAdmin();
    showToast('Record removed.');
  }
  if (action === 'ai-placeholder') document.querySelector('.ai-message').textContent = 'Choose a topic → review the facts and order → edit the challenge → approve for publication.';
});

document.addEventListener('change', event => {
  if (event.target.id === 'admin-section') location.hash = `#/admin/${event.target.value}`;
});

document.addEventListener('submit', event => {
  if (!['login-form', 'register-form', 'record-form'].includes(event.target.id)) return;
  event.preventDefault();
  const form = event.target;
  if (form.id === 'record-form') return saveRecord(form);
  const message = form.querySelector('.form-message');
  if (form.id === 'register-form' && form.elements.password.value !== form.elements.confirm.value) {
    message.textContent = 'The passwords don’t match. Please try again.';
    form.elements.confirm.focus();
    return;
  }
  form.reset();
  message.textContent = form.id === 'login-form' ? 'Sign-in is currently unavailable. You can still play today’s challenge.' : 'Registration is currently unavailable. You can still play today’s challenge.';
});

document.addEventListener('keydown', event => {
  if (menuToggle.getAttribute('aria-expanded') === 'true') {
    if (event.key === 'Escape') setMobileMenu(false);
    if (event.key === 'Tab') {
      const links = [...document.querySelectorAll('.site-header a, .site-header button')].filter(link => link.getClientRects().length);
      const first = links[0];
      const last = links[links.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
    return;
  }
  if (!event.target.matches('[data-period]') || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
  event.preventDefault();
  const tabs = [...document.querySelectorAll('[data-period]')];
  const index = tabs.indexOf(event.target);
  const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
  tabs[next].click();
  tabs[next].focus();
});

app.addEventListener('dragstart', event => {
  const row = event.target.closest('.ranking-row');
  if (!row || demoAttempt) return;
  draggedItem = Number(row.dataset.id);
  event.dataTransfer.setData('text/plain', String(draggedItem));
  event.dataTransfer.effectAllowed = 'move';
  row.classList.add('dragging');
});
app.addEventListener('dragover', event => {
  if (!demoAttempt && draggedItem !== null && event.target.closest('.ranking-row')) event.preventDefault();
});
app.addEventListener('drop', event => {
  const row = event.target.closest('.ranking-row');
  if (!row || demoAttempt || draggedItem === null) return;
  event.preventDefault();
  const from = ranking.indexOf(draggedItem);
  const to = ranking.indexOf(Number(row.dataset.id));
  ranking.splice(from, 1);
  ranking.splice(to, 0, draggedItem);
  const movedId = draggedItem;
  draggedItem = null;
  renderRanking(movedId);
  document.querySelector('#game-status').textContent = 'Order updated. Looking good?';
});
app.addEventListener('dragend', () => {
  draggedItem = null;
  document.querySelectorAll('.dragging').forEach(row => row.classList.remove('dragging'));
});

menuMedia.addEventListener('change', () => {
  const needsFocus = navigation.contains(document.activeElement) || document.activeElement === document.body;
  setMobileMenu(false, false);
  if (needsFocus) {
    if (menuMedia.matches) menuToggle.focus();
    else document.querySelector('.brand').focus();
  }
});
setMobileMenu(false, false);
window.addEventListener('hashchange', renderRoute);
if (!location.hash) location.replace('#/home');
else renderRoute();
