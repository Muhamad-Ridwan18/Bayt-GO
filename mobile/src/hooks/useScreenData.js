import { useCallback, useState } from 'react';

export function useScreenData(loadFn) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const result = await loadFn();
      setData(result);
      setError(null);
      return result;
    } catch (err) {
      setError(err.message || 'Terjadi kesalahan');
      throw err;
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [loadFn]);

  const refresh = useCallback(() => load(true), [load]);

  return {
    data,
    setData,
    loading,
    refreshing,
    error,
    load,
    refresh,
    setError,
  };
}
