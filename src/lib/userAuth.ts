export type UserAccount = {
  email: string;
  password: string;
  name?: string;
  createdAt: string;
};

const STORAGE_KEY = "spk-user-auth-v1";

const isBrowser = () => typeof window !== "undefined";

const normalizeEmail = (email: string) => email.trim().toLowerCase();

const getDefaultUsers = (): UserAccount[] => {
  const email = process.env.NEXT_PUBLIC_USER_EMAIL;
  const password = process.env.NEXT_PUBLIC_USER_PASSWORD;
  if (!email || !password) {
    return [];
  }
  return [
    {
      email,
      password,
      name: "User Default",
      createdAt: "1970-01-01T00:00:00.000Z",
    },
  ];
};

const mergeUsers = (base: UserAccount[], stored: UserAccount[]) => {
  const map = new Map<string, UserAccount>();
  base.forEach((user) => map.set(normalizeEmail(user.email), user));
  stored.forEach((user) => map.set(normalizeEmail(user.email), user));
  return Array.from(map.values());
};

export const loadUsers = (): UserAccount[] => {
  const defaults = getDefaultUsers();
  if (!isBrowser()) {
    return defaults;
  }

  const raw = window.localStorage.getItem(STORAGE_KEY);
  if (!raw) {
    return defaults;
  }

  try {
    const parsed = JSON.parse(raw) as UserAccount[];
    if (!Array.isArray(parsed)) {
      return defaults;
    }
    return mergeUsers(defaults, parsed);
  } catch {
    return defaults;
  }
};

export const saveUsers = (users: UserAccount[]) => {
  if (!isBrowser()) {
    return;
  }
  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(users));
};

export const registerUser = (payload: {
  email: string;
  password: string;
  name?: string;
}) => {
  const email = payload.email.trim();
  const password = payload.password;
  if (!email || !password) {
    return { ok: false, error: "Email dan password wajib diisi." };
  }

  const users = loadUsers();
  const exists = users.some(
    (user) => normalizeEmail(user.email) === normalizeEmail(email)
  );
  if (exists) {
    return { ok: false, error: "Email sudah terdaftar." };
  }

  const next: UserAccount = {
    email,
    password,
    name: payload.name?.trim() || undefined,
    createdAt: new Date().toISOString(),
  };
  const updated = [next, ...users];
  saveUsers(updated);
  return { ok: true };
};

export const validateLogin = (email: string, password: string) => {
  const users = loadUsers();
  const user = users.find(
    (item) => normalizeEmail(item.email) === normalizeEmail(email)
  );
  if (!user) {
    return { ok: false, error: "Email belum terdaftar." };
  }
  if (user.password !== password) {
    return { ok: false, error: "Password salah." };
  }
  return { ok: true, user };
};
