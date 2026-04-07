(function () {
  /* ── DOM refs ──────────────────────────────────────────────────── */
  const form        = document.getElementById('secureForm');
  const loader      = document.getElementById('loaderOverlay');
  const msgField    = document.getElementById('message');
  const counter     = document.getElementById('messageCounter');
  const pwInput     = document.getElementById('password');
  const pwRules     = document.getElementById('passwordRules');
  const limit       = 250;

  /* ── Regex ─────────────────────────────────────────────────────── */
  const nameRegex  = /^[\p{L}][\p{L}\p{M}\s'\-]*[\p{L}]$/u;
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const pwChecks = {
    length:  (v) => v.length >= 8,
    lower:   (v) => /[a-z]/.test(v),
    upper:   (v) => /[A-Z]/.test(v),
    digit:   (v) => /\d/.test(v),
    special: (v) => /[^a-zA-Z0-9]/.test(v),
  };

  /* ── Progress bar helpers ──────────────────────────────────────── */
  const getErrorEl = (name) =>
    document.querySelector(`[data-error-for="${name}"]`);

  /**
   * Set the progress bar fill for a field.
   * Only applies when the error div is "empty" (no error text).
   * @param {string} name - field name
   * @param {number} percent - 0–100
   * @param {boolean} complete - true → add glow data-attr
   */
  const setBarProgress = (name, percent, complete = false) => {
    const el = getErrorEl(name);
    if (!el || el.textContent.trim()) return; // skip if showing an error
    el.style.setProperty('--progress', `${Math.min(100, Math.max(0, percent))}%`);
    el.dataset.complete = complete ? 'true' : 'false';
  };

  /** Refresh bar after an error is cleared */
  const refreshBar = (name, percent, complete) => {
    const el = getErrorEl(name);
    if (!el) return;
    // Give DOM a tick so :empty applies first
    requestAnimationFrame(() => setBarProgress(name, percent, complete));
  };

  /* ── Error display ─────────────────────────────────────────────── */
  const showError = (input, text) => {
    const target = getErrorEl(input.name);
    if (target) {
      target.style.removeProperty('--progress');
      target.dataset.complete = 'false';
      target.textContent = text;
    }
    input.setAttribute('aria-invalid', 'true');
  };

  const clearError = (input) => {
    const target = getErrorEl(input.name);
    if (target) target.textContent = '';
    input.removeAttribute('aria-invalid');
  };

  /* ── Progress calculators ──────────────────────────────────────── */
  const calcNameProgress = (value) => {
    const v = value.trim().replace(/\s+/g, ' ');
    if (!v) return 0;
    if (v.length < 2) return 20;
    if (v.length > 100) return 45;
    if (!nameRegex.test(v)) return 55;
    return 100;
  };

  const calcEmailProgress = (value) => {
    const v = value.trim();
    if (!v) return 0;
    if (!v.includes('@')) return 20;
    const [local, domain] = v.split('@');
    if (!local || !domain) return 35;
    if (!domain.includes('.')) return 55;
    if (!emailRegex.test(v)) return 65;
    if (v.length > 255) return 40;
    return 100;
  };

  const calcPasswordProgress = (value) => {
    if (!value) return 0;
    const met = Object.values(pwChecks).filter((fn) => fn(value)).length;
    return (met / 5) * 100;
  };

  const calcMessageProgress = (value) => {
    const len = value.trim().length;
    if (!len) return 0;
    return Math.min(100, (len / limit) * 100);
  };

  /* ── Password rules panel ──────────────────────────────────────── */
  const updatePwRules = (value) => {
    if (!pwRules) return;
    Object.entries(pwChecks).forEach(([key, fn]) => {
      const li = document.getElementById(`rule-${key}`);
      if (li) li.classList.toggle('met', fn(value));
    });
    const progress = calcPasswordProgress(value);
    setBarProgress('password', progress, progress === 100);
  };

  if (pwInput && pwRules) {
    pwInput.addEventListener('focus', () => {
      pwRules.classList.add('visible');
      updatePwRules(pwInput.value);
    });
    pwInput.addEventListener('blur', () => {
      // Keep open if there are unmet rules (helps the user)
      const allMet = Object.values(pwChecks).every((fn) => fn(pwInput.value));
      if (allMet) pwRules.classList.remove('visible');
    });
    pwInput.addEventListener('input', () => {
      clearError(pwInput);
      updatePwRules(pwInput.value);
    });
  }

  /* ── Message counter & progress ───────────────────────────────── */
  if (msgField && counter) {
    const updateCounter = () => {
      const len = msgField.value.length;
      const remaining = limit - len;
      counter.textContent = `${len}/${limit} caracteres`;
      counter.style.color = remaining < 20 ? '#ff7b90' : '';
      const progress = calcMessageProgress(msgField.value);
      setBarProgress('message', progress, progress >= 100);
    };
    msgField.addEventListener('input', () => {
      clearError(msgField);
      updateCounter();
    });
    updateCounter();
  }

  /* ── Name live progress ────────────────────────────────────────── */
  const nameInput = form ? form.querySelector('[name="name"]') : null;
  if (nameInput) {
    nameInput.addEventListener('input', () => {
      clearError(nameInput);
      const p = calcNameProgress(nameInput.value);
      setBarProgress('name', p, p === 100);
    });
  }

  /* ── Email live progress ───────────────────────────────────────── */
  const emailInput = form ? form.querySelector('[name="email"]') : null;
  if (emailInput) {
    emailInput.addEventListener('input', () => {
      clearError(emailInput);
      const p = calcEmailProgress(emailInput.value);
      setBarProgress('email', p, p === 100);
    });
  }

  /* ── Full validation (returns true if all ok) ──────────────────── */
  const validate = () => {
    if (!form) return true;

    const fields = Array.from(form.querySelectorAll('[data-validate]'));
    fields.forEach((f) => clearError(f));

    const name     = form.querySelector('[name="name"]');
    const email    = form.querySelector('[name="email"]');
    const password = form.querySelector('[name="password"]');
    const message  = form.querySelector('[name="message"]');

    let valid = true;
    let firstBad = null;

    const fail = (input, msg) => {
      showError(input, msg);
      if (!firstBad) firstBad = input;
      valid = false;
    };

    /* --- Name --- */
    const nameVal = name.value.trim().replace(/\s+/g, ' ');
    if (!nameVal)              fail(name, 'O nome é obrigatório.');
    else if (nameVal.length < 2)   fail(name, 'O nome deve ter no mínimo 2 caracteres.');
    else if (nameVal.length > 100) fail(name, 'O nome deve ter no máximo 100 caracteres.');
    else if (!nameRegex.test(nameVal))
      fail(name, 'O nome deve conter apenas letras, espaços, hífen ou apóstrofo.');
    else setBarProgress('name', 100, true);

    /* --- Email --- */
    const emailVal = email.value.trim();
    if (!emailVal)             fail(email, 'O e-mail é obrigatório.');
    else if (!emailRegex.test(emailVal))
      fail(email, 'O e-mail informado não possui um formato válido.');
    else if (emailVal.length > 255)
      fail(email, 'O e-mail deve ter no máximo 255 caracteres.');
    else setBarProgress('email', 100, true);

    /* --- Password --- */
    const pwVal = password.value;
    if (!pwVal)               fail(password, 'A senha é obrigatória.');
    else if (pwVal.length < 8)    fail(password, 'A senha deve ter no mínimo 8 caracteres.');
    else if (pwVal.length > 64)   fail(password, 'A senha deve ter no máximo 64 caracteres.');
    else if (!pwChecks.lower(pwVal))   fail(password, 'A senha deve conter ao menos uma letra minúscula.');
    else if (!pwChecks.upper(pwVal))   fail(password, 'A senha deve conter ao menos uma letra maiúscula.');
    else if (!pwChecks.digit(pwVal))   fail(password, 'A senha deve conter ao menos um número.');
    else if (!pwChecks.special(pwVal)) fail(password, 'A senha deve conter ao menos um caractere especial.');
    else {
      setBarProgress('password', 100, true);
      if (pwRules) pwRules.classList.remove('visible');
    }

    /* --- Message --- */
    const msgVal = message.value.trim();
    if (!msgVal)              fail(message, 'A mensagem é obrigatória.');
    else if (msgVal.length < 3)  fail(message, 'A mensagem deve ter no mínimo 3 caracteres.');
    else if (msgVal.length > limit)
      fail(message, 'A mensagem deve ter no máximo 250 caracteres.');
    else setBarProgress('message', 100, true);

    /* --- Scroll to first error --- */
    if (firstBad) {
      firstBad.closest('.field')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      setTimeout(() => firstBad.focus({ preventScroll: true }), 420);
    }

    return valid;
  };

  /* ── Form submit ───────────────────────────────────────────────── */
  if (form) {
    form.addEventListener('submit', (e) => {
      if (!validate()) {
        e.preventDefault();
        return;
      }
      if (loader) loader.classList.add('active');
    });

    /* Blur-based field-level feedback */
    const allInputs = form.querySelectorAll('input:not([type="hidden"]), textarea');
    allInputs.forEach((input) => {
      /* On blur: run field-specific progress refresh */
      input.addEventListener('blur', () => {
        const n = input.name;
        if (!input.getAttribute('aria-invalid')) {
          // If no current error, update progress on blur
          if (n === 'name')    setBarProgress(n, calcNameProgress(input.value),    calcNameProgress(input.value) === 100);
          if (n === 'email')   setBarProgress(n, calcEmailProgress(input.value),   calcEmailProgress(input.value) === 100);
          if (n === 'message') setBarProgress(n, calcMessageProgress(input.value), calcMessageProgress(input.value) >= 100);
          if (n === 'password') {
            const p = calcPasswordProgress(input.value);
            setBarProgress(n, p, p === 100);
          }
        }
      });
    });
  }

  /* ── Init progress bars from server-rendered values ───────────── */
  window.addEventListener('DOMContentLoaded', () => {
    if (!form) return;

    const nameEl    = form.querySelector('[name="name"]');
    const emailEl   = form.querySelector('[name="email"]');
    const messageEl = form.querySelector('[name="message"]');
    const pwEl      = form.querySelector('[name="password"]');

    if (nameEl?.value)    setBarProgress('name',    calcNameProgress(nameEl.value),       false);
    if (emailEl?.value)   setBarProgress('email',   calcEmailProgress(emailEl.value),     false);
    if (messageEl?.value) setBarProgress('message', calcMessageProgress(messageEl.value), false);
    if (pwEl?.value)      updatePwRules(pwEl.value);

    /* If PHP returned errors, scroll to first visible error field */
    const firstInvalid = form.querySelector('[aria-invalid="true"]');
    if (firstInvalid) {
      setTimeout(() => {
        firstInvalid.closest('.field')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 200);
    }

    /* If PHP returned errors in fields without aria-invalid (from the global error list),
       scroll to the form panel */
    const globalErr = document.querySelector('.global-error');
    if (globalErr && !firstInvalid) {
      setTimeout(() => globalErr.scrollIntoView({ behavior: 'smooth', block: 'center' }), 200);
    }
  });

})();

/* ── Delete Modal ──────────────────────────────────────────────────── */
function openDeleteModal(btn) {
  const id   = btn.dataset.id;
  const name = btn.dataset.name;
  document.getElementById('deleteRecordId').value = id;
  document.getElementById('deleteModalName').textContent = name;
  document.getElementById('deleteStep1').classList.remove('hidden');
  document.getElementById('deleteStep2').classList.add('hidden');
  const modal = document.getElementById('deleteModal');
  modal.setAttribute('aria-hidden', 'false');
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function deleteStep2() {
  document.getElementById('deleteStep1').classList.add('hidden');
  document.getElementById('deleteStep2').classList.remove('hidden');
}

function closeDeleteModal() {
  const modal = document.getElementById('deleteModal');
  modal.setAttribute('aria-hidden', 'true');
  modal.classList.remove('active');
  document.body.style.overflow = '';
  setTimeout(() => {
    document.getElementById('deleteStep1').classList.remove('hidden');
    document.getElementById('deleteStep2').classList.add('hidden');
  }, 300);
}

/* ── Edit Modal ────────────────────────────────────────────────────── */
function openEditModal(btn) {
  openEditModalWithData(
    btn.dataset.id,
    btn.dataset.name,
    btn.dataset.email,
    btn.dataset.message
  );
}

function openEditModalWithData(id, name, email, message) {
  document.getElementById('editRecordId').value    = id;
  document.getElementById('edit_name').value       = name;
  document.getElementById('edit_email').value      = email;
  document.getElementById('edit_message').value    = message;
  updateEditCounter();
  const modal = document.getElementById('editModal');
  modal.setAttribute('aria-hidden', 'false');
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
  setTimeout(() => document.getElementById('edit_name').focus(), 100);
}

function closeEditModal() {
  const modal = document.getElementById('editModal');
  modal.setAttribute('aria-hidden', 'true');
  modal.classList.remove('active');
  document.body.style.overflow = '';
}

function updateEditCounter() {
  const ta  = document.getElementById('edit_message');
  const cnt = document.getElementById('editMessageCounter');
  if (!ta || !cnt) return;
  const len = ta.value.length;
  cnt.textContent = len + '/250 caracteres';
  cnt.style.color = (250 - len) < 20 ? '#ff7b90' : '';
}

document.addEventListener('DOMContentLoaded', function () {
  const ta = document.getElementById('edit_message');
  if (ta) ta.addEventListener('input', updateEditCounter);

  /* Close modals on overlay click */
  document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
  });
  document.getElementById('editModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
  });

  /* Close modals on Escape */
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeDeleteModal();
      closeEditModal();
    }
  });
});
