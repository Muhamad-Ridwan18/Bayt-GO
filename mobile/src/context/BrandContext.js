import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { fetchHomeData } from '../api/home';

const BrandContext = createContext({ logoUrl: null, appName: 'BaytGo' });

export function BrandProvider({ children }) {
  const [logoUrl, setLogoUrl] = useState(null);
  const [appName, setAppName] = useState('BaytGo');

  useEffect(() => {
    fetchHomeData()
      .then((data) => {
        if (data.brand?.logo_url) setLogoUrl(data.brand.logo_url);
        if (data.brand?.name) setAppName(data.brand.name);
      })
      .catch(() => {});
  }, []);

  const value = useMemo(() => ({ logoUrl, appName }), [logoUrl, appName]);

  return <BrandContext.Provider value={value}>{children}</BrandContext.Provider>;
}

export function useBrand() {
  return useContext(BrandContext);
}
