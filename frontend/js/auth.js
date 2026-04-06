alert('auth.js loaded');
console.log('auth.js loaded');

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('login-form');
  if (!form) return;
form.addEventListener('submit', async (e) => {
  e.preventDefault();

  const emailInput = document.getElementById('email');
  const passwordInput = document.getElementById('password');
  const errorEl = document.getElementById('login-error');

  console.log('emailInput:', emailInput);
  console.log('passwordInput:', passwordInput);

  const email = emailInput ? emailInput.value.trim() : '';
  const password = passwordInput ? passwordInput.value : '';

  console.log('Email value:', email);
  console.log('Password value:', password);

  if (errorEl) errorEl.textContent = '';

  try {
    const response = await fetch(`${API_BASE}/auth/login.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      credentials: 'include',
      body: JSON.stringify({ email, password })
    });

    const data = await response.json();

    console.log('Response status:', response.status);
    console.log('Response data:', data);

    if (!response.ok) {
      if (errorEl) errorEl.textContent = data.error || 'Login failed';
      return;
    }

    const role = (data.user?.role || '').trim().toLowerCase();

    if (role === 'owner') {
      window.location.href = 'owner.html';
    } else if (role === 'fournisseur') {
      window.location.href = 'supplier.html';
    } else if (role === 'client') {
      window.location.href = 'client.html';
    } else {
      if (errorEl) errorEl.textContent = `Unknown user role: ${role}`;
    }

  } catch (error) {
    console.error(error);
    if (errorEl) errorEl.textContent = 'Network or server error';
  }
});
});