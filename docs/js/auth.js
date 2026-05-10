/* ============================================================
   auth.js — Rabbit B2B MRO Platform
   Depends on config.js
   Backend role: "owner" | "client" | "fournisseur"
   ============================================================ */

const ROLE_PAGES = {
  owner: "owner.html",
  admin: "owner.html",
  client: "client.html",
  fournisseur: "supplier.html",
  supplier: "supplier.html",
};

const PAGE_ROLES = {
  "owner.html": ["owner", "admin"],
  "client.html": ["client"],
  "supplier.html": ["fournisseur", "supplier"],
};

function extractUserFromResponse(response) {
  const payload = response?.data ?? response;
  return payload?.user ?? payload;
}

async function login() {
  const emailEl = document.getElementById("login-email");
  const passwordEl = document.getElementById("login-password");
  const btnEl = document.getElementById("do-login");
  const errEl = document.getElementById("login-error");

  if (!emailEl || !passwordEl) return;

  const email = emailEl.value.trim();
  const password = passwordEl.value;

  if (!email || !password) {
    showLoginError("Veuillez renseigner votre email et mot de passe.");
    return;
  }

  if (btnEl) {
    btnEl.disabled = true;
    btnEl.textContent = "Connexion…";
  }

  if (errEl) {
    errEl.textContent = "";
    errEl.style.display = "none";
  }

  try {
    const loginRes = await fetch(apiUrl("/login"), {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      "Accept": "application/json",
    },
    body: JSON.stringify({
      email: email,
      password: password,
    }),
  });
    const data = await loginRes.json().catch(() => ({}));

    if (!loginRes.ok || data.error) {
      throw data;
    }

    const user = extractUserFromResponse(data);
    const role = user?.role;

    console.log("LOGIN RESPONSE:", data);
    console.log("EXTRACTED USER:", user);
    console.log("EXTRACTED ROLE:", role);

    if (!role) {
      throw { error: "Rôle manquant dans la réponse serveur." };
    }

    const dest = ROLE_PAGES[role];

    if (!dest) {
      throw { error: `Rôle non reconnu : "${role}"` };
    }

    sessionStorage.setItem("rabbit_role", role);
    sessionStorage.setItem("rabbit_user", JSON.stringify(user));

    window.location.href = dest;
  } catch (err) {
    showLoginError(formatAuthError(err, "Erreur de connexion. Réessayez."));
  } finally {
    if (btnEl) {
      btnEl.disabled = false;
      btnEl.textContent = "Se connecter →";
    }
  }
}

async function signup() {
  const nameEl = document.getElementById("signup-name");
  const emailEl = document.getElementById("signup-email");
  const roleEl = document.getElementById("signup-role");
  const passwordEl = document.getElementById("signup-password");
  const confirmEl = document.getElementById("signup-confirm");
  const btnEl = document.getElementById("do-signup");
  const errEl = document.getElementById("signup-error");

  if (!nameEl || !emailEl || !roleEl || !passwordEl || !confirmEl) return;

  const name = nameEl.value.trim();
  const email = emailEl.value.trim();
  const role = roleEl.value;
  const password = passwordEl.value;
  const confirm = confirmEl.value;

  if (!name || !email || !role || !password || !confirm) {
    showSignupError("Veuillez remplir tous les champs.");
    return;
  }

  if (password !== confirm) {
    showSignupError("Les mots de passe ne correspondent pas.");
    return;
  }

  if (password.length < 6) {
    showSignupError("Le mot de passe doit contenir au moins 6 caractères.");
    return;
  }

  if (btnEl) {
    btnEl.disabled = true;
    btnEl.textContent = "Inscription…";
  }

  if (errEl) {
    errEl.textContent = "";
    errEl.style.display = "none";
  }

  try {
    const formBody = new FormData();
    formBody.append("name", name);
    formBody.append("email", email);
    formBody.append("role", role);
    formBody.append("password", password);

    const signupRes = await fetch(apiUrl("/register"), {
      method: "POST",
      credentials: "include",
      headers: {
        Accept: "application/json",
      },
      body: formBody,
    });

    const data = await signupRes.json().catch(() => ({}));

    if (!signupRes.ok || data.error) {
      throw data;
    }

    showToast("Compte créé avec succès ! Connectez-vous.", "success");
    window.location.href = "login.html";
  } catch (err) {
    showSignupError(formatAuthError(err, "Erreur lors de l'inscription. Réessayez."));
  } finally {
    if (btnEl) {
      btnEl.disabled = false;
      btnEl.textContent = "Créer mon compte →";
    }
  }
}

