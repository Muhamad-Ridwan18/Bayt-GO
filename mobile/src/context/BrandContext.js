import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { fetchHomeData } from '../api/home';

const BrandContext = createContext({
  logoUrl: null,
  appName: 'BaytGo',
  contactUrl: null,
  contactLabel: null,
});

export function BrandProvider({ children }) {
  const [logoUrl, setLogoUrl] = useState(null);
  const [appName, setAppName] = useState('BaytGo');
  const [contactUrl, setContactUrl] = useState(null);
  const [contactLabel, setContactLabel] = useState(null);

  useEffect(() => {
    fetchHomeData()
      .then((data) => {
        if (data.brand?.logo_url) setLogoUrl(data.brand.logo_url);
        if (data.brand?.name) setAppName(data.brand.name);
        setContactUrl(data.brand?.contact_url || null);
        setContactLabel(data.brand?.contact_label || null);
      })
      .catch(() => {});
  }, []);

  const value = useMemo(
    () => ({ logoUrl, appName, contactUrl, contactLabel }),
    [logoUrl, appName, contactUrl, contactLabel],
  );

  return <BrandContext.Provider value={value}>{children}</BrandContext.Provider>;
}

export function useBrand() {
  return useContext(BrandContext);
}
