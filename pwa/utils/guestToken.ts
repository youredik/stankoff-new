const STORAGE_KEY = 'guest_access_token';

export const getGuestTokenFromUrl = (): string | null => {
  if (typeof window === 'undefined') {
    return null;
  }

  const params = new URLSearchParams(window.location.search);
  const token = params.get('access_token') || params.get('guest_token');
  return token && token.trim() ? token.trim() : null;
};

export const setGuestToken = (token: string | null): void => {
  if (typeof window === 'undefined') {
    return;
  }

  if (!token) {
    sessionStorage.removeItem(STORAGE_KEY);
    return;
  }

  sessionStorage.setItem(STORAGE_KEY, token);
};

export const getGuestToken = (): string | null => {
  if (typeof window === 'undefined') {
    return null;
  }

  const fromUrl = getGuestTokenFromUrl();
  if (fromUrl) {
    setGuestToken(fromUrl);
    return fromUrl;
  }

  const stored = sessionStorage.getItem(STORAGE_KEY);
  return stored && stored.trim() ? stored.trim() : null;
};

export const isGuestMode = (): boolean => Boolean(getGuestToken());