async function logout() {
  try {
    await apiFetchJson("/logout", { method: "POST" });
  } catch (_) {}

  sessionStorage.clear();
  window.location.href = "login.html";
}
async function checkAuth(expectedRole = null) {
  try {
    // Try backend session check first, if /me exists later.
    const data = await apiFetchJson("/me");

    const user = extractUserFromResponse(data);
    const role = user?.role;

    if (!role) {
      redirectToLogin();
      return null;
    }

    if (expectedRole) {
      const expectedPage = ROLE_PAGES[expectedRole];
      const allowed = PAGE_ROLES[expectedPage] || [expectedRole];

      if (!allowed.includes(role)) {
        window.location.href = ROLE_PAGES[role] || "login.html";
        return null;
      }
    }

    sessionStorage.setItem("rabbit_role", role);
    sessionStorage.setItem("rabbit_user", JSON.stringify(user));

    return user;

  } catch (err) {
    console.warn("/me unavailable, using sessionStorage fallback:", err);

    const storedUser = getCurrentUser();
    const storedRole = getCurrentRole() || storedUser?.role;

    if (!storedUser || !storedRole) {
      redirectToLogin();
      return null;
    }

    if (expectedRole) {
      const expectedPage = ROLE_PAGES[expectedRole];
      const allowed = PAGE_ROLES[expectedPage] || [expectedRole];

      if (!allowed.includes(storedRole)) {
        window.location.href = ROLE_PAGES[storedRole] || "login.html";
        return null;
      }
    }

    return storedUser;
  }
}

function showSignupError(msg) {
  const el = document.getElementById("signup-error");
  if (el) {
    el.textContent = msg;
    el.style.display = "block";
  }
}

function showLoginError(msg) {
  const el = document.getElementById("login-error");
  if (el) {
    el.textContent = msg;
    el.style.display = "block";
  }
}

function formatAuthError(err, fallback) {
  if (!err) return fallback;

  if (typeof err === "string") return err;

  if (err.fields && typeof err.fields === "object") {
    return Object.entries(err.fields)
      .flatMap(([field, messages]) => {
        const list = Array.isArray(messages) ? messages : [messages];
        return list.map((msg) => `${field}: ${msg}`);
      })
      .join("\n");
  }

  return err.message || err.error || fallback;
}

function redirectToLogin() {
  try {
    sessionStorage.clear();
  } catch (_) {}

  window.location.href = "login.html";
}

function getCurrentUser() {
  try {
    return JSON.parse(sessionStorage.getItem("rabbit_user")) || null;
  } catch (_) {
    return null;
  }
}

function getCurrentRole() {
  return sessionStorage.getItem("rabbit_role") || null;
}

document.addEventListener("DOMContentLoaded", () => {
  const loginBtn = document.getElementById("do-login");
  if (loginBtn) {
    loginBtn.addEventListener("click", login);
  }

  const loginPwd = document.getElementById("login-password");
  if (loginPwd) {
    loginPwd.addEventListener("keydown", (e) => {
      if (e.key === "Enter") login();
    });
  }

  const signupBtn = document.getElementById("do-signup");
  if (signupBtn) {
    signupBtn.addEventListener("click", signup);
  }

  const signupPwd = document.getElementById("signup-confirm");
  if (signupPwd) {
    signupPwd.addEventListener("keydown", (e) => {
      if (e.key === "Enter") signup();
    });
  }
});