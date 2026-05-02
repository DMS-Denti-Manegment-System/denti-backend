// src/modules/auth/Hooks/useAuth.ts

import { useCallback } from 'react';
import { usePage, router } from '@inertiajs/react';
import { App } from 'antd';

export const useAuth = () => {
  const { message } = App.useApp();
  const { auth } = usePage<any>().props;
  const user = auth.user;
  const isAuthenticated = !!user;

  const login = useCallback(async (data: any) => {
    router.post('/login', data);
  }, []);

  const adminLogin = useCallback(async (data: any) => {
    router.post('/login', data);
  }, []);

  const verify2fa = useCallback(async (data: any) => {
    router.post('/auth/2fa/verify', data);
  }, []);

  const logout = useCallback(async () => {
    router.post('/logout', {}, {
        onSuccess: () => message.success('Başarıyla çıkış yapıldı.'),
    });
  }, [message]);

  return {
    login,
    adminLogin,
    verify2fa,
    logout,
    user,
    isAuthenticated,
    loading: false,
    isSessionValidated: true,
    permissions: user?.permissions || []
  };
};

