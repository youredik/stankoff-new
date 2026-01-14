import {getSession} from 'next-auth/react';
import {type Session} from '../app/auth';

export const authenticatedFetch = async (url: string, options: RequestInit = {}): Promise<Response> => {
  const session = await getSession() as Session | null;
  const token = session?.accessToken;

  return fetch(url, {
    ...options,
    headers: {
      ...options.headers,
      'Authorization': `Bearer ${token}`,
    },
  });
};
