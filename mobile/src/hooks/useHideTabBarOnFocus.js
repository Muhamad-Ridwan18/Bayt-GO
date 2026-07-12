import { useCallback } from 'react';
import { useFocusEffect } from '@react-navigation/native';

export function useHideTabBarOnFocus(navigation) {
  useFocusEffect(
    useCallback(() => {
      const tab = navigation.getParent();
      if (!tab) return undefined;

      tab.setOptions({ tabBarStyle: { display: 'none' } });
      return () => tab.setOptions({ tabBarStyle: undefined });
    }, [navigation]),
  );
}
