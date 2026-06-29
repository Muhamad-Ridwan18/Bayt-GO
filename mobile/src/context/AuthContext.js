import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as authApi from '../api/auth';

const TOKEN_KEY = '@baytgo_auth_token';
const USER_KEY = '@baytgo_auth_user';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [token, setToken] = useState(null);
  const [user, setUser] = useState(null);
  const [booting, setBooting] = useState(true);

  useEffect(() => {
    Promise.all([AsyncStorage.getItem(TOKEN_KEY), AsyncStorage.getItem(USER_KEY)])
      .then(([storedToken, storedUser]) => {
        if (storedToken && storedUser) {
          setToken(storedToken);
          setUser(JSON.parse(storedUser));
        }
      })
      .finally(() => setBooting(false));
  }, []);

  const persistSession = useCallback(async (sessionToken, sessionUser) => {
    setToken(sessionToken);
    setUser(sessionUser);
    await AsyncStorage.setItem(TOKEN_KEY, sessionToken);
    await AsyncStorage.setItem(USER_KEY, JSON.stringify(sessionUser));
  }, []);

  const login = useCallback(async (email, password) => {
    const data = await authApi.login(email, password);
    await persistSession(data.token, data.user);
    return data;
  }, [persistSession]);

  const registerCustomer = useCallback(async (payload) => {
    const data = await authApi.registerCustomer(payload);
    if (data.token) {
      await persistSession(data.token, data.user);
    }
    return data;
  }, [persistSession]);

  const registerMuthowif = useCallback(async (payload) => {
    const data = await authApi.registerMuthowif(payload);
    if (data.token) {
      await persistSession(data.token, data.user);
    }
    return data;
  }, [persistSession]);

  const logout = useCallback(async () => {
    if (token) {
      try {
        await authApi.logout(token);
      } catch {
        // ignore network errors on logout
      }
    }
    setToken(null);
    setUser(null);
    await AsyncStorage.multiRemove([TOKEN_KEY, USER_KEY]);
  }, [token]);

  const value = useMemo(
    () => ({
      token,
      user,
      booting,
      isAuthenticated: Boolean(token && user),
      login,
      logout,
      registerCustomer,
      registerMuthowif,
    }),
    [token, user, booting, login, logout, registerCustomer, registerMuthowif],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
