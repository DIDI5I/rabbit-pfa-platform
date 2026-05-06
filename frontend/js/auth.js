/* ============================================================
   auth.js — Rabbit B2B MRO Platform
   Gestion de session. Le backend utilise les cookies PHP.
   Tout doit passer par credentials: "include".
   ============================================================ */

let _currentUser = null;

// ── Récupère l'utilisateur courant ─────────────────────────────
async function getCurrentUser() {
  if (_currentUser) return _currentUser;

  const stored = localStorage.getItem("rabbit_user");

  if (stored) {
    try {
      _currentUser = JSON.parse(stored);
      return _currentUser;
    } catch {
      localStorage.removeItem("rabbit_user");
    }
  }

  try {
    const data = await apiFetchJson("/user");
    _currentUser = unwrap(data);

    if (_currentUser) {
      localStorage.setItem("rabbit_user", JSON.stringify(_currentUser));
    }

    return _currentUser;
  } catch {
    return null;
  }
}

// ── Vérifie l'auth et redirige si nécessaire ──────────────────
// allowedRoles: string | string[] | null (null = tout rôle authentifié)
async function checkAuth(allowedRoles = null) {
  const user = await getCurrentUser();

  if (!user) {
    window.location.href = "../login.html";
    return null;
  }

  if (allowedRoles) {
    const roles = Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles];
    const userRole = user.role === "fournisseur" ? "supplier" : user.role;
    const normalised = roles.map(r => r === "fournisseur" ? "supplier" : r);

    if (!normalised.includes(userRole)) {
      window.location.href = roleRedirectUrl(userRole);
      return null;
    }
  }

  return user;
}

// ── Redirige selon le rôle ─────────────────────────────────────
  function roleRedirectUrl(role) {
  const map = {
    owner: "pages/owner.html",
    client: "pages/client.html",
    supplier: "pages/supplier.html",
    fournisseur: "pages/supplier.html",
  };

  return map[role] || "../login.html";
}

// ── Login ──────────────────────────────────────────────────────
async function login(email, password) {
  const data = await apiFetchJson("/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  });

  const user = unwrap(data)?.user || unwrap(data);

  if (user) {
    _currentUser = user;
    localStorage.setItem("rabbit_user", JSON.stringify(user));
    return user;
  }

  throw new Error(L.login.error);
}
// ── Logout ─────────────────────────────────────────────────────
async function logout() {
  _currentUser = null;
  localStorage.removeItem("rabbit_user");

  try {
    await apiFetchJson("/logout", { method: "POST" });
  } catch {}

  window.location.href = "../login.html";
}

// ── Seed user display ──────────────────────────────────────────
function seedUserDisplay(user) {
  if (!user) return;
  const initials = (user.name || "RB").slice(0, 2).toUpperCase();
  const roleLabel = L.roles[user.role] || user.role;

  document.querySelectorAll("[data-user-initials]").forEach(el => el.textContent = initials);
  document.querySelectorAll("[data-user-name]").forEach(el => el.textContent = user.name || "");
  document.querySelectorAll("[data-user-role]").forEach(el => el.textContent = roleLabel);
  document.querySelectorAll("[data-user-company]").forEach(el => el.textContent = user.company_name || "");
}
