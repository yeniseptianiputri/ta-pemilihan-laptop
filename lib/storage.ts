import { Preference } from "./types";

const STORAGE_KEY = "spk-laptop-preference";

export const savePreference = (preference: Preference) => {
  if (typeof window === "undefined") return;
  localStorage.setItem(STORAGE_KEY, JSON.stringify(preference));
};

export const loadPreference = (): Preference | null => {
  if (typeof window === "undefined") return null;
  const raw = localStorage.getItem(STORAGE_KEY);
  if (!raw) return null;

  try {
    return JSON.parse(raw) as Preference;
  } catch (error) {
    console.error("Failed to parse preference", error);
    return null;
  }
};

export const clearPreference = () => {
  if (typeof window === "undefined") return;
  localStorage.removeItem(STORAGE_KEY);
};
